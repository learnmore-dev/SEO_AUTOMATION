<?php
/**
 * Test Groq / xAI Grok API Integration
 * Run via browser: http://localhost:8000/test_groq.php
 */
require_once __DIR__ . '/config.php';

$key = defined('GROQ_API_KEY') ? GROQ_API_KEY : '';
if (empty($key)) {
    $local = is_readable(__DIR__ . '/config.local.php') ? (array) include __DIR__ . '/config.local.php' : [];
    $key = $local['GROQ_API_KEY'] ?? '';
}

$testPrompt = "Write a catchy 1-line SEO tagline for 'Best Web Development Course 2026'.";

echo "<div style='font-family:sans-serif; max-width:650px; margin:40px auto; padding:24px; border:1px solid #ddd; border-radius:10px;'>";
echo "<h2>⚡ Groq / Grok API Live Test</h2>";

if (empty($key)) {
    echo "<p style='color:red;'><strong>No Groq API key configured yet!</strong></p>";
    echo "<p>Please visit <a href='api-setup.php'>API Keys Setup</a> and paste your key in the <strong>Groq / Grok API Key</strong> box.</p>";
    echo "</div>";
    exit;
}

$maskedKey = substr($key, 0, 6) . '...' . substr($key, -4);
$modelUsed = !empty($_GET['model']) ? trim($_GET['model']) : (defined('GROQ_MODEL') ? GROQ_MODEL : 'qwen/qwen3.8-27b');
echo "<p>Testing with Key: <code>{$maskedKey}</code> | Model: <strong>{$modelUsed}</strong></p>";

$start = microtime(true);
$result = generateWithGroq($testPrompt, $key, $modelUsed);
$duration = round(microtime(true) - $start, 3);

if ($result) {
    echo "<div style='background:#e6fffa; border:1px solid #38b2ac; padding:15px; border-radius:8px; margin-top:15px;'>";
    echo "<h4 style='color:#234e52; margin-top:0;'>✅ Success! (Response time: {$duration}s)</h4>";
    echo "<p><strong>Prompt:</strong> {$testPrompt}</p>";
    echo "<p><strong>AI Response:</strong></p>";
    echo "<pre style='background:#fff; padding:12px; border-radius:6px; border:1px solid #b2f5ea; white-space:pre-wrap; font-family:sans-serif;'>" . htmlspecialchars($result) . "</pre>";
    echo "</div>";
} else {
    echo "<div style='background:#fff5f5; border:1px solid #e53e3e; padding:15px; border-radius:8px; margin-top:15px;'>";
    echo "<h4 style='color:#742a2a; margin-top:0;'>❌ Failed to connect</h4>";
    echo "<p>Check if your key is active and has credits/quota.</p>";
    echo "</div>";
}

echo "<p style='margin-top:20px;'><a href='api-setup.php'>← Back to API Setup</a></p>";
echo "</div>";
