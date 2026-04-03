<?php
namespace Vsys\Modules\Analysis;

/**
 * VS System ERP - Operations Analysis Module
 */

require_once __DIR__ . '/../../lib/Database.php';

use Vsys\Lib\Database;

class OperationAnalysis
{
    private $db;

    public function __construct()
    {
        $this->db = Database::getInstance();
    }

    /**
     * Get detailed data for a quotation and its potential/actual purchase costs
     */
    public function getQuotationAnalysis($quoteId)
    {
        // 1. Get Quotation Header and Items
        $sqlQuote = "SELECT q.*, e.name as client_name, e.tax_category, e.is_retention_agent 
                     FROM quotations q 
                     JOIN entities e ON q.client_id = e.id 
                     WHERE q.id = :id";
        $quote = $this->db->prepare($sqlQuote);
        $quote->execute([':id' => $quoteId]);
        $header = $quote->fetch();

        if (!$header)
            return null;

        $sqlItems = "SELECT qi.*, 
                            qi.quantity as qty,
                            qi.unit_price_usd as unit_price,
                            p.sku,
                            p.description, 
                            p.unit_cost_usd as catalog_cost 
                     FROM quotation_items qi 
                     LEFT JOIN products p ON qi.product_id = p.id 
                     WHERE qi.quotation_id = :id";
        $itemsStmt = $this->db->prepare($sqlItems);
        $itemsStmt->execute([':id' => $quoteId]);
        $items = $itemsStmt->fetchAll();

        // MERGE header fields into the top level array so $analysis['quote_number'] works
        $result = $header;

        $totalCost = 0;
        $processedItems = [];

        // Calculate dynamic totals for the view
        foreach ($items as $item) {
            // Find LATEST Purchase Order price for this SKU
            $sqlPurchase = "SELECT pi.unit_price_usd 
                            FROM purchase_items pi 
                            JOIN purchases p ON pi.purchase_id = p.id 
                            WHERE pi.sku = :sku 
                            ORDER BY p.purchase_date DESC, p.id DESC LIMIT 1";
            $stmtP = $this->db->prepare($sqlPurchase);
            $stmtP->execute([':sku' => $item['sku']]);
            $purchasePrice = $stmtP->fetchColumn();

            // Use Purchase Price if found, otherwise Catalog Cost
            $realCost = $purchasePrice !== false ? (float) $purchasePrice : (float) ($item['catalog_cost'] ?? 0);

            $item['unit_cost'] = $realCost;
            $item['is_real_cost'] = ($purchasePrice !== false);

            $totalCost += ($realCost * $item['qty']);
            $processedItems[] = $item;
        }

        $result['items'] = $processedItems;
        $result['total_revenue'] = $header['subtotal_usd']; // Assuming subtotal is net
        $result['total_cost'] = $totalCost;
        $result['profit'] = $result['total_revenue'] - $totalCost;
        $result['margin_percent'] = $result['total_revenue'] > 0 ? ($result['profit'] / $result['total_revenue']) * 100 : 0;
        $result['taxes'] = $result['total_revenue'] * 0.035; // Est. 3.5% IIBB
        $result['date'] = date('d/m/Y', strtotime($header['created_at']));

        return $result;
    }

    /**
     * Calculate Summary for Dashboard
     * Returns: Total Sales (Net), Total Purchases (Net), Total Expenses, Total Profit
     */
    public function getDashboardSummary()
    {
        // Check for columns to avoid Fatal error if migration hasn't run
        $quoteCols = $this->db->query("DESCRIBE quotations")->fetchAll(\PDO::FETCH_COLUMN);
        $purchaseCols = $this->db->query("DESCRIBE purchases")->fetchAll(\PDO::FETCH_COLUMN);

        $hasQuoteConfirmed = in_array('is_confirmed', $quoteCols);
        $hasPurchaseConfirmed = in_array('is_confirmed', $purchaseCols);
        $hasQuotePaymentStatus = in_array('payment_status', $quoteCols);
        $hasPurchasePaymentStatus = in_array('payment_status', $purchaseCols);

        // Net Sales (USD)
        $salesSql = $hasQuoteConfirmed
            ? "SELECT SUM(subtotal_usd) FROM quotations WHERE is_confirmed = 1"
            : "SELECT SUM(subtotal_usd) FROM quotations WHERE status = 'Aceptado'";
        $totalSales = $this->db->query($salesSql)->fetchColumn() ?: 0;

        // Net Purchases (USD)
        $purchasesSql = $hasPurchasePaymentStatus
            ? "SELECT SUM(subtotal_usd) FROM purchases WHERE payment_status = 'Pagado'"
            : "SELECT SUM(subtotal_usd) FROM purchases WHERE status = 'Pagado'";
        $totalPurchases = $this->db->query($purchasesSql)->fetchColumn() ?: 0;

        // Effectiveness
        $totalQuotes = $this->db->query("SELECT COUNT(*) FROM quotations")->fetchColumn() ?: 0;
        $acceptedQuotesSql = $hasQuoteConfirmed
            ? "SELECT COUNT(*) FROM quotations WHERE is_confirmed = 1"
            : "SELECT COUNT(*) FROM quotations WHERE status = 'Aceptado'";
        $acceptedQuotes = $this->db->query($acceptedQuotesSql)->fetchColumn() ?: 0;
        $effectiveness = $totalQuotes > 0 ? ($acceptedQuotes / $totalQuotes) * 100 : 0;

        // Commercial Status Summaries (Real data from Current Accounts)
        $pendingCollections = 0;
        $pendingPayments = 0;

        // Verify tables exist before querying to avoid Fatal Errors during migration
        $tables = $this->db->query("SHOW TABLES")->fetchAll(\PDO::FETCH_COLUMN);

        if (in_array('client_movements', $tables)) {
            $pendingCollections = $this->db->query("SELECT SUM(debit) - SUM(credit) FROM client_movements")->fetchColumn() ?: 0;
        }

        if (in_array('provider_movements', $tables)) {
            $pendingPayments = $this->db->query("SELECT SUM(debit) - SUM(credit) FROM provider_movements")->fetchColumn() ?: 0;
        }

        return [
            'total_sales' => $totalSales,
            'total_purchases' => $totalPurchases,
            'total_profit' => $totalSales - $totalPurchases,
            'pending_collections' => (float) $pendingCollections,
            'pending_payments' => (float) $pendingPayments,
            'quotations_total' => $totalQuotes,
            'orders_total' => $acceptedQuotes,
            'effectiveness' => round($effectiveness, 2)
        ];
    }

    /**
     * Get counts for status charts
     */
    public function getStatusStats()
    {
        // Quotations Status
        // Quotations Status
        $qConfirm = $this->db->query("SELECT COUNT(*) FROM quotations WHERE is_confirmed = 1")->fetchColumn() ?: 0;
        $qPend = $this->db->query("SELECT COUNT(*) FROM quotations WHERE is_confirmed = 0 AND (status != 'Perdido' OR status IS NULL)")->fetchColumn() ?: 0;
        $qLost = $this->db->query("SELECT COUNT(*) FROM quotations WHERE status = 'Perdido'")->fetchColumn() ?: 0;

        // Purchases Status
        $pPend = $this->db->query("SELECT COUNT(*) FROM purchases WHERE status = 'Pendiente' AND is_confirmed = 0")->fetchColumn() ?: 0;
        $pConfirmed = $this->db->query("SELECT COUNT(*) FROM purchases WHERE is_confirmed = 1 AND payment_status != 'Pagado'")->fetchColumn() ?: 0;
        $pPaid = $this->db->query("SELECT COUNT(*) FROM purchases WHERE payment_status = 'Pagado'")->fetchColumn() ?: 0;
        $pCanceled = $this->db->query("SELECT COUNT(*) FROM purchases WHERE status = 'Cancelado'")->fetchColumn() ?: 0;

        return [
            'quotations' => [
                'confirmadas' => (int) $qConfirm,
                'pendientes' => (int) $qPend,
                'perdidas' => (int) $qLost
            ],
            'purchases' => [
                'pendientes' => (int) $pPend,
                'confirmadas' => (int) $pConfirmed,
                'pagadas' => (int) $pPaid,
                'canceladas' => (int) $pCanceled
            ]
        ];
    }

    /**
     * Global Profitability Report Data
     */
    public function getGlobalProfitabilitySummary()
    {
        // Total of ALL quotes revenue (confirmed)
        $sql = "SELECT SUM(subtotal_usd) FROM quotations WHERE is_confirmed = 1";
        $totalRevenue = $this->db->query($sql)->fetchColumn() ?: 0;

        // Calculate total cost of all items in confirmed quotes
        $sqlItems = "SELECT qi.product_id, qi.quantity, p.sku, p.unit_cost_usd as catalog_cost 
                     FROM quotation_items qi
                     JOIN quotations q ON qi.quotation_id = q.id
                     LEFT JOIN products p ON qi.product_id = p.id
                     WHERE q.is_confirmed = 1";
        $items = $this->db->query($sqlItems)->fetchAll();

        $totalCost = 0;
        foreach ($items as $item) {
            // Priority: Real purchase cost, then catalog cost
            $sqlP = "SELECT unit_price_usd FROM purchase_items pi 
                     JOIN purchases p ON pi.purchase_id = p.id 
                     WHERE pi.product_id = ? ORDER BY p.purchase_date DESC LIMIT 1";
            $realCost = $this->db->prepare($sqlP);
            $realCost->execute([$item['product_id']]);
            $cost = $realCost->fetchColumn();

            if ($cost === false) {
                $cost = $item['catalog_cost'] ?: 0;
            }

            $totalCost += ($cost * $item['quantity']);
        }

        $profit = $totalRevenue - $totalCost;
        $avgMargin = $totalRevenue > 0 ? ($profit / $totalRevenue) * 100 : 0;

        return [
            'total_revenue' => $totalRevenue,
            'total_cost' => $totalCost,
            'total_profit' => $profit,
            'avg_margin' => round($avgMargin, 2)
        ];
    }

    /**
     * Get monthly metrics for the chart
     */
    public function getMonthlyProfitability($months = 6)
    {
        $data = [];
        for ($i = $months - 1; $i >= 0; $i--) {
            $monthStart = date('Y-m-01', strtotime("-$i months"));
            $monthEnd = date('Y-m-t', strtotime("-$i months"));

            // Map English months to Spanish for UI
            $monthsEs = [
                'Jan' => 'Ene',
                'Feb' => 'Feb',
                'Mar' => 'Mar',
                'Apr' => 'Abr',
                'May' => 'May',
                'Jun' => 'Jun',
                'Jul' => 'Jul',
                'Aug' => 'Ago',
                'Sep' => 'Sep',
                'Oct' => 'Oct',
                'Nov' => 'Nov',
                'Dec' => 'Dic'
            ];
            $monthLabel = $monthsEs[date('M', strtotime("-$i months"))];

            // Sales this month (USD)
            $salesSql = "SELECT SUM(subtotal_usd) FROM quotations WHERE is_confirmed = 1 AND created_at >= :start AND created_at <= :end";
            $salesStmt = $this->db->prepare($salesSql);
            $salesStmt->execute([':start' => $monthStart . ' 00:00:00', ':end' => $monthEnd . ' 23:59:59']);
            $sales = $salesStmt->fetchColumn() ?: 0;

            // Purchases this month (USD)
            $purchasesSql = "SELECT SUM(subtotal_usd) FROM purchases WHERE is_confirmed = 1 AND purchase_date >= :start AND purchase_date <= :end";
            $purchStmt = $this->db->prepare($purchasesSql);
            $purchStmt->execute([':start' => $monthStart, ':end' => $monthEnd]);
            $purchases = $purchStmt->fetchColumn() ?: 0;

            $data[] = [
                'month' => $monthLabel,
                'sales' => (float) $sales,
                'purchases' => (float) $purchases,
                'profit' => (float) ($sales - $purchases)
            ];
        }
        return $data;
    }
}
