<?php
namespace CMS\Models;

use CMS\Core\Database;

class User {
    private $db;

    public function __construct() {
        $this->db = Database::getInstance();
    }

    public function findByEmail($email) {
        // Secure prepared statement
        $stmt = $this->db->query("SELECT * FROM pa_users WHERE email = :email LIMIT 1", [
            'email' => $email
        ]);
        return $stmt->fetch();
    }

    // For the 'admin' user who might not have an email set in your old system
    public function findByUsername($username) {
        $stmt = $this->db->query("SELECT * FROM pa_users WHERE uname = :uname LIMIT 1", [
            'uname' => $username
        ]);
        return $stmt->fetch();
    }
}