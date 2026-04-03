<?php
namespace Vsys\Modules\Billing;

require_once dirname(__DIR__, 2) . '/lib/Database.php';

use Vsys\Lib\Database;
use PDO;
use Exception;

class CurrentAccounts
{
    private $db;

    public function __construct()
    {
        $this->db = Database::getInstance();
    }

    /**
     * Add a movement to the client's current account
     */
    public function addMovement($clientId, $type, $referenceId, $amount, $notes = '')
    {
        try {
            $companyId = 1; // Default or fetch from session/context

            $debit = 0;
            $credit = 0;

            // Define Debit/Credit based on type
            // Debit = Client owes us (Invoice, Debit Note)
            // Credit = Client pays us or we owe them (Receipt, Credit Note)
            switch ($type) {
                case 'Factura':
                case 'Nota de Débito':
                case 'Saldo Inicial': // If positive debt
                    $debit = $amount;
                    break;
                case 'Recibo':
                case 'Nota de Crédito':
                case 'Pago':
                    $credit = $amount;
                    break;
                default:
                    throw new Exception("Tipo de movimiento inválido: $type");
            }

            // Calculate new balance based on previous balance approach or aggregate approach
            // Here we just insert, getBalance calculates real time.
            // But we can store snapshot for performance, let's store it.
            $currentBalance = $this->getBalance($clientId);
            $newBalance = $currentBalance + $debit - $credit;

            $sql = "INSERT INTO client_movements 
                    (company_id, client_id, type, reference_id, debit, credit, balance, notes, date) 
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, NOW())";

            $stmt = $this->db->prepare($sql);
            $stmt->execute([
                $companyId,
                $clientId,
                $type,
                $referenceId,
                $debit,
                $credit,
                $newBalance,
                $notes
            ]);

            $lastId = $this->db->lastInsertId();

            // --- TREASURY INTEGRATION ---
            if ($type === 'Recibo' || $type === 'Pago') {
                try {
                    $this->db->prepare("INSERT INTO treasury_movements (type, category, amount, notes, reference_id, reference_type) VALUES (?, ?, ?, ?, ?, ?)")
                        ->execute(['Ingreso', 'Ventas', $amount, $notes, $lastId, 'client_payment']);
                } catch (\Exception $te) {
                    // Silently fail if table not migrated yet
                    error_log("Treasury Auto-Log Error: " . $te->getMessage());
                }
            }

            return $lastId;

        } catch (Exception $e) {
            error_log("CurrentAccounts Error: " . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Get current balance for a client
     */
    public function getBalance($clientId)
    {
        $stmt = $this->db->prepare("SELECT SUM(debit) - SUM(credit) as balance FROM client_movements WHERE client_id = ?");
        $stmt->execute([$clientId]);
        return (float) $stmt->fetchColumn() ?: 0.00;
    }

    /**
     * Get movements history for a client
     */
    public function getMovements($clientId, $limit = 50)
    {
        $stmt = $this->db->prepare("
            SELECT cm.*, 
                   DATE_FORMAT(cm.date, '%d/%m/%Y %H:%i') as formatted_date
            FROM client_movements cm
            WHERE cm.client_id = ?
            ORDER BY cm.date DESC, cm.id DESC
            LIMIT " . (int) $limit
        );
        $stmt->execute([$clientId]);
        return $stmt->fetchAll();
    }

    /**
     * List all clients with their current balances
     */
    public function getClientsWithBalances()
    {
        // This query aggregates movements to show current state
        $sql = "SELECT 
                    e.id, 
                    e.name, 
                    e.contact_person,
                    COALESCE(SUM(cm.debit), 0) as total_debit,
                    COALESCE(SUM(cm.credit), 0) as total_credit,
                    (COALESCE(SUM(cm.debit), 0) - COALESCE(SUM(cm.credit), 0)) as balance,
                    MAX(cm.date) as last_movement
                FROM entities e
                LEFT JOIN client_movements cm ON e.id = cm.client_id
                WHERE e.type = 'client'
                GROUP BY e.id
                HAVING balance != 0 OR last_movement IS NOT NULL
                ORDER BY balance DESC";

        return $this->db->query($sql)->fetchAll();
    }

    public function deleteByReference($referenceId, $type)
    {
        try {
            $stmt = $this->db->prepare("SELECT id FROM client_movements WHERE reference_id = ? AND type = ?");
            $stmt->execute([$referenceId, $type]);
            $ids = $stmt->fetchAll(PDO::FETCH_COLUMN);

            if (!empty($ids)) {
                $placeholders = implode(',', array_fill(0, count($ids), '?'));
                $this->db->prepare("DELETE FROM treasury_movements WHERE reference_id IN ($placeholders) AND reference_type = 'client_payment'")->execute($ids);
                $this->db->prepare("DELETE FROM client_movements WHERE id IN ($placeholders)")->execute($ids);
            }
            return true;
        } catch (Exception $e) {
            error_log("CurrentAccounts Delete Error: " . $e->getMessage());
            return false;
        }
    }
}
