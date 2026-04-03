<?php
namespace Vsys\Modules\Treasury;

require_once dirname(__DIR__, 2) . '/lib/Database.php';

use Vsys\Lib\Database;
use PDO;
use Exception;

class Treasury
{
    private $db;

    public function __construct()
    {
        $this->db = Database::getInstance();
    }

    /**
     * Add a movement to Treasury
     */
    public function addMovement($data)
    {
        try {
            $sql = "INSERT INTO treasury_movements 
                    (type, category, amount, currency, payment_method, reference_id, reference_type, notes, created_by) 
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)";

            $stmt = $this->db->prepare($sql);
            $stmt->execute([
                $data['type'], // 'Ingreso' or 'Egreso'
                $data['category'] ?? 'Varios',
                $data['amount'],
                $data['currency'] ?? 'ARS',
                $data['payment_method'] ?? 'Efectivo',
                $data['reference_id'] ?? null,
                $data['reference_type'] ?? null,
                $data['notes'] ?? '',
                $_SESSION['user_id'] ?? null
            ]);

            return $this->db->lastInsertId();
        } catch (Exception $e) {
            error_log("Treasury Error: " . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Get summary of current balances (grouped by payment method)
     */
    public function getBalanceSummary()
    {
        $sql = "SELECT payment_method, 
                       SUM(CASE WHEN type = 'Ingreso' THEN amount ELSE -amount END) as balance
                FROM treasury_movements 
                GROUP BY payment_method";
        return $this->db->query($sql)->fetchAll();
    }

    /**
     * Get recent movements
     */
    public function getRecentMovements($limit = 50)
    {
        $sql = "SELECT *, DATE_FORMAT(date, '%d/%m/%Y %H:%i') as formatted_date 
                FROM treasury_movements 
                ORDER BY date DESC, id DESC 
                LIMIT " . (int) $limit;
        return $this->db->query($sql)->fetchAll();
    }

    /**
     * Get totals for dashboard
     */
    public function getTotals()
    {
        $totalIn = $this->db->query("SELECT SUM(amount) FROM treasury_movements WHERE type = 'Ingreso'")->fetchColumn() ?: 0;
        $totalOut = $this->db->query("SELECT SUM(amount) FROM treasury_movements WHERE type = 'Egreso'")->fetchColumn() ?: 0;
        $totalWithholdings = $this->db->query("SELECT SUM(amount) FROM treasury_movements WHERE category = 'Retenciones' OR payment_method = 'Retenciones'")->fetchColumn() ?: 0;

        return [
            'total_in' => (float) $totalIn,
            'total_out' => (float) $totalOut,
            'total_withholdings' => (float) $totalWithholdings,
            'net_cash' => (float) ($totalIn - $totalOut)
        ];
    }
}
