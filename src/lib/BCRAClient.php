<?php
/**
 * VS System ERP - Currency API Client
 */

namespace Vsys\Lib;

class BCRAClient
{
    private $token;
    private $dolarApiUrl = 'https://dolarapi.com/v1/dolares/oficial'; // BNA Official
    private $blueApiUrl = 'https://dolarapi.com/v1/dolares/blue'; // For reference (1480 case)

    public function __construct($token = null)
    {
        $this->token = $token;
    }

    public function getCurrentRate($type = 'oficial')
    {
        $url = ($type === 'blue') ? $this->blueApiUrl : $this->dolarApiUrl;

        try {
            $response = file_get_contents($url);
            if ($response === FALSE) {
                return null;
            }

            $data = json_decode($response, true);
            return isset($data['venta']) ? $data['venta'] : null;
        } catch (\Exception $e) {
            error_log("Currency API Error: " . $e->getMessage());
            return null;
        }
    }
}
?>

