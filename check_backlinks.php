<?php
require_once __DIR__ . '/config.php';
$db = getDB();
$stmt = $db->query("SELECT * FROM backlinks ORDER BY id DESC LIMIT 5");
echo "<pre>";
print_r($stmt->fetchAll(PDO::FETCH_ASSOC));
echo "</pre>";
