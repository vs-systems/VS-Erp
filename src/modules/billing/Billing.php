<?php
namespace Vsys\Modules\Billing;

require_once dirname(__DIR__, 2) . '/lib/Database.php';
require_once __DIR__ . '/CurrentAccounts.php';

use Vsys\Lib\Database;
use Vsys\Modules\Billing\CurrentAccounts;
use PDO;
use Exception;

class Billing
{
    private $db;
    private $currentAccounts;

    public function __construct()
    {
        $this->db = Database::getInstance();
        $this->currentAccounts = new CurrentAccounts();
    }

    /**
     * Create a new Invoice
     */
    public function createInvoice($data)
    {
        try {
            $this->db->beginTransaction();

            $companyId = 1; // Default

            // 1. Generate Invoice Number (Simple incremental or format based)
            // Format: 0001-00000001
            $invoiceNumber = $this->generateNextInvoiceNumber($data['type'] ?? 'A');

            // 2. Insert Header
            $sql = "INSERT INTO invoices 
                    (company_id, client_id, quote_id, invoice_number, invoice_type, date, due_date, status, total_net, total_iva, total_amount, currency, exchange_rate, notes, created_at) 
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())";

            $stmt = $this->db->prepare($sql);
            $stmt->execute([
                $companyId,
                $data['client_id'],
                $data['quote_id'] ?? null,
                $invoiceNumber,
                $data['type'],
                $data['date'],
                $data['due_date'] ?? date('Y-m-d', strtotime('+30 days')),
                'Pendiente', // Default status
                $data['total_net'],
                $data['total_iva'],
                $data['total_amount'],
                $data['currency'] ?? 'ARS',
                $data['exchange_rate'] ?? 1,
                $data['notes'] ?? ''
            ]);

            $invoiceId = $this->db->lastInsertId();

            // 3. Insert Items
            if (!empty($data['items'])) {
                $sqlItem = "INSERT INTO invoice_items (invoice_id, description, quantity, unit_price, iva_rate, subtotal) VALUES (?, ?, ?, ?, ?, ?)";
                $stmtItem = $this->db->prepare($sqlItem);

                foreach ($data['items'] as $item) {
                    $stmtItem->execute([
                        $invoiceId,
                        $item['description'],
                        $item['quantity'],
                        $item['unit_price'],
                        $item['iva_rate'],
                        $item['subtotal']
                    ]);
                }
            }

            // 4. Register Movement in Current Account (Debit)
            // Use ARS amount for the movement balance if provided, otherwise use the header total
            $movementAmount = $data['total_amount_ars'] ?? $data['total_amount'];

            $this->currentAccounts->addMovement(
                $data['client_id'],
                'Factura',
                $invoiceId,
                $movementAmount,
                "Factura $invoiceNumber"
            );

            // 5. If linked to a Quote, update quote status
            if (!empty($data['quote_id'])) {
                $stmtQ = $this->db->prepare("UPDATE quotations SET status = 'ordered', is_confirmed = 1 WHERE id = ?");
                $stmtQ->execute([$data['quote_id']]);
            }

            $this->db->commit();
            return ['success' => true, 'invoice_id' => $invoiceId, 'invoice_number' => $invoiceNumber];

        } catch (Exception $e) {
            $this->db->rollBack();
            error_log("Billing Error: " . $e->getMessage());
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    /**
     * Generate next invoice number based on type
     */
    private function generateNextInvoiceNumber($type)
    {
        // Get last number for this type
        $stmt = $this->db->prepare("SELECT invoice_number FROM invoices WHERE invoice_type = ? ORDER BY id DESC LIMIT 1");
        $stmt->execute([$type]);
        $last = $stmt->fetchColumn();

        if ($last) {
            // Assume format XXXX-YYYYYYYY
            $parts = explode('-', $last);
            if (count($parts) == 2) {
                $prefix = $parts[0];
                $number = intval($parts[1]) + 1;
                return $prefix . '-' . str_pad($number, 8, '0', STR_PAD_LEFT);
            }
        }

        // Default start
        return '0001-00000001';
    }

    /**
     * Get recent invoices
     */
    public function getRecentInvoices($limit = 10)
    {
        $sql = "SELECT i.*, e.name as client_name, q.quote_number 
                FROM invoices i 
                LEFT JOIN entities e ON i.client_id = e.id 
                LEFT JOIN quotations q ON i.quote_id = q.id
                ORDER BY i.date DESC, i.id DESC 
                LIMIT " . (int) $limit;
        return $this->db->query($sql)->fetchAll();
    }

    public function updateStatus($invoiceId, $status)
    {
        $stmt = $this->db->prepare("UPDATE invoices SET status = ? WHERE id = ?");
        return $stmt->execute([$status, $invoiceId]);
    }
}
