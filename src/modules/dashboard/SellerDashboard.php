<?php
/**
 * VS System ERP - Seller Dashboard Logic
 */

namespace Vsys\Modules\Dashboard;

use Vsys\Lib\Database;
use PDO;

class SellerDashboard
{
    private $db;
    private $seller_id;

    public function __construct($userId)
    {
        $this->db = Database::getInstance();
        $this->seller_id = $userId;
    }

    public function getEfficiencyStats()
    {
        // Efficiency: Quotes vs Converted (simplified for now)
        $sql = "SELECT 
                    COUNT(*) as total,
                    SUM(CASE WHEN authorized_dispatch = 1 THEN 1 ELSE 0 END) as converted
                FROM quotations 
                WHERE seller_id = :sid";
        $stmt = $this->db->prepare($sql);
        $stmt->execute(['sid' => $this->seller_id]);
        return $stmt->fetch();
    }

    public function getRecentQuotes()
    {
        $sql = "SELECT q.*, e.name as client_name 
                FROM quotations q 
                JOIN entities e ON q.client_id = e.id 
                WHERE q.seller_id = :sid 
                ORDER BY q.created_at DESC 
                LIMIT 5";
        $stmt = $this->db->prepare($sql);
        $stmt->execute(['sid' => $this->seller_id]);
        return $stmt->fetchAll();
    }

    public function getClientShipments()
    {
        $sql = "SELECT l.*, e.name as client_name 
                FROM logistics_process l 
                JOIN quotations q ON l.quote_number = q.quote_number 
                JOIN entities e ON q.client_id = e.id 
                WHERE q.seller_id = :sid 
                ORDER BY l.updated_at DESC 
                LIMIT 5";
        $stmt = $this->db->prepare($sql);
        $stmt->execute(['sid' => $this->seller_id]);
        return $stmt->fetchAll();
    }
}
