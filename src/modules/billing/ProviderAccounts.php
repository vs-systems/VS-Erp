<?php
namespace Vsys\Modules\Billing;

require_once dirname(__DIR__, 2) . '/lib/Database.php';

use Vsys\Lib\Database;
use PDO;
use Exception;

class ProviderAccounts
{
    private $db;

    public function __construct()
    {
        $this->db = Database::getInstance();
    }

    public function addMovement($providerId, $type, $referenceId, $amount, $notes = '')
    {
        try {
            $companyId = 1;
            $debit = 0; // Debt (Compra)
            $credit = 0; // Payment

            switch ($type) {
                case 'Compra':
                case 'Nota de Débito':
                case 'Saldo Inicial':
                    $debit = $amount;
                    break;
                case 'Pago':
                case 'Nota de Crédito':
                    $credit = $amount;
                    break;
                default:
                    throw new Exception("Tipo de movimiento inválido: $type");
            }

            $currentBalance = $this->getBalance($providerId);
            $newBalance = $currentBalance + $debit - $credit;

            $sql = "INSERT INTO provider_movements 
                    (company_id, provider_id, type, reference_id, debit, credit, balance, notes, date) 
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, NOW())";

            $stmt = $this->db->prepare($sql);
            $stmt->execute([
                $companyId,
                $providerId,
                $type,
                $referenceId,
                $debit,
                $credit,
                $newBalance,
                $notes
            ]);

            $lastId = $this->db->lastInsertId();

            // --- TREASURY INTEGRATION ---
            if ($type === 'Pago') {
                try {
                    $this->db->prepare("INSERT INTO treasury_movements (type, category, amount, notes, reference_id, reference_type) VALUES (?, ?, ?, ?, ?, ?)")
                        ->execute(['Egreso', 'Compras', $amount, $notes, $lastId, 'provider_payment']);
                } catch (\Exception $te) {
                    error_log("Treasury Auto-Log Provider Error: " . $te->getMessage());
                }
            }

            return $lastId;
        } catch (Exception $e) {
            error_log("ProviderAccounts Error: " . $e->getMessage());
            throw $e;
        }
    }

    public function getBalance($providerId)
    {
        $stmt = $this->db->prepare("SELECT SUM(debit) - SUM(credit) as balance FROM provider_movements WHERE provider_id = ?");
        $stmt->execute([$providerId]);
        return (float) $stmt->fetchColumn() ?: 0.00;
    }

    public function getMovements($providerId, $limit = 50)
    {
        $stmt = $this->db->prepare("
            SELECT pm.*, 
                   DATE_FORMAT(pm.date, '%d/%m/%Y %H:%i') as formatted_date
            FROM provider_movements pm
            WHERE pm.provider_id = ?
            ORDER BY pm.date DESC, pm.id DESC
            LIMIT " . (int) $limit
        );
        $stmt->execute([$providerId]);
        return $stmt->fetchAll();
    }

    public function getProvidersWithBalances()
    {
        $sql = "SELECT 
                    e.id, 
                    e.name, 
                    e.contact_person,
                    COALESCE(SUM(pm.debit), 0) as total_debit,
                    COALESCE(SUM(pm.credit), 0) as total_credit,
                    (COALESCE(SUM(pm.debit), 0) - COALESCE(SUM(pm.credit), 0)) as balance,
                    MAX(pm.date) as last_movement
                FROM entities e
                LEFT JOIN provider_movements pm ON e.id = pm.provider_id
                WHERE e.type = 'provider'
                GROUP BY e.id
                ORDER BY balance DESC";

        return $this->db->query($sql)->fetchAll();
    }

    public function deleteByReference($referenceId, $type)
    {
        try {
            $stmt = $this->db->prepare("SELECT id FROM provider_movements WHERE reference_id = ? AND type = ?");
            $stmt->execute([$referenceId, $type]);
            $ids = $stmt->fetchAll(PDO::FETCH_COLUMN);

            if (!empty($ids)) {
                $placeholders = implode(',', array_fill(0, count($ids), '?'));
                $this->db->prepare("DELETE FROM treasury_movements WHERE reference_id IN ($placeholders) AND reference_type = 'provider_payment'")->execute($ids);
                $this->db->prepare("DELETE FROM provider_movements WHERE id IN ($placeholders)")->execute($ids);
            }
            return true;
        } catch (Exception $e) {
            error_log("ProviderAccounts Delete Error: " . $e->getMessage());
            return false;
        }
    }
}
