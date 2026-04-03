<?php

namespace Vsys\Lib;

class BNAClient
{
    private $url = 'https://www.bna.com.ar/Personas';

    public function getCurrentRate()
    {
        try {
            $opts = [
                "http" => [
                    "method" => "GET",
                    "header" => "User-Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/91.0.4472.124 Safari/537.36\r\n"
                ]
            ];
            $context = stream_context_create($opts);
            $html = @file_get_contents($this->url, false, $context);

            if ($html === FALSE) {
                return null;
            }

            // The structure is roughly:
            // <tr>
            //     <td class="tit">Dolar U.S.A</td>
            //     <td>CompraValue</td>
            //     <td>VentaValue</td>
            // </tr>
            
            // Regex to find the Dolar U.S.A row and capture the Venta value (second <td> after the title)
            // We look for "Dolar U.S.A", then skip the first <td> (Compra) and capture the second <td> (Venta)
            if (preg_match('/<td[^>]*>Dolar U\.S\.A<\/td>\s*<td[^>]*>([\d,.]+)<\/td>\s*<td[^>]*>([\d,.]+)<\/td>/i', $html, $matches)) {
                $venta = str_replace(',', '.', $matches[2]);
                return (float)$venta;
            }

            return null;
        } catch (\Exception $e) {
            error_log("BNA API Error: " . $e->getMessage());
            return null;
        }
    }
}
