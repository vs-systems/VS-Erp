<?php
/**
 * AJAX Handler - Get Lead Details (Legajo)
 */
header('Content-Type: application/json');
require_once __DIR__ . '/src/config/config.php';
require_once __DIR__ . '/src/lib/Database.php';

try {
    $db = \Vsys\Lib\Database::getInstance();
    $id = $_GET['id'] ?? null;

    if (!$id) {
        throw new Exception("ID de Lead no especificado");
    }

    // 1. Get Lead Basic Info
    $stmtLead = $db->prepare("SELECT * FROM crm_leads WHERE id = ?");
    $stmtLead->execute([$id]);
    $lead = $stmtLead->fetch();

    if (!$lead) {
        throw new Exception("Lead no encontrado");
    }

    // 2. Get Interactions (Conversation History)
    $stmtInt = $db->prepare("SELECT i.*, u.full_name as user_name 
                             FROM crm_interactions i 
                             LEFT JOIN users u ON i.user_id = u.id 
                             WHERE i.entity_id = ? AND i.entity_type = 'lead' 
                             ORDER BY i.interaction_date DESC");
    $stmtInt->execute([$id]);
    $interactions = $stmtInt->fetchAll();

    // 3. Get Linked Quotations (by exact Name match for now, or phone if available)
    // We search quotations where client name matches lead name
    $stmtQuotes = $db->prepare("SELECT id, quote_number, total_usd, status, is_confirmed, created_at 
                                FROM quotations 
                                WHERE client_id IN (SELECT id FROM entities WHERE name = ?) 
                                OR client_id IN (SELECT id FROM entities WHERE phone = ? AND phone != '')
                                ORDER BY created_at DESC");
    $stmtQuotes->execute([$lead['name'], $lead['phone']]);
    $quotations = $stmtQuotes->fetchAll();

    // 4. Get Competitor Analyses
    $stmtComp = $db->prepare("SELECT id, analysis_number, created_at 
                              FROM competitor_analysis 
                              WHERE client_id IN (SELECT id FROM entities WHERE name = ?)
                              OR quote_id IN (SELECT id FROM quotations WHERE client_id IN (SELECT id FROM entities WHERE name = ?))
                              ORDER BY created_at DESC");
    $stmtComp->execute([$lead['name'], $lead['name']]);
    $compAnalyses = $stmtComp->fetchAll();

    echo json_encode([
        'success' => true,
        'lead' => $lead,
        'interactions' => $interactions,
        'quotations' => $quotations,
        'competitor_analyses' => $compAnalyses
    ]);

} catch (Exception $e) {
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
