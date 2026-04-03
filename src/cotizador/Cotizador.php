<?php
/**
 * VS System ERP - Quotation Module
 */

namespace Vsys\Modules\Cotizador;

use Vsys\Lib\Database;
use Vsys\Lib\BCRAClient;

class Cotizador
{
    private $db;

    public function __construct()
    {
        $this->db = Database::getInstance();
    }

    /**
     * Generate unique quote number: VS-COT-YYYY-MM-DD-NUMBER_VERSION
     */
    public function generateQuoteNumber($clientId)
    {
        $dateStr = date('Y-m-d');
        $prefix = "VS-COT-" . $dateStr . "-";

        // Count quotes for today to get the next number
        $stmt = $this->db->prepare("SELECT COUNT(*) FROM quotations WHERE DATE(created_at) = CURDATE()");
        $stmt->execute();
        $count = $stmt->fetchColumn() + 1;
        $numberPart = str_pad($count, 4, '0', STR_PAD_LEFT);

        return $prefix . $numberPart . "_01";
    }

    /**
     * Create a new version of an existing quote
     */
    public function createNewVersion($parentQuoteId)
    {
        $stmt = $this->db->prepare("SELECT quote_number, version FROM quotations WHERE id = ?");
        $stmt->execute([$parentQuoteId]);
        $parent = $stmt->fetch();

        if (!$parent)
            return null;

        $newVersion = $parent['version'] + 1;
        $baseNumber = explode('_', $parent['quote_number'])[0];
        $newQuoteNumber = $baseNumber . "_" . str_pad($newVersion, 2, '0', STR_PAD_LEFT);

        return [
            'number' => $newQuoteNumber,
            'version' => $newVersion
        ];
    }

    /**
     * Calculate adjusted price based on special rules:
     * - Retention Agent: +7% (Hidden in selling price)
     * - Bank Deposit: +3% (Hidden in selling price)
     */
    public function getAdjustedPrice($basePrice, $isRetentionAgent = false, $isBankDeposit = false)
    {
        $price = $basePrice;
        if ($isRetentionAgent) {
            $price *= 1.07;
        }
        if ($isBankDeposit) {
            $price *= 1.03;
        }
        return round($price, 2);
    }

    /**
     * Calculate ARS total based on BNA rate
     */
    public function calculateArsTotal($usdTotal, $rate)
    {
        return round($usdTotal * $rate, 2);
    }

    public function saveQuotation($data)
    {
        $sql = "INSERT INTO quotations (quote_number, version, client_id, user_id, payment_method, with_iva, exchange_rate_usd, subtotal_usd, total_usd, total_ars, valid_until) 
                VALUES (:number, :version, :client_id, :user_id, :payment, :with_iva, :rate, :subtotal, :total_usd, :total_ars, :valid)";

        $stmt = $this->db->prepare($sql);
        $result = $stmt->execute([
            'number' => $data['quote_number'],
            'version' => $data['version'] ?? 1,
            'client_id' => $data['client_id'],
            'user_id' => $data['user_id'],
            'payment' => $data['payment_method'],
            'with_iva' => $data['with_iva'],
            'rate' => $data['exchange_rate_usd'],
            'subtotal' => $data['subtotal_usd'],
            'total_usd' => $data['total_usd'],
            'total_ars' => $data['total_ars'],
            'valid' => $data['valid_until']
        ]);

        if (!$result)
            return false;

        $quotationId = $this->db->lastInsertId();

        // Save items
        foreach ($data['items'] as $item) {
            $sqlItem = "INSERT INTO quotation_items (quotation_id, product_id, quantity, unit_price_usd, subtotal_usd, iva_rate) 
                        VALUES (:qid, :pid, :qty, :price, :sub, :iva)";
            $stmtItem = $this->db->prepare($sqlItem);
            $stmtItem->execute([
                'qid' => $quotationId,
                'pid' => $item['product_id'],
                'qty' => $item['quantity'],
                'price' => $item['unit_price_usd'],
                'sub' => $item['subtotal_usd'],
                'iva' => $item['iva_rate']
            ]);
        }

        return $quotationId;
    }

    public function getQuotation($id)
    {
        $stmt = $this->db->prepare("SELECT q.*, e.name as client_name, e.tax_id, e.address, e.email as client_email, e.phone, u.full_name as seller_name 
                                    FROM quotations q 
                                    JOIN entities e ON q.client_id = e.id 
                                    JOIN users u ON q.user_id = u.id 
                                    WHERE q.id = ?");
        $stmt->execute([$id]);
        return $stmt->fetch();
    }

    public function getQuotationItems($id)
    {
        $stmt = $this->db->prepare("SELECT qi.*, p.sku, p.description 
                                    FROM quotation_items qi 
                                    JOIN products p ON qi.product_id = p.id 
                                    WHERE qi.quotation_id = ?");
        $stmt->execute([$id]);
        return $stmt->fetchAll();
    }
}
?>

