<?php
namespace Vsys\Modules\Logistica;

require_once dirname(__DIR__, 2) . '/lib/Database.php';

use Vsys\Lib\Database;

class Logistics
{
    private $db;

    public function __construct()
    {
        $this->db = Database::getInstance();

        // Auto-migration for is_transport field
        try {
            $this->db->exec("ALTER TABLE entities ADD COLUMN IF NOT EXISTS is_transport TINYINT(1) DEFAULT 0 AFTER is_retention_agent");
        } catch (\Exception $e) {
            // Ignore if already exists
        }
    }

    /**
     * Get orders ready for preparation or in logistics process
     */
    public function getOrdersForPreparation()
    {
        // Join with logistics_process to get current phase
        $sql = "SELECT q.*, e.name as client_name, lp.current_phase 
                FROM quotations q
                LEFT JOIN entities e ON q.client_id = e.id
                LEFT JOIN logistics_process lp ON q.quote_number = lp.quote_number
                WHERE q.payment_status = 'Pagado' OR q.authorized_dispatch = 1 OR lp.id IS NOT NULL
                ORDER BY q.created_at DESC";
        $stmt = $this->db->prepare($sql);
        $stmt->execute();
        return $stmt->fetchAll();
    }

    /**
     * Update order phase
     */
    public function updateOrderPhase($quoteNumber, $newPhase)
    {
        try {
            $quoteNumber = trim($quoteNumber);
            error_log("Logistics: Attempting update for [$quoteNumber] to phase $newPhase");

            // 0. Try to find company_id from quotation first
            $stmtQ = $this->db->prepare("SELECT company_id FROM quotations WHERE quote_number = ? LIMIT 1");
            $stmtQ->execute([$quoteNumber]);
            $qInfo = $stmtQ->fetch();
            $companyId = $qInfo ? $qInfo['company_id'] : null;

            // 1. Try to update existing record
            $updateSql = "UPDATE logistics_process SET current_phase = ?, updated_at = NOW()";
            $params = [$newPhase];
            if ($companyId) {
                $updateSql .= ", company_id = ?";
                $params[] = $companyId;
            }
            $updateSql .= " WHERE quote_number = ?";
            $params[] = $quoteNumber;

            $stmt = $this->db->prepare($updateSql);
            $stmt->execute($params);

            $rowCount = $stmt->rowCount();
            error_log("Logistics: UPDATE rowCount = $rowCount");

            if ($rowCount === 0) {
                // 2. If no record was updated, check if it exists or insert new
                $stmtCheck = $this->db->prepare("SELECT id, current_phase FROM logistics_process WHERE quote_number = ?");
                $stmtCheck->execute([$quoteNumber]);
                $existing = $stmtCheck->fetch();

                if (!$existing) {
                    error_log("Logistics: No record found for $quoteNumber, performing INSERT");
                    $stmtIns = $this->db->prepare("INSERT INTO logistics_process (quote_number, current_phase, updated_at, company_id) VALUES (?, ?, NOW(), ?)");
                    $res = $stmtIns->execute([$quoteNumber, $newPhase, $companyId]);
                    error_log("Logistics: INSERT result = " . ($res ? "Success" : "Failure"));
                    return $res;
                } else {
                    error_log("Logistics: Record already exists for $quoteNumber with phase " . ($existing['current_phase'] ?? 'NULL') . ". No rows were updated (possibly same phase).");
                }
            }
            return true;
        } catch (\Exception $e) {
            error_log("Logistics Update Error: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Log freight cost analysis data
     */
    public function logFreightCost($data)
    {
        $sql = "INSERT INTO logistics_freight_costs 
                (quote_number, dispatch_date, client_id, packages_qty, freight_cost, transport_id) 
                VALUES (?, ?, ?, ?, ?, ?)";
        $stmt = $this->db->prepare($sql);
        return $stmt->execute([
            $data['quote_number'],
            $data['dispatch_date'] ?? date('Y-m-d'),
            $data['client_id'],
            $data['packages_qty'],
            $data['freight_cost'],
            $data['transport_id']
        ]);
    }

    /**
     * Get master list of transport companies with new fields
     */
    public function getTransports($onlyActive = true)
    {
        $activeFilter = $onlyActive ? " WHERE is_active = TRUE" : "";
        $sql = "SELECT id, name, contact_person, phone, email, address, cuit, 'legacy' as source 
                FROM transports $activeFilter
                UNION
                SELECT id, name, contact_person as contact_person, phone, email, address, tax_id as cuit, 'unified' as source
                FROM entities 
                WHERE is_transport = 1" . ($onlyActive ? " AND is_enabled = 1" : "") . "
                ORDER BY name";
        return $this->db->query($sql)->fetchAll();
    }

    /**
     * Save/Update Transport (including new fields)
     */
    public function saveTransport($data)
    {
        if (isset($data['id']) && $data['id']) {
            $stmt = $this->db->prepare("UPDATE transports SET 
                name = ?, contact_person = ?, phone = ?, email = ?, 
                address = ?, cuit = ?, can_pickup = ?, is_active = ? 
                WHERE id = ?");
            return $stmt->execute([
                $data['name'],
                $data['contact_person'],
                $data['phone'],
                $data['email'],
                $data['address'] ?? '',
                $data['cuit'] ?? '',
                $data['can_pickup'] ?? 0,
                $data['is_active'],
                $data['id']
            ]);
        } else {
            $stmt = $this->db->prepare("INSERT INTO transports 
                (name, contact_person, phone, email, address, cuit, can_pickup) 
                VALUES (?, ?, ?, ?, ?, ?, ?)");
            return $stmt->execute([
                $data['name'],
                $data['contact_person'],
                $data['phone'],
                $data['email'],
                $data['address'] ?? '',
                $data['cuit'] ?? '',
                $data['can_pickup'] ?? 0
            ]);
        }
    }

    /**
     * Create Remito (Dispatch Note)
     */
    public function createRemito($quoteNumber, $transportId)
    {
        $remitoNum = 'REM-' . strtoupper(substr(uniqid(), -6));
        $stmt = $this->db->prepare("INSERT INTO logistics_remitos (quote_number, transport_id, remito_number, status) VALUES (?, ?, ?, 'Pending')");
        if ($stmt->execute([$quoteNumber, $transportId, $remitoNum])) {
            // Also advance phase to 'En su transporte'
            $this->updateOrderPhase($quoteNumber, 'En su transporte');
            return $remitoNum;
        }
        return false;
    }

    /**
     * Get Shipping Stats for Dashboard (current month)
     */
    public function getShippingStats()
    {
        $stats = [
            'pending' => 0,
            'prepared' => 0,
            'dispatched' => 0
        ];

        try {
            // Pending: En reserva or En preparación
            $res = $this->db->query("SELECT COUNT(*) FROM logistics_process WHERE current_phase IN ('En reserva', 'En preparación') AND MONTH(updated_at) = MONTH(CURRENT_DATE)")->fetchColumn();
            $stats['pending'] = $res ?: 0;

            // Prepared: Disponible
            $res = $this->db->query("SELECT COUNT(*) FROM logistics_process WHERE current_phase = 'Disponible' AND MONTH(updated_at) = MONTH(CURRENT_DATE)")->fetchColumn();
            $stats['prepared'] = $res ?: 0;

            // Dispatched: En su transporte or Entregado
            $res = $this->db->query("SELECT COUNT(*) FROM logistics_process WHERE current_phase IN ('En su transporte', 'Entregado') AND MONTH(updated_at) = MONTH(CURRENT_DATE)")->fetchColumn();
            $stats['dispatched'] = $res ?: 0;

            return $stats;
        } catch (\Exception $e) {
            return $stats;
        }
    }

    public function attachDocument($entityId, $entityType, $docType, $filePath, $notes = '')
    {
        $stmt = $this->db->prepare("INSERT INTO operation_documents (entity_id, entity_type, doc_type, file_path, notes) VALUES (?, ?, ?, ?, ?)");
        return $stmt->execute([$entityId, $entityType, $docType, $filePath, $notes]);
    }

    public function getDocuments($entityId, $entityType)
    {
        $stmt = $this->db->prepare("SELECT * FROM operation_documents WHERE entity_id = ? AND entity_type = ? ORDER BY uploaded_at DESC");
        $stmt->execute([$entityId, $entityType]);
        return $stmt->fetchAll();
    }
}


