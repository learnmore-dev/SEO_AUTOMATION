<?php
require_once __DIR__ . '/config.php';
$db = getDB();
// Reset bluesky to pending now that published_url is created
$db->exec("UPDATE backlink_queue SET status = 'pending', error_message = NULL WHERE id = 2");
$stmt = $db->query("SELECT id, platform, status, error_message FROM backlink_queue");
echo "<pre>";
print_r($stmt->fetchAll(PDO::FETCH_ASSOC));
echo "</pre>";
