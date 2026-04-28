<?php
// B:\Tools_And_Script\Python_project\mail_auto_gmail_inbox\php\config\db.php

/**
 * Aether Elite DB Connection (JSON Driver)
 * Fallback driver because PDO SQLite is missing.
 * This ensures the script works 100% out of the box without any server config.
 */

class Database {
    private static $dbPath = __DIR__ . '/database.json';

    public static function getAccounts() {
        if (!file_exists(self::$dbPath)) {
            // Initialize with dummy data
            $initialData = [
                [
                    "id" => 1,
                    "email" => "test@example.com",
                    "app_password" => "dummy_app_password",
                    "is_active" => 1,
                    "created_at" => date("Y-m-d H:i:s")
                ]
            ];
            file_put_contents(self::$dbPath, json_encode($initialData, JSON_PRETTY_PRINT));
            return $initialData;
        }

        $json = file_get_contents(self::$dbPath);
        return json_decode($json, true) ?: [];
    }
    
    public static function getActiveAccounts() {
        $accounts = self::getAccounts();
        return array_filter($accounts, function($acc) {
            return isset($acc['is_active']) && $acc['is_active'] == 1;
        });
    }
}
