<?php
/**
 * VS System ERP - User & Session Management
 */

namespace Vsys\Lib;

use Vsys\Lib\Database;
use PDO;

class User
{
    private $db;
    private $user_id;
    private $username;
    private $role;
    private $entity_id;
    private $full_name;

    public function __construct()
    {
        $this->db = Database::getInstance();
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        $this->loadFromSession();
    }

    private function loadFromSession()
    {
        if (isset($_SESSION['user_id'])) {
            $this->user_id = $_SESSION['user_id'];
            $this->username = $_SESSION['username'];
            $this->role = $_SESSION['role'];
            $this->full_name = $_SESSION['full_name'] ?? $_SESSION['username'];
            $this->entity_id = $_SESSION['entity_id'] ?? null;
        }
    }

    /**
     * Authenticate and login user
     */
    public function login($username, $password)
    {
        // Using 'status' and 'password_hash' to match actual schema
        $stmt = $this->db->prepare("SELECT * FROM users WHERE username = :u AND status = 'Active'");
        $stmt->execute([':u' => $username]);
        $user = $stmt->fetch();

        if ($user && password_verify($password, $user['password_hash'])) {
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['username'] = $user['username'];
            $_SESSION['full_name'] = $user['full_name'] ?? $user['username'];
            $_SESSION['role'] = $user['role'];
            $_SESSION['entity_id'] = $user['entity_id'] ?? null;

            $this->loadFromSession();

            // Log login time
            $this->db->prepare("UPDATE users SET last_login = NOW() WHERE id = ?")->execute([$user['id']]);
            return true;
        }
        return false;
    }

    public function logout()
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        session_destroy();
        $_SESSION = [];
    }

    public function isLoggedIn()
    {
        return isset($this->user_id);
    }

    public function getRole()
    {
        return $this->role;
    }

    public function getUsername()
    {
        return $this->username;
    }

    public function getEntityId()
    {
        return $this->entity_id;
    }

    /**
     * Check if user has permission
     */
    public function hasRole($roles)
    {
        if (!is_array($roles)) {
            $roles = [$roles];
        }
        $userRole = strtolower($this->role);
        $roles = array_map('strtolower', $roles);
        return in_array($userRole, $roles);
    }

    /**
     * Support for legacy/other modules
     */
    public function authenticate($username, $password)
    {
        $stmt = $this->db->prepare("SELECT * FROM users WHERE username = ? AND status = 'Active'");
        $stmt->execute([$username]);
        $user = $stmt->fetch();

        if ($user && password_verify($password, $user['password_hash'])) {
            return $user;
        }
        return false;
    }

    public function getUserById($id)
    {
        $stmt = $this->db->prepare("SELECT * FROM users WHERE id = ?");
        $stmt->execute([$id]);
        return $stmt->fetch();
    }

    public function getAllUsers()
    {
        $stmt = $this->db->prepare("SELECT * FROM users ORDER BY username ASC");
        $stmt->execute();
        return $stmt->fetchAll();
    }

    public function createUser($data)
    {
        $hash = password_hash($data['password'], PASSWORD_DEFAULT);
        $sql = "INSERT INTO users (username, password_hash, role, entity_id, status) VALUES (?, ?, ?, ?, ?)";
        $stmt = $this->db->prepare($sql);
        return $stmt->execute([
            $data['username'],
            $hash,
            $data['role'],
            $data['entity_id'] ?? null,
            $data['status'] ?? 'Active'
        ]);
    }

    public function updateUser($id, $data)
    {
        $params = [$data['role'], $data['status']];
        $sql = "UPDATE users SET role = ?, status = ?";

        if (!empty($data['password'])) {
            $sql .= ", password_hash = ?";
            $params[] = password_hash($data['password'], PASSWORD_DEFAULT);
        }

        $sql .= " WHERE id = ?";
        $params[] = $id;

        $stmt = $this->db->prepare($sql);
        return $stmt->execute($params);
    }
}
