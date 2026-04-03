<?php
namespace Vsys\Modules\Catalogo;

use Vsys\Lib\Database;
use Vsys\Modules\Config\PriceList;

class PublicCatalog
{
    private $db;
    private $priceListModule;

    public function __construct()
    {
        $this->db = Database::getInstance();
        $this->priceListModule = new PriceList();
    }

    /**
     * Get the current USD to ARS exchange rate from DB (or fallback).
     */
    public function getExchangeRate()
    {
        // Fetch latest rate from exchange_rates table
        $stmt = $this->db->prepare("SELECT rate FROM exchange_rates ORDER BY id DESC LIMIT 1");
        $stmt->execute();
        $rate = $stmt->fetchColumn();

        // Fallback if no rate found (e.g. 1000 to avoid crash, but should be sync'd)
        return $rate ? (float) $rate : 1200.00;
    }

    /**
     * Get all products with calculated Web Price in ARS.
     */
    public function getProductsForWeb()
    {
        $rate = $this->getExchangeRate();

        // Get Mostrador Margin
        $stmt = $this->db->prepare("SELECT margin_percent FROM price_lists WHERE name = 'Mostrador'");
        $stmt->execute();
        $margin = $stmt->fetchColumn();
        $margin = ($margin !== false) ? (float) $margin : 55.0; // Fallback Mostrador

        // Get Products
        $stmt = $this->db->prepare("SELECT * FROM products WHERE stock_current > 0 OR stock_current IS NULL ORDER BY brand, description"); // Maybe filter enabled?
        // Assuming all products in DB are active. Added basic stock check optional.
        // Actually, user didn't specify stock check. Let's just return all.
        $stmt = $this->db->prepare("SELECT * FROM products ORDER BY brand, description");
        $stmt->execute();
        $products = $stmt->fetchAll();

        $webProducts = [];
        foreach ($products as $p) {
            $cost = (float) $p['unit_cost_usd'];
            $iva = (float) $p['iva_rate'];

            // Calc Price USD: Cost + Margin + IVA
            $priceUsd = $cost * (1 + ($margin / 100)); // Base + Markup
            $priceUsdWithIva = $priceUsd * (1 + ($iva / 100)); // + IVA

            // Convert to ARS
            $priceArs = $priceUsdWithIva * $rate;

            // Only add if price is valid
            if ($priceArs > 0) {
                // Rounding
                $p['price_final_usd'] = round($priceUsdWithIva, 2);
                $p['price_final_ars'] = round($priceArs, 0);
                $p['price_final_formatted'] = number_format($p['price_final_ars'], 0, ',', '.');

                // Clean image path
                $img = $p['image_url'] ?? '';
                $img = str_replace(['Z:\\Vsys_ERP\\', 'Z:/Vsys_ERP/'], '', $img);
                $p['image_url'] = !empty($img) ? $img : 'https://placehold.co/300x300?text=No+Image';

                $webProducts[] = $p;
            }
        }

        return [
            'rate' => $rate,
            'products' => $webProducts
        ];
    }

    /**
     * Get products with price based on client profile (Gremio or Web).
     * Returns prices in ARS applying IVA and exchange rate.
     */
    public function getProductsForProfile(string $profile)
    {
        $rate = $this->getExchangeRate();

        // Determine which price column to use
        $priceColumn = ($profile === 'Gremio') ? 'price_gremio' : 'price_web';

        // Get Products
        $stmt = $this->db->prepare("SELECT *, $priceColumn FROM products ORDER BY brand, description");
        $stmt->execute();
        $products = $stmt->fetchAll();

        $profileProducts = [];
        foreach ($products as $p) {
            $basePriceUsd = (float) $p[$priceColumn];
            $iva = (float) $p['iva_rate'];

            // If base price is null or zero, fallback to unit_price_usd
            if ($basePriceUsd <= 0) {
                $basePriceUsd = (float) $p['unit_price_usd'];
            }

            // Add IVA
            $priceUsdWithIva = $basePriceUsd * (1 + ($iva / 100));

            // Convert to ARS
            $priceArs = $priceUsdWithIva * $rate;

            if ($priceArs > 0) {
                $p['price_final_ars'] = round($priceArs, 0);
                $p['price_final_formatted'] = number_format($p['price_final_ars'], 0, ',', '.');

                // Clean image path
                $img = $p['image_url'] ?? '';
                $img = str_replace(['Z:\\Vsys_ERP\\', 'Z:/Vsys_ERP/'], '', $img);
                $p['image_url'] = !empty($img) ? $img : 'https://placehold.co/300x300?text=No+Image';

                $profileProducts[] = $p;
            }
        }

        return [
            'rate' => $rate,
            'products' => $profileProducts
        ];
    }
}
