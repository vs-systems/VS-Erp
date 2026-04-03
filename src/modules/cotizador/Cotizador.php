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
        $yearMonth = date('Y-m');
        $prefix = "VS-" . $yearMonth . "-";

        // Get the last number used this month to reset monthly
        $stmt = $this->db->prepare("SELECT quote_number FROM quotations WHERE quote_number LIKE :prefix ORDER BY id DESC LIMIT 1");
        $stmt->execute(['prefix' => $prefix . '%']);
        $lastQuote = $stmt->fetch();

        if ($lastQuote) {
            // Format: VS-YYYY-MM-XXXX_VV -> split by '-'
            $parts = explode('-', $lastQuote['quote_number']);
            $lastSegment = end($parts); // XXXX_VV
            $numPart = explode('_', $lastSegment)[0];
            $nextNum = (int) $numPart + 1;
        } else {
            $nextNum = 1;
        }

        $numberPart = str_pad($nextNum, 4, '0', STR_PAD_LEFT);
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
        // Calculate detailed IVAs if not provided
        $iva105 = $data['total_iva_105'] ?? 0;
        $iva21 = $data['total_iva_21'] ?? 0;

        // Generate Public Hash unique
        $publicHash = md5(uniqid(rand(), true));

        $sql = "INSERT INTO quotations (quote_number, version, client_id, user_id, payment_method, with_iva, exchange_rate_usd, subtotal_usd, total_iva_105, total_iva_21, subtotal_ars, total_usd, total_ars, valid_until, observations, public_hash) 
                VALUES (:number, :version, :client_id, :user_id, :payment, :with_iva, :rate, :subtotal, :iva105, :iva21, :subtotal_ars, :total_usd, :total_ars, :valid, :obs, :hash)";

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
            'iva105' => $iva105,
            'iva21' => $iva21,
            'subtotal_ars' => $data['subtotal_ars'],
            'total_usd' => $data['total_usd'],
            'total_ars' => $data['total_ars'],
            'valid' => $data['valid_until'],
            'obs' => $data['observations'] ?? '',
            'hash' => $publicHash
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

        return ['id' => $quotationId, 'hash' => $publicHash];
    }

    public function getQuotation($id)
    {
        $stmt = $this->db->prepare("SELECT q.*, e.name as client_name, e.tax_id, e.address, e.email as client_email, e.phone, e.transport, u.full_name as seller_name 
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

    /**
     * Get all quotations for listing
     */
    public function getAllQuotations($limit = 50)
    {
        $sql = "SELECT q.*, e.name as client_name 
                FROM quotations q 
                JOIN entities e ON q.client_id = e.id 
                ORDER BY q.created_at DESC 
                LIMIT :limit";
        $stmt = $this->db->prepare($sql);
        $stmt->bindValue(':limit', (int) $limit, \PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll();
    }
}
?>