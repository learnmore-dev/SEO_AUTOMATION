<?php
require_once __DIR__ . '/config.php';
$db = getDB();

$src = __DIR__ . '/assets/logo.jpeg';
if (file_exists($src)) {
    $destName = 'project_1_' . time() . '.jpeg';
    $destPath = __DIR__ . '/uploads/' . $destName;
    copy($src, $destPath);
    file_put_contents(__DIR__ . '/assets/logo_ext.txt', 'jpeg');

    $db->prepare("UPDATE projects SET post_image = ? WHERE id = 1")->execute([$destName]);
    echo "Successfully copied logo.jpeg to uploads/{$destName} and updated project 1 post_image!\n";
} else {
    echo "assets/logo.jpeg not found\n";
}

$stmt = $db->query("SELECT id, business_name, post_image FROM projects");
echo "<pre>";
print_r($stmt->fetchAll(PDO::FETCH_ASSOC));
echo "</pre>";
