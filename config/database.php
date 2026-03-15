<?php
/**
 * CivicTrack — MongoDB Database Connection
 * 
 * Provides a singleton connection to the MongoDB database
 * and helper functions to access collections.
 */

require_once __DIR__ . '/../vendor/autoload.php';

use MongoDB\Client;

class Database {
    private static $instance = null;
    private $client;
    private $db;

    private function __construct() {
        try {
            // Explicitly set a short timeout for initial connection check
            $this->client = new Client("mongodb://localhost:27017", [
                'serverSelectionTimeoutMS' => 2000
            ]);
            // Attempt a simple command to verify connection
            $this->client->listDatabases();
            $this->db = $this->client->civictrack;
        } catch (Exception $e) {
            header('Content-Type: text/plain');
            die("CivicTrack Error: Database connection failed. Please ensure MongoDB is running on localhost:27017.\nDetails: " . $e->getMessage());
        }
    }

    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    public function getDatabase() {
        return $this->db;
    }

    public function getCollection($name) {
        return $this->db->$name;
    }

    /**
     * Seed default admin if none exists
     */
    public function seedAdmin() {
        try {
            $admins = $this->getCollection('admins');
            $existing = $admins->findOne(['email' => 'admin@civictrack.com']);
            if (!$existing) {
                $admins->insertOne([
                    'name'       => 'Admin',
                    'email'      => 'admin@civictrack.com',
                    'password'   => password_hash('admin123', PASSWORD_BCRYPT),
                    'role'       => 'admin',
                    'created_at' => date('Y-m-d H:i:s')
                ]);
            }
        } catch (Exception $e) {
            // Seed failed (possibly validation schema) — skip silently
        }
    }

    /**
     * Seed default officer if none exists
     */
    public function seedOfficer() {
        try {
            $officers = $this->getCollection('officers');
            $existing = $officers->findOne(['email' => 'officer@civictrack.com']);
            if (!$existing) {
                $officers->insertOne([
                    'name'       => 'Default Officer',
                    'email'      => 'officer@civictrack.com',
                    'phone'      => '',
                    'password'   => password_hash('officer123', PASSWORD_BCRYPT),
                    'role'       => 'officer',
                    'created_at' => date('Y-m-d H:i:s')
                ]);
            }
        } catch (Exception $e) {
            // Seed failed (possibly validation schema) — skip silently
        }
    }
}

// Auto-seed admin and officer on first load
$database = Database::getInstance();
$database->seedAdmin();
$database->seedOfficer();
