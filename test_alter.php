<?php
require_once __DIR__ . '/config.php';
$db = getDB();
try {
    $db->exec("ALTER TABLE backlink_queue ADD COLUMN published_url TEXT DEFAULT NULL");
    echo "Column published_url added successfully!\n";
} catch (Exception $e) {
    echo "Notice/Error adding published_url: " . $e->getMessage() . "\n";
}

// Check PRAGMA table_info
$cols = $db->query("PRAGMA table_info(backlink_queue)")->fetchAll(PDO::FETCH_ASSOC);
echo "<pre>";
print_r($cols);
echo "</pre>";
