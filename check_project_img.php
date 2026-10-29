<?php
require_once __DIR__ . '/config.php';
$db = getDB();
$p = $db->query("SELECT id, website_url, business_name, post_image FROM projects")->fetchAll(PDO::FETCH_ASSOC);
echo "<pre>PROJECTS:\n";
print_r($p);

$q = $db->query("SELECT id, platform, post_image FROM backlink_queue")->fetchAll(PDO::FETCH_ASSOC);
echo "\nBACKLINK_QUEUE:\n";
print_r($q);
echo "</pre>";
