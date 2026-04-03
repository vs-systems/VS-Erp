<?php
/**
 * VS System ERP - FTP Migration Tool
 */

$ftp_server = "cpanel175.cprapid.online";
$ftp_user = "gozziar";
$ftp_pass = 'Blanca1938@!!';

echo "Conectando a $ftp_server...<br>";
$conn_id = ftp_connect($ftp_server) or die("No se pudo conectar a $ftp_server");

if (@ftp_login($conn_id, $ftp_user, $ftp_pass)) {
    echo "Conectado como $ftp_user<br>";

    // Switch to passive mode
    ftp_pasv($conn_id, true);

    $files = [
        'z:\vsys_erp_package.zip' => 'vsys_erp_package.zip',
        'z:\Vsys_ERP\public\vsys_migration_dump_20260116_214027.sql' => 'vsys_dump.sql'
    ];

    foreach ($files as $local => $remote) {
        echo "Subiendo $local a $remote... ";
        if (ftp_put($conn_id, "public_html/" . $remote, $local, FTP_BINARY)) {
            echo "âœ… OK<br>";
        } else {
            echo "âŒ FALLó“<br>";
        }
    }
} else {
    echo "âŒ No se pudo autenticar FTP";
}

ftp_close($conn_id);





