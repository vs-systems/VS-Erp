<?php
namespace Vsys\Modules\Config;

class PriceList
{
    private $config;
    private $configFile;

    public function __construct()
    {
        $this->configFile = dirname(__DIR__, 2) . '/../config_prices.json';
        $this->loadConfig();
    }

    private function loadConfig()
    {
        if (file_exists($this->configFile)) {
            $this->config = json_decode(file_get_contents($this->configFile), true);
        } else {
            // Default constants if file doesn't exist
            $this->config = [
                'gremio' => 25.0,
                'web' => 40.0,
                'mostrador' => 55.0
            ];
        }
    }

    /**
     * Calculate price based on list name
     * 
     * @param float $costUsd Unit cost in USD
     * @param float $ivaRate IVA rate (e.g., 10.5 or 21)
     * @param string $listName 'Gremio', 'Web', or 'Mostrador'
     * @param float $dollarRate Current exchange rate
     * @param bool $includeIva Whether to include IVA in the final price
     * @return float Price in ARS
     */
    public function getPriceByListName($costUsd, $ivaRate, $listName, $dollarRate, $includeIva = true)
    {
        $key = strtolower($listName);
        $margin = $this->config[$key] ?? 0; // Default to 0 margin if list not found

        // Base price calculation: Cost + Margin
        // Note: Margin is usually applied to Cost. 
        // Price = Cost * (1 + Margin/100)

        $priceUsd = $costUsd * (1 + ($margin / 100));

        if ($includeIva) {
            $priceUsd = $priceUsd * (1 + ($ivaRate / 100));
        }

        return $priceUsd * $dollarRate;
    }

    public function getMargins()
    {
        return $this->config;
    }
}
?>