<?php
/**
 * VS System ERP - Price Analyzer Module
 */

namespace Vsys\Modules\Analizador;

use Vsys\Lib\Database;

class PriceAnalyzer
{
    private $db;

    public function __construct()
    {
        $this->db = Database::getInstance();
    }

    /**
     * Get products for comparison (Sample for analyzer)
     */
    public function getProductsForAnalysis($limit = 50)
    {
        $sql = "SELECT p.*, c.name as category_name 
                FROM products p 
                LEFT JOIN categories c ON p.category_id = c.id 
                ORDER BY p.updated_at DESC 
                LIMIT :limit";
        $stmt = $this->db->prepare($sql);
        $stmt->bindValue(':limit', (int) $limit, \PDO::PARAM_INT);
        $stmt->execute();
        $products = $stmt->fetchAll();

        // Attach supplier prices to each product
        foreach ($products as &$p) {
            $stmt = $this->db->prepare("SELECT sp.*, e.name as supplier_name 
                                       FROM supplier_prices sp 
                                       JOIN entities e ON sp.supplier_id = e.id 
                                       WHERE sp.product_id = ?");
            $stmt->execute([$p['id']]);
            $p['suppliers'] = $stmt->fetchAll();
        }

        return $products;
    }

    /**
     * Get Statistics for Charts (e.g., Brand distribution or price tiers)
     */
    public function getAnalyticsSummary()
    {
        // 1. Category Distribution
        $sqlCat = "SELECT c.name as label, COUNT(p.id) as value 
                   FROM products p 
                   JOIN categories c ON p.category_id = c.id 
                   GROUP BY c.id";
        $stmtCat = $this->db->query($sqlCat);
        $categories = $stmtCat->fetchAll();

        // 2. Average Price by Brand (Top 5)
        $sqlBrand = "SELECT brand as label, AVG(unit_price_usd) as value 
                     FROM products 
                     WHERE brand IS NOT NULL AND brand != '' 
                     GROUP BY brand 
                     ORDER BY value DESC 
                     LIMIT 5";
        $stmtBrand = $this->db->query($sqlBrand);
        $brands = $stmtBrand->fetchAll();

        return [
            'categories' => $categories,
            'brands' => $brands
        ];
    }
}


