<?php
/**
 * VS System ERP - CRM Module Logic
 */

namespace Vsys\Modules\CRM;

require_once __DIR__ . '/../../lib/Database.php';

use Vsys\Lib\Database;

class CRM
{
    private $db;

    public function __construct()
    {
        $this->db = Database::getInstance();
    }

    /**
     * Delete a lead by ID
     */
    public function deleteLead($id)
    {
        try {
            // Also delete interactions? Or keep them? Usually cascade or soft delete.
            // For now hard delete as requested.
            $this->db->prepare("DELETE FROM crm_interactions WHERE entity_type = 'lead' AND entity_id = ?")->execute([$id]);
            $stmt = $this->db->prepare("DELETE FROM crm_leads WHERE id = ?");
            return $stmt->execute([$id]);
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * Get Leads count by status
     */
    /**
     * Get General CRM Stats for Dashboard
     */
    /**
     * Get General CRM Stats for Dashboard
     */
    public function getStats($date = null)
    {
        try {
            // Active Quotes (Presupuestado)
            $activeQuotes = $this->db->query("SELECT COUNT(*) FROM crm_leads WHERE status = 'Presupuestado'")->fetchColumn();

            // Orders Today (Ganado today) - Use provided date or CURDATE()
            $dateFilter = $date ? $date : date('Y-m-d');
            $stmt = $this->db->prepare("SELECT COUNT(*) FROM crm_leads WHERE status = 'Ganado' AND DATE(updated_at) = ?");
            $stmt->execute([$dateFilter]);
            $ordersToday = $stmt->fetchColumn();

            // Efficiency (Won / Total)
            $total = $this->db->query("SELECT COUNT(*) FROM crm_leads")->fetchColumn();
            $won = $this->db->query("SELECT COUNT(*) FROM crm_leads WHERE status = 'Ganado'")->fetchColumn();
            $efficiency = $total > 0 ? round(($won / $total) * 100, 1) : 0;

            return [
                'active_quotes' => $activeQuotes ?: 0,
                'orders_today' => $ordersToday ?: 0,
                'efficiency' => $efficiency
            ];
        } catch (\Exception $e) {
            return [
                'active_quotes' => 0,
                'orders_today' => 0,
                'efficiency' => 0
            ];
        }
    }

    public function getLeadsStats()
    {
        try {
            // Using CASE to ensure all statuses are present even if count is 0
            $sql = "SELECT 
                        SUM(CASE WHEN status = 'Nuevo' THEN 1 ELSE 0 END) as 'Nuevos Leads',
                        SUM(CASE WHEN status = 'Contactado' THEN 1 ELSE 0 END) as 'En Contacto',
                        SUM(CASE WHEN status = 'Presupuestado' THEN 1 ELSE 0 END) as 'Cotizados',
                        SUM(CASE WHEN status = 'Ganado' THEN 1 ELSE 0 END) as 'Ganados',
                        SUM(CASE WHEN status = 'Perdido' THEN 1 ELSE 0 END) as 'Perdidos'
                    FROM crm_leads";
            return $this->db->query($sql)->fetch(\PDO::FETCH_ASSOC);
        } catch (\Exception $e) {
            return [];
        }
    }

    public function getSellerRanking()
    {
        try {
            // Ranking based on confirmed quotations, total quotes and contacts
            $sql = "SELECT 
                        u.full_name as seller_name,
                        (SELECT COUNT(*) FROM quotations WHERE user_id = u.id) as total_quotes,
                        (SELECT COUNT(*) FROM quotations WHERE user_id = u.id AND is_confirmed = 1) as orders_closed,
                        (SELECT COUNT(*) FROM crm_interactions WHERE user_id = u.id) as interactions
                    FROM users u
                    WHERE u.role IN ('Vendedor', 'Admin', 'Sistemas')
                    HAVING total_quotes > 0 OR interactions > 0
                    ORDER BY orders_closed DESC, total_quotes DESC
                    LIMIT 10";
            return $this->db->query($sql)->fetchAll();
        } catch (\Exception $e) {
            return [];
        }
    }

    /**
     * Get specific Leads by status for the pipeline
     */
    public function getLeadsByStatus($status)
    {
        try {
            $sql = "SELECT * FROM crm_leads WHERE status = :status ORDER BY updated_at DESC";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([':status' => $status]);
            return $stmt->fetchAll();
        } catch (\Exception $e) {
            return [];
        }
    }

    /**
     * Save/Create a new lead
     */
    public function saveLead($data)
    {
        if (isset($data['id']) && $data['id'] > 0) {
            $sql = "UPDATE crm_leads SET 
                    name = :name, contact_person = :contact, email = :email, 
                    phone = :phone, status = :status, notes = :notes 
                    WHERE id = :id";
            $params = [
                ':name' => $data['name'],
                ':contact' => $data['contact_person'] ?? '',
                ':email' => $data['email'] ?? '',
                ':phone' => $data['phone'] ?? '',
                ':status' => $data['status'] ?? 'Nuevo',
                ':notes' => $data['notes'] ?? '',
                ':id' => $data['id']
            ];
        } else {
            $sql = "INSERT INTO crm_leads (name, contact_person, email, phone, status, notes) 
                    VALUES (:name, :contact, :email, :phone, :status, :notes)";
            $params = [
                ':name' => $data['name'],
                ':contact' => $data['contact_person'] ?? '',
                ':email' => $data['email'] ?? '',
                ':phone' => $data['phone'] ?? '',
                ':status' => $data['status'] ?? 'Nuevo',
                ':notes' => $data['notes'] ?? ''
            ];
        }

        $stmt = $this->db->prepare($sql);
        return $stmt->execute($params);
    }

    /**
     * Log a new interaction and optionally sync with Lead Pipeline
     */
    public function logInteraction($entityId, $type, $description, $userId, $entityType = 'entity')
    {
        // 1. If it's an 'entity' (Client), check if we should create/link a Lead
        if ($entityType === 'entity') {
            $stmt = $this->db->prepare("SELECT name, contact_person, email, phone FROM entities WHERE id = ?");
            $stmt->execute([$entityId]);
            $ent = $stmt->fetch();

            if ($ent) {
                // Check if lead already exists based on name
                $stmtLead = $this->db->prepare("SELECT id FROM crm_leads WHERE name = ? LIMIT 1");
                $stmtLead->execute([$ent['name']]);
                $leadId = $stmtLead->fetchColumn();

                if (!$leadId) {
                    // Create Lead
                    $this->saveLead([
                        'name' => $ent['name'],
                        'contact_person' => $ent['contact_person'],
                        'email' => $ent['email'],
                        'phone' => $ent['phone'],
                        'status' => ($type === 'Presupuesto' || $type === 'Envó­o Presupuesto') ? 'Presupuestado' : 'Contactado',
                        'notes' => 'Auto-generado desde interacció³n con Cliente'
                    ]);
                } else {
                    // Update existing lead status
                    $newStatus = ($type === 'Presupuesto' || $type === 'Envó­o Presupuesto') ? 'Presupuestado' : 'Contactado';
                    $this->db->prepare("UPDATE crm_leads SET status = ?, updated_at = NOW() WHERE id = ?")->execute([$newStatus, $leadId]);
                }

                // AUTO-ASSIGN SELLER: If entity has no seller_id, assign the current user
                $this->db->prepare("UPDATE entities SET seller_id = ? WHERE id = ? AND seller_id IS NULL AND (SELECT role FROM users WHERE id = ?) = 'Vendedor'")
                    ->execute([$userId, $entityId, $userId]);
            }
        } elseif ($entityType === 'lead') {
            // Update existing lead status
            $newStatus = ($type === 'Presupuesto' || $type === 'Envó­o Presupuesto') ? 'Presupuestado' : 'Contactado';
            $this->db->prepare("UPDATE crm_leads SET status = ?, updated_at = NOW() WHERE id = ? AND status IN ('Nuevo', 'Contactado')")->execute([$newStatus, $entityId]);
        }

        $sql = "INSERT INTO crm_interactions (entity_id, entity_type, user_id, type, description, interaction_date) 
                VALUES (:eid, :etype, :uid, :type, :desc, NOW())";
        $stmt = $this->db->prepare($sql);
        return $stmt->execute([
            ':eid' => $entityId,
            ':etype' => $entityType,
            ':uid' => $userId,
            ':type' => $type,
            ':desc' => $description
        ]);
    }

    /**
     * Get recent interactions
     */
    public function getRecentInteractions($limit = 10)
    {
        try {
            $sql = "SELECT i.*, 
                           i.type as interaction_type,
                           i.interaction_date as created_at,
                           CASE 
                            WHEN i.entity_type = 'entity' THEN (SELECT name FROM entities WHERE id = i.entity_id LIMIT 1)
                            WHEN i.entity_type = 'lead' THEN (SELECT name FROM crm_leads WHERE id = i.entity_id LIMIT 1)
                           END as client_name,
                           CASE 
                            WHEN i.entity_type = 'entity' THEN (SELECT name FROM entities WHERE id = i.entity_id LIMIT 1)
                            WHEN i.entity_type = 'lead' THEN (SELECT name FROM crm_leads WHERE id = i.entity_id LIMIT 1)
                           END as entity_name,
                           u.full_name as user_name 
                    FROM crm_interactions i 
                    LEFT JOIN users u ON i.user_id = u.id 
                    ORDER BY i.interaction_date DESC LIMIT :limit";
            $stmt = $this->db->prepare($sql);
            $stmt->bindValue(':limit', (int) $limit, \PDO::PARAM_INT);
            $stmt->execute();
            return $stmt->fetchAll();
        } catch (\Exception $e) {
            return [];
        }
    }

    /**
     * Move lead to next/prev stage
     */
    public function moveLead($id, $direction)
    {
        $statuses = ['Nuevo', 'Contactado', 'Presupuestado', 'Ganado', 'Perdido'];

        $stmt = $this->db->prepare("SELECT status FROM crm_leads WHERE id = ?");
        $stmt->execute([$id]);
        $current = $stmt->fetchColumn();

        if (!$current)
            return false;

        $idx = array_search($current, $statuses);
        if ($direction === 'next' && $idx < count($statuses) - 1)
            $idx++;
        elseif ($direction === 'prev' && $idx > 0)
            $idx--;
        else
            return true; // No movement possible but ok

        $stmt = $this->db->prepare("UPDATE crm_leads SET status = ?, updated_at = NOW() WHERE id = ?");
        return $stmt->execute([$statuses[$idx], $id]);
    }
    /**
     * Get Sales Funnel Stats (30 Days)
     */
    public function getFunnelStats()
    {
        try {
            // 1. Clicks (Interactions of type 'Consulta Web' or public logs)
            $clicks = $this->db->query("SELECT COUNT(*) FROM crm_interactions 
                                        WHERE type = 'Consulta Web' 
                                        AND interaction_date >= DATE_SUB(NOW(), INTERVAL 30 DAY)")->fetchColumn();

            // 2. Quoted (Quotations created)
            $quoted = $this->db->query("SELECT COUNT(*) FROM quotations 
                                        WHERE created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)")->fetchColumn();

            // 3. Sold (Confirmed Sales)
            // Check if is_confirmed exists, otherwise fallback to status
            $cols = $this->db->query("DESCRIBE quotations")->fetchAll(\PDO::FETCH_COLUMN);
            $soldSql = in_array('is_confirmed', $cols)
                ? "SELECT COUNT(*) FROM quotations WHERE is_confirmed = 1 AND created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)"
                : "SELECT COUNT(*) FROM quotations WHERE status = 'Aceptado' AND created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)";

            $sold = $this->db->query($soldSql)->fetchColumn();

            return [
                'clicks' => $clicks ?: 0,
                'quoted' => $quoted ?: 0,
                'sold' => $sold ?: 0
            ];
        } catch (\Exception $e) {
            return ['clicks' => 0, 'quoted' => 0, 'sold' => 0];
        }
    }
}


