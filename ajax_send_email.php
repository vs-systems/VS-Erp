<?php
/**
 * VS System ERP - AJAX Handler - Send Email
 */
header('Content-Type: application/json');

require_once __DIR__ . '/src/config/config.php';
require_once __DIR__ . '/src/lib/Database.php';
require_once __DIR__ . '/src/lib/Mailer.php';
require_once __DIR__ . '/src/modules/cotizador/Cotizador.php';
require_once __DIR__ . '/src/modules/crm/CRM.php';

use Vsys\Lib\Mailer;
use Vsys\Modules\Cotizador\Cotizador;
use Vsys\Modules\CRM\CRM;

$data = json_decode(file_get_contents('php://input'), true);

if (!isset($data['type']) || !isset($data['id'])) {
    echo json_encode(['success' => false, 'error' => 'Paró¡metros incompletos']);
    exit;
}

try {
    $mailer = new Mailer();
    $subject = "";
    $body = "";
    $to = "";

    if ($data['type'] === 'quotation') {
        $cot = new Cotizador();
        $q = $cot->getQuotation($data['id']);
        if (!$q)
            throw new Exception("Presupuesto no encontrado");

        $to = $q['client_email'];
        if (!$to)
            throw new Exception("El cliente no tiene un email configurado");

        $subject = "Presupuesto VS System - " . $q['quote_number'];
        $body = "
            <h2>Hola " . htmlspecialchars($q['client_name']) . ",</h2>
            <p>Adjuntamos la informació³n del presupuesto solicitado.</p>
            <p><strong>Nro de Presupuesto:</strong> " . $q['quote_number'] . "</p>
            <p><strong>Total:</strong> $" . number_format($q['total_usd'], 2) . " USD / $" . number_format($q['total_ars'], 2) . " ARS</p>
            <p>Puede ver el detalle completo en el siguiente enlace:</p>
            <p><a href='http://" . $_SERVER['HTTP_HOST'] . str_replace('public/ajax_send_email.php', '', $_SERVER['PHP_SELF']) . "public/imprimir_cotizacion.php?id=" . $q['id'] . "' target='_blank'>Ver Presupuesto Online (PDF)</a></p>
            <br>
            <p>Saludos,<br>El equipo de VS System</p>
        ";
    } elseif ($data['type'] === 'lead') {
        // Handle lead email if needed
        $db = Vsys\Lib\Database::getInstance();
        $stmt = $db->prepare("SELECT * FROM crm_leads WHERE id = ?");
        $stmt->execute([$data['id']]);
        $lead = $stmt->fetch();
        if (!$lead)
            throw new Exception("Lead no encontrado");

        $to = $lead['email'];
        if (!$to)
            throw new Exception("El lead no tiene un email configurado");

        $subject = "Contacto VS System";
        $body = "
            <h2>Hola " . htmlspecialchars($lead['name']) . ",</h2>
            <p>Nos ponemos en contacto con usted desde VS System por su reciente consulta.</p>
            <br>
            <p>Saludos,<br>El equipo de VS System</p>
        ";
    }

    $mailer->send($to, $subject, $body);
    echo json_encode(['success' => true]);

} catch (Exception $e) {
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}





