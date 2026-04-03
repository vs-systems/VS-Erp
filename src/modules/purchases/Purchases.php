<?php
/**
 * VS System ERP - Purchases Module
 */

namespace Vsys\Modules\Purchases;

require_once __DIR__ . '/../../lib/Database.php';
require_once __DIR__ . '/../billing/ProviderAccounts.php';

use Vsys\Lib\Database;
use Vsys\Modules\Billing\ProviderAccounts;

class Purchases
{
    private $db;

    public function __construct()
    {
        $this->db = Database::getInstance();
    }

    /**
     * Create a new purchase order
     */
    public function savePurchase($data)
    {
        try {
            $this->db->beginTransaction();

            // 1. Insert/Update header
            if (isset($data['id']) && $data['id'] > 0) {
                // Update logic if needed (usually purchases aren't updated this way but let's be flexible)
                $sql = "UPDATE purchases SET 
                        purchase_number = :purchase_number,
                        entity_id = :entity_id,
                        purchase_date = :purchase_date,
                        exchange_rate_usd = :exchange_rate_usd,
                        subtotal_usd = :subtotal_usd,
                        subtotal_ars = :subtotal_ars,
                        total_usd = :total_usd,
                        total_ars = :total_ars,
                        status = :status,
                        is_confirmed = :is_confirmed,
                        payment_status = :payment_status,
                        notes = :notes
                        WHERE id = :id";
                $stmt = $this->db->prepare($sql);
                $stmt->execute([
                    ':purchase_number' => $data['purchase_number'],
                    ':entity_id' => $data['entity_id'],
                    ':purchase_date' => $data['purchase_date'],
                    ':exchange_rate_usd' => $data['exchange_rate_usd'],
                    ':subtotal_usd' => $data['subtotal_usd'],
                    ':subtotal_ars' => $data['subtotal_ars'],
                    ':total_usd' => $data['total_usd'],
                    ':total_ars' => $data['total_ars'],
                    ':status' => $data['status'] ?? 'Pendiente',
                    ':is_confirmed' => $data['is_confirmed'] ?? 0,
                    ':payment_status' => $data['payment_status'] ?? 'Pendiente',
                    ':notes' => $data['notes'] ?? '',
                    ':id' => $data['id']
                ]);
                $purchaseId = $data['id'];

                // Remove old items for a clean update
                $this->db->prepare("DELETE FROM purchase_items WHERE purchase_id = :pid")->execute([':pid' => $purchaseId]);
            } else {
                $sql = "INSERT INTO purchases (purchase_number, entity_id, purchase_date, exchange_rate_usd, subtotal_usd, subtotal_ars, total_usd, total_ars, status, is_confirmed, payment_status, notes) 
                        VALUES (:purchase_number, :entity_id, :purchase_date, :exchange_rate_usd, :subtotal_usd, :subtotal_ars, :total_usd, :total_ars, :status, :is_confirmed, :payment_status, :notes)";
                $stmt = $this->db->prepare($sql);
                $stmt->execute([
                    ':purchase_number' => $data['purchase_number'],
                    ':entity_id' => $data['entity_id'],
                    ':purchase_date' => $data['purchase_date'],
                    ':exchange_rate_usd' => $data['exchange_rate_usd'],
                    ':subtotal_usd' => $data['subtotal_usd'],
                    ':subtotal_ars' => $data['subtotal_ars'],
                    ':total_usd' => $data['total_usd'],
                    ':total_ars' => $data['total_ars'],
                    ':status' => $data['status'] ?? 'Pendiente',
                    ':is_confirmed' => $data['is_confirmed'] ?? 0,
                    ':payment_status' => $data['payment_status'] ?? 'Pendiente',
                    ':notes' => $data['notes'] ?? ''
                ]);
                $purchaseId = $this->db->lastInsertId();
            }

            // 2. Insert items
            $sqlItem = "INSERT INTO purchase_items (purchase_id, product_id, sku, description, qty, iva_rate, unit_price_usd, unit_price_ars, total_usd) 
                        VALUES (:pid, :prod_id, :sku, :desc, :qty, :iva, :up_usd, :up_ars, :total)";
            $stmtItem = $this->db->prepare($sqlItem);

            foreach ($data['items'] as $item) {
                $productId = $item['product_id'] ?? null;

                // Handle quick-add products (prefix 'new-')
                if (is_string($productId) && strpos($productId, 'new-') === 0) {
                    $insertProdSql = "INSERT INTO products (sku, description, unit_cost_usd, unit_price_usd, iva_rate) 
                                     VALUES (:sku, :desc, :cost, :price, :iva)";
                    $stmtProd = $this->db->prepare($insertProdSql);
                    $stmtProd->execute([
                        ':sku' => $item['sku'],
                        ':desc' => $item['description'],
                        ':cost' => $item['unit_price_usd'],
                        ':price' => $item['unit_price_usd'] * 1.3, // Default 30% margin if new
                        ':iva' => $item['iva_rate'] ?? 21.00
                    ]);
                    $productId = $this->db->lastInsertId();
                }

                // Determine ARS unit price
                $up_ars = $item['unit_price_ars'] ?? 0;
                $up_usd = $item['unit_price_usd'] ?? 0;

                $stmtItem->execute([
                    ':pid' => $purchaseId,
                    ':prod_id' => $productId,
                    ':sku' => $item['sku'] ?? '',
                    ':desc' => $item['description'] ?? '',
                    ':qty' => $item['qty'],
                    ':iva' => $item['iva_rate'] ?? 21.00,
                    ':up_usd' => $up_usd,
                    ':up_ars' => $up_ars,
                    ':total' => $item['qty'] * $up_usd
                ]);
            }

            $this->db->commit();

            // 3. Register movement in Provider Account if confirmed
            if (!empty($data['is_confirmed'])) {
                $providerAccounts = new ProviderAccounts();
                $providerAccounts->addMovement(
                    $data['entity_id'],
                    'Compra',
                    $purchaseId,
                    $data['total_ars'],
                    "Compra #{$data['purchase_number']}"
                );
            }

            return $purchaseId;
        } catch (\Exception $e) {
            $this->db->rollBack();
            error_log("Error in savePurchase: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Get all purchases with supplier info
     */
    public function getAllPurchases()
    {
        $sql = "SELECT p.*, e.name as supplier_name 
                FROM purchases p 
                JOIN entities e ON p.entity_id = e.id 
                ORDER BY p.purchase_date DESC, p.id DESC";
        return $this->db->query($sql)->fetchAll();
    }

    /**
     * Get pending purchases (unconfirmed or unpaid)
     */
    public function getPendingPurchases()
    {
        $sql = "SELECT p.*, e.name as supplier_name 
                FROM purchases p 
                JOIN entities e ON p.entity_id = e.id 
                WHERE p.is_confirmed = 0 OR p.payment_status != 'Pagado'
                ORDER BY p.purchase_date ASC";
        return $this->db->query($sql)->fetchAll();
    }

    /**
     * Get unique purchase number
     */
    public function generatePurchaseNumber()
    {
        // Format: VS-YYYY-MM-XXXX (Reset monthly)
        $prefix = "VS-" . date('Y-m') . "-";

        // Find last number for this month
        $sql = "SELECT purchase_number FROM purchases WHERE purchase_number LIKE :prefix ORDER BY id DESC LIMIT 1";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([':prefix' => $prefix . '%']);
        $last = $stmt->fetchColumn();

        if ($last) {
            // Split by '-' and get the last part (sequence)
            $parts = explode('-', $last);
            $seq = intval(end($parts)) + 1;
        } else {
            $seq = 1;
        }

        return $prefix . str_pad($seq, 4, '0', STR_PAD_LEFT);
    }
    /**
     * Get a single purchase by ID
     */
    public function getPurchase($id)
    {
        $sql = "SELECT p.*, e.name as supplier_name, e.tax_id
                FROM purchases p 
                LEFT JOIN entities e ON p.entity_id = e.id 
                WHERE p.id = :id";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([':id' => $id]);
        return $stmt->fetch();
    }

    /**
     * Get items for a purchase
     */
    public function getPurchaseItems($purchaseId)
    {
        $sql = "SELECT * FROM purchase_items WHERE purchase_id = :pid";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([':pid' => $purchaseId]);
        return $stmt->fetchAll();
    }
}


