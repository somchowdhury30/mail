<?php
// B:\Tools_And_Script\Python_project\mail_auto_gmail_inbox\php\config\init_db.php

require_once __DIR__ . '/db.php';

try {
    $pdo = Database::getConnection();

    // Create table
    $query = "
    CREATE TABLE IF NOT EXISTS email_credentials (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        email TEXT NOT NULL UNIQUE,
        app_password TEXT NOT NULL,
        is_active INTEGER DEFAULT 1,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP
    );
    ";
    
    $pdo->exec($query);
    
    // Add the user's test email if it doesn't exist
    // Taking standard format for testing. The user will manage these via their admin panel later.
    $check = $pdo->query("SELECT COUNT(*) FROM email_credentials")->fetchColumn();
    
    if ($check == 0) {
        // Insert a dummy row (User should replace with real or delete later)
        // They can insert via script or sqlite browser
        $stmt = $pdo->prepare("INSERT INTO email_credentials (email, app_password) VALUES (:email, :password)");
        $stmt->execute([
            ':email' => 'test@example.com',
            ':password' => 'dummy_app_password_here'
        ]);
        echo "Database initialized and test credential added. \n";
    } else {
        echo "Database already initialized and contains data. \n";
    }

    echo "Status: Elite Schema Active.";

} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}
