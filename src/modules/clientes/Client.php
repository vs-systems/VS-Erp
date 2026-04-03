<?php
/**
 * VS System ERP - Client Module
 */

namespace Vsys\Modules\Clientes;

use Vsys\Lib\Database;

class Client
{
    private $db;

    public function __construct()
    {
        $this->db = Database::getInstance();
    }

    /**
     * Search entities by name, fantasy_name, tax_id or document
     */
    public function searchClients($query, $type = 'client')
    {
        $typeFilter = ($type === 'all') ? "1=1" : "type = :type";
        $sql = "SELECT * FROM entities WHERE 
                $typeFilter AND (
                    LOWER(name) LIKE :q1 OR 
                    LOWER(fantasy_name) LIKE :q2 OR
                    LOWER(tax_id) LIKE :q3 OR 
                    LOWER(document_number) LIKE :q4 OR
                    LOWER(email) LIKE :q5 OR
                    LOWER(contact_person) LIKE :q6 OR
                    LOWER(tax_category) LIKE :q7
                ) 
                ORDER BY name ASC 
                LIMIT 20";

        $searchTerm = "%" . mb_strtolower($query, 'UTF-8') . "%";
        $params = [
            'q1' => $searchTerm,
            'q2' => $searchTerm,
            'q3' => $searchTerm,
            'q4' => $searchTerm,
            'q5' => $searchTerm,
            'q6' => $searchTerm,
            'q7' => $searchTerm
        ];

        if ($type !== 'all') {
            $params['type'] = $type;
        }

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    /**
     * Add or Update entity (Upsert by tax_id or name/fantasy_name)
     */
    public function saveClient($data)
    {
        // Sanitize lat/lng to be numeric or null
        $data['lat'] = (!empty($data['lat']) && is_numeric($data['lat'])) ? (float) $data['lat'] : null;
        $data['lng'] = (!empty($data['lng']) && is_numeric($data['lng'])) ? (float) $data['lng'] : null;

        $sql = "INSERT INTO entities (
                    id, type, tax_id, document_number, name, fantasy_name, 
                    contact_person, email, phone, mobile, address, 
                    delivery_address, default_voucher_type, tax_category,
                    is_enabled, is_retention_agent, payment_condition, preferred_payment_method,
                    seller_id, client_profile, is_verified, city, lat, lng, transport, is_transport
                ) 
                VALUES (
                    :id, :type, :tax_id, :document_number, :name, :fantasy_name, 
                    :contact, :email, :phone, :mobile, :address, 
                    :delivery_address, :default_voucher, :tax_category,
                    :is_enabled, :retention, :payment_condition, :payment_method,
                    :seller_id, :client_profile, :is_verified, :city, :lat, :lng, :transport, :is_transport
                )
                ON DUPLICATE KEY UPDATE 
                document_number = VALUES(document_number),
                name = VALUES(name),
                fantasy_name = VALUES(fantasy_name),
                contact_person = VALUES(contact_person),
                email = VALUES(email),
                phone = VALUES(phone),
                mobile = VALUES(mobile),
                address = VALUES(address),
                delivery_address = VALUES(delivery_address),
                default_voucher_type = VALUES(default_voucher_type),
                tax_category = VALUES(tax_category),
                is_enabled = VALUES(is_enabled),
                is_retention_agent = VALUES(is_retention_agent),
                payment_condition = VALUES(payment_condition),
                preferred_payment_method = VALUES(preferred_payment_method),
                seller_id = VALUES(seller_id),
                client_profile = VALUES(client_profile),
                is_verified = VALUES(is_verified),
                city = VALUES(city),
                lat = VALUES(lat),
                lng = VALUES(lng),
                transport = VALUES(transport),
                is_transport = VALUES(is_transport)";
        $stmt = $this->db->prepare($sql);
        return $stmt->execute($data);
    }
}


