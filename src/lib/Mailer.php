<?php
/**
 * VS System ERP - Resend API Mailer
 * Reemplaza la vieja conexión por sockets SMTP con la API oficial de Resend (mucho más rápida y estable).
 */

namespace Vsys\Lib;

class Mailer
{
    private $apiKey;
    private $from;

    public function __construct()
    {
        $this->apiKey = defined('RESEND_API_KEY') ? RESEND_API_KEY : '';
        $this->from   = defined('MAIL_FROM') ? MAIL_FROM : 'no-reply@vecinoseguro.com.ar';
    }

    public function send($to, $subject, $body, $isHtml = true)
    {
        if (empty($this->apiKey)) {
            throw new \Exception("RESEND_API_KEY no está configurada en config.php.");
        }

        $url = 'https://api.resend.com/emails';
        
        $data = [
            'from'    => "Vecino Seguro <{$this->from}>",
            'to'      => [$to],
            'subject' => $subject,
        ];

        if ($isHtml) {
            $data['html'] = $body;
        } else {
            $data['text'] = $body;
        }

        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Authorization: Bearer ' . $this->apiKey,
            'Content-Type: application/json'
        ]);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error    = curl_error($ch);
        curl_close($ch);

        if ($httpCode >= 200 && $httpCode < 300) {
            return true;
        }

        // Extraer mensaje de error de Resend si existe
        $resendError = json_decode($response, true);
        $errorMsg = isset($resendError['message']) ? $resendError['message'] : $response;

        throw new \Exception("Error Resend: HTTP $httpCode " . ($error ?: $errorMsg));
    }
}
