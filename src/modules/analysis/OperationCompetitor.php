<?php
namespace Vsys\Modules\Analysis;

require_once __DIR__ . '/../../lib/Database.php';

use Vsys\Lib\Database;

class OperationCompetitor
{
    private $db;

    public function __construct()
    {
        $this->db = Database::getInstance();
        $this->checkMigration();
    }

    private function checkMigration()
    {
        try {
            $this->db->exec("CREATE TABLE IF NOT EXISTS competitor_analysis (
                id INT AUTO_INCREMENT PRIMARY KEY,
                quote_id INT NOT NULL,
                analysis_number VARCHAR(50) NOT NULL UNIQUE,
                client_id INT NOT NULL,
                exchange_rate DECIMAL(10,2) NOT NULL,
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                FOREIGN KEY (quote_id) REFERENCES quotations(id) ON DELETE CASCADE
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");

            $this->db->exec("CREATE TABLE IF NOT EXISTS competitor_analysis_items (
                id INT AUTO_INCREMENT PRIMARY KEY,
                analysis_id INT NOT NULL,
                product_id INT DEFAULT NULL,
                sku VARCHAR(100),
                description TEXT,
                qty INT DEFAULT 1,
                vs_unit_usd DECIMAL(15,2) DEFAULT 0,
                vs_unit_ars DECIMAL(15,2) DEFAULT 0,
                comp_unit_ars DECIMAL(15,2) DEFAULT 0,
                FOREIGN KEY (analysis_id) REFERENCES competitor_analysis(id) ON DELETE CASCADE
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");
        } catch (\Exception $e) {
            error_log("Migration Error: " . $e->getMessage());
        }
    }

    public function generateAnalysisNumber()
    {
        $prefix = "VSA-" . date('Y-m') . "-";
        $stmt = $this->db->prepare("SELECT analysis_number FROM competitor_analysis WHERE analysis_number LIKE ? ORDER BY id DESC LIMIT 1");
        $stmt->execute([$prefix . '%']);
        $last = $stmt->fetchColumn();

        if ($last) {
            $parts = explode('-', $last);
            $seq = intval(end($parts)) + 1;
        } else {
            $seq = 1;
        }

        return $prefix . str_pad($seq, 4, '0', STR_PAD_LEFT);
    }

    public function getAnalysis($id)
    {
        $stmt = $this->db->prepare("SELECT a.*, e.name as client_name, q.quote_number, e.tax_id, e.address, e.city, e.phone, e.email
                                    FROM competitor_analysis a
                                    JOIN entities e ON a.client_id = e.id
                                    JOIN quotations q ON a.quote_id = q.id
                                    WHERE a.id = ?");
        $stmt->execute([$id]);
        $header = $stmt->fetch();

        if (!$header)
            return null;

        $stmtItems = $this->db->prepare("SELECT * FROM competitor_analysis_items WHERE analysis_id = ?");
        $stmtItems->execute([$id]);
        $header['items'] = $stmtItems->fetchAll();

        return $header;
    }

    public function getAnalyticsByQuote($quoteId)
    {
        $stmt = $this->db->prepare("SELECT id FROM competitor_analysis WHERE quote_id = ?");
        $stmt->execute([$quoteId]);
        return $stmt->fetchAll(\PDO::FETCH_COLUMN);
    }

    public function saveAnalysis($data)
    {
        try {
            $this->db->beginTransaction();

            if (isset($data['id']) && $data['id']) {
                $stmt = $this->db->prepare("UPDATE competitor_analysis SET exchange_rate = ? WHERE id = ?");
                $stmt->execute([$data['exchange_rate'], $data['id']]);
                $analysisId = $data['id'];

                $this->db->prepare("DELETE FROM competitor_analysis_items WHERE analysis_id = ?")->execute([$analysisId]);
            } else {
                $analysisNumber = $this->generateAnalysisNumber();

                // Get Quote Version if needed
                $stmtQ = $this->db->prepare("SELECT quote_number FROM quotations WHERE id = ?");
                $stmtQ->execute([$data['quote_id']]);
                $quoteNum = $stmtQ->fetchColumn();

                // Extract version if exists (e.g. _01)
                $versionSuffix = "";
                if (strpos($quoteNum, '_') !== false) {
                    $versionSuffix = "_" . explode('_', $quoteNum)[1];
                }

                $analysisNumber .= $versionSuffix;

                $stmt = $this->db->prepare("INSERT INTO competitor_analysis (quote_id, analysis_number, client_id, exchange_rate) VALUES (?, ?, ?, ?)");
                $stmt->execute([$data['quote_id'], $analysisNumber, $data['client_id'], $data['exchange_rate']]);
                $analysisId = $this->db->lastInsertId();
            }

            $stmtItem = $this->db->prepare("INSERT INTO competitor_analysis_items (analysis_id, product_id, sku, description, qty, vs_unit_usd, vs_unit_ars, comp_unit_ars) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
            foreach ($data['items'] as $item) {
                $stmtItem->execute([
                    $analysisId,
                    $item['product_id'] ?? null,
                    $item['sku'],
                    $item['description'],
                    $item['qty'],
                    $item['vs_unit_usd'],
                    $item['vs_unit_ars'],
                    $item['comp_unit_ars']
                ]);
            }

            $this->db->commit();
            return $analysisId;
        } catch (\Exception $e) {
            $this->db->rollBack();
            error_log("Save Analysis Error: " . $e->getMessage());
            return false;
        }
    }
}
