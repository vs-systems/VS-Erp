<?php
/**
 * VS System ERP - AJAX Payment Upload
 */
session_start();
require_once __DIR__ . '/src/config/config.php';
require_once __DIR__ . '/src/lib/Database.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $quoteNumber = $_POST['quote_number'] ?? null;
    $file = $_FILES['payment_file'] ?? null;

    if (!$quoteNumber) {
        echo json_encode(['success' => false, 'error' => 'Número de presupuesto no proporcionado.']);
        exit;
    }

    if ($file && $file['error'] === UPLOAD_ERR_OK) {
        $uploadDir = __DIR__ . '/uploads/payments/';
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0777, true);
        }

        $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        $allowed = ['jpg', 'jpeg', 'png', 'pdf'];

        if (!in_array($ext, $allowed)) {
            echo json_encode(['success' => false, 'error' => 'Formato no permitido (Solo JPG, PNG, PDF).']);
            exit;
        }

        $cleanNumber = str_replace(['/', '\\', '*', ':', '?', '"', '<', '>', '|'], '_', $quoteNumber);
        $fileName = 'PAY_' . $cleanNumber . '_' . time() . '.' . $ext;
        $dest = $uploadDir . $fileName;

        if (move_uploaded_file($file['tmp_name'], $dest)) {
            $method = $_POST['payment_method'] ?? 'No especificado';
            $stmt = $db->prepare("INSERT INTO operation_documents (entity_id, entity_type, doc_type, file_path, notes) 
                                 VALUES (?, 'quotation', 'Pago', ?, ?)");
            $stmt->execute([
                $quoteNumber,
                'uploads/payments/' . $fileName,
                "Comprobante de pago ($method) subido desde Ventas/Facturación."
            ]);

            echo json_encode(['success' => true]);
            exit;
        } else {
            echo json_encode(['success' => false, 'error' => 'Error al mover el archivo al destino.']);
            exit;
        }
    } else {
        echo json_encode(['success' => false, 'error' => 'Archivo no recibido o con error: ' . ($file['error'] ?? 'desconocido')]);
        exit;
    }
}

echo json_encode(['success' => false, 'error' => 'Método no permitido.']);
