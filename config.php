<?php
// Enable local PHP error logging in the logs folder
ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/logs/php_errors.log');

// ============================================================
// config.php - SEO 80/20 System Configuration
// ============================================================

// Load local overrides (API keys, DB password) — copy config.local.php.example
$_seoLocal = [];
if (is_readable(__DIR__ . '/config.local.php')) {
    $_seoLocal = (array) include __DIR__ . '/config.local.php';
}

function seoCfg(string $key, $default = '') {
    global $_seoLocal;
    $env = getenv($key);
    if ($env !== false && $env !== '') {
        return $env;
    }
    return $_seoLocal[$key] ?? $default;
}

define('DB_TYPE', (string) seoCfg('DB_TYPE', 'sqlite'));
define('DB_SQLITE_PATH', (string) seoCfg('DB_SQLITE_PATH', __DIR__ . '/database.sqlite'));
define('DB_HOST', (string) seoCfg('DB_HOST', '127.0.0.1:3306'));
define('DB_USER', (string) seoCfg('DB_USER', 'root'));
define('DB_PASS', (string) seoCfg('DB_PASS', ''));
define('DB_NAME', (string) seoCfg('DB_NAME', 'seo_system'));

define('SITE_NAME', 'SEO 80/20 System');

// Auto-detect site URL (fixes menu when folder is not /seo-system/)
function detectSiteUrl(): string {
    if (!empty($_SERVER['HTTP_HOST'])) {
        $https = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
            || (isset($_SERVER['SERVER_PORT']) && (int) $_SERVER['SERVER_PORT'] === 443);
        $scheme = $https ? 'https' : 'http';
        $host   = $_SERVER['HTTP_HOST'];
        $script = str_replace('\\', '/', $_SERVER['SCRIPT_NAME'] ?? '/index.php');
        $dir    = rtrim(dirname($script), '/\\');
        if ($dir === '/' || $dir === '.') {
            $dir = '';
        }
        return $scheme . '://' . $host . $dir;
    }
    return rtrim((string) seoCfg('SITE_URL', 'http://localhost/seo-system'), '/');
}

define('SITE_URL', rtrim((string) seoCfg('SITE_URL', detectSiteUrl()), '/'));

// API keys — set in config.local.php (see api-setup.php)
define('OPENAI_API_KEY',       (string) seoCfg('OPENAI_API_KEY', ''));
define('OPENAI_IMAGE_API_KEY', (string) seoCfg('OPENAI_IMAGE_API_KEY', ''));
define('OPENAI_MODEL',         (string) seoCfg('OPENAI_MODEL', 'gpt-4o-mini'));
define('OPENAI_IMAGE_MODEL',   (string) seoCfg('OPENAI_IMAGE_MODEL', 'dall-e-3'));
define('OPENAI_IMAGE_SIZE',    (string) seoCfg('OPENAI_IMAGE_SIZE', '1024x1024'));
define('GEMINI_API_KEY',       (string) seoCfg('GEMINI_API_KEY', ''));
define('DEEPSEEK_API_KEY',     (string) seoCfg('DEEPSEEK_API_KEY', ''));
define('DEEPSEEK_MODEL',       (string) seoCfg('DEEPSEEK_MODEL', 'deepseek-chat'));
define('GROQ_API_KEY',         (string) seoCfg('GROQ_API_KEY', ''));
define('GROQ_MODEL',           (string) seoCfg('GROQ_MODEL', 'qwen/qwen3.8-27b'));
define('PRIMARY_AI_PROVIDER',  (string) seoCfg('PRIMARY_AI_PROVIDER', 'auto'));
define('STABILITY_API_KEY',    (string) seoCfg('STABILITY_API_KEY', ''));
define('HUGGINGFACE_API_KEY',  (string) seoCfg('HUGGINGFACE_API_KEY', ''));
define('GOOGLE_API_KEY',       (string) seoCfg('GOOGLE_API_KEY', ''));
define('GOOGLE_CSE_CX',        (string) seoCfg('GOOGLE_CSE_CX', ''));
define('DATAFORSEO_LOGIN',     (string) seoCfg('DATAFORSEO_LOGIN', ''));
define('DATAFORSEO_PASSWORD',  (string) seoCfg('DATAFORSEO_PASSWORD', ''));

// Toggle for Tier 2 Auto-Posting (set to false in config.local.php to save API costs)
define('ENABLE_TIER2_POSTING', (bool) seoCfg('ENABLE_TIER2_POSTING', true));

// Cron queue worker batch size (number of tasks to process in one execution)
define('CRON_BATCH_SIZE', (int) seoCfg('CRON_BATCH_SIZE', 5));

define('SMTP_HOST', (string) seoCfg('SMTP_HOST', 'smtp.gmail.com'));
define('SMTP_USER', (string) seoCfg('SMTP_USER', ''));
define('SMTP_PASS', (string) seoCfg('SMTP_PASS', ''));
define('SMTP_PORT', (int) seoCfg('SMTP_PORT', 587));

// Session
if (session_status() === PHP_SESSION_NONE && php_sapi_name() !== 'cli' && !headers_sent()) {
    session_start();
}

// Ensure upload folders exist
foreach (['uploads', 'assets', 'logs'] as $_dir) {
    $_path = __DIR__ . '/' . $_dir;
    if (!is_dir($_path)) {
        @mkdir($_path, 0755, true);
    }
}

function initSqliteDatabase(PDO $pdo, string $superUser = 'admin', string $superPass = 'admin123', string $superEmail = 'admin@skyrank.com'): void {
    $schema = "
    CREATE TABLE IF NOT EXISTS users (
      id INTEGER PRIMARY KEY AUTOINCREMENT,
      username TEXT NOT NULL UNIQUE,
      password TEXT NOT NULL,
      email TEXT NOT NULL UNIQUE,
      role TEXT DEFAULT 'admin',
      api_key TEXT DEFAULT NULL,
      allowed_menus TEXT DEFAULT NULL,
      created_at DATETIME DEFAULT CURRENT_TIMESTAMP
    );
    CREATE TABLE IF NOT EXISTS projects (
      id INTEGER PRIMARY KEY AUTOINCREMENT,
      user_id INTEGER NOT NULL,
      website_url TEXT NOT NULL,
      target_keyword TEXT NOT NULL,
      target_site TEXT DEFAULT NULL,
      package_type TEXT DEFAULT 'basic',
      business_name TEXT DEFAULT '',
      business_address TEXT DEFAULT NULL,
      business_hours TEXT DEFAULT NULL,
      business_desc TEXT DEFAULT NULL,
      contact_name TEXT DEFAULT '',
      phone TEXT DEFAULT '',
      email TEXT DEFAULT '',
      gsc_access TEXT DEFAULT NULL,
      ga_access TEXT DEFAULT NULL,
      google_ads_id TEXT DEFAULT NULL,
      admin_url TEXT DEFAULT '',
      admin_user TEXT DEFAULT '',
      admin_pass TEXT DEFAULT NULL,
      competitor_sites TEXT DEFAULT NULL,
      post_image TEXT DEFAULT NULL,
      seo_score INTEGER DEFAULT NULL,
      pagespeed_score INTEGER DEFAULT NULL,
      pagespeed_mobile_score INTEGER DEFAULT NULL,
      pagespeed_desktop_score INTEGER DEFAULT NULL,
      pagespeed_metrics_json TEXT DEFAULT NULL,
      pagespeed_checked_at DATETIME DEFAULT NULL,
      created_at DATETIME DEFAULT CURRENT_TIMESTAMP
    );
    CREATE TABLE IF NOT EXISTS social_accounts (
      id INTEGER PRIMARY KEY AUTOINCREMENT,
      user_id INTEGER NOT NULL,
      project_id INTEGER NOT NULL DEFAULT 0,
      platform TEXT NOT NULL,
      username TEXT NOT NULL,
      password TEXT DEFAULT NULL,
      api_key TEXT DEFAULT NULL,
      api_secret TEXT DEFAULT NULL,
      refresh_token TEXT DEFAULT NULL,
      status TEXT DEFAULT 'active',
      created_at DATETIME DEFAULT CURRENT_TIMESTAMP
    );
    CREATE TABLE IF NOT EXISTS backlinks (
      id INTEGER PRIMARY KEY AUTOINCREMENT,
      project_id INTEGER NOT NULL,
      backlink_url TEXT NOT NULL,
      platform TEXT NOT NULL,
      da_score INTEGER DEFAULT 70,
      status TEXT DEFAULT 'created',
      verified_status TEXT DEFAULT 'unverified',
      last_checked_at DATETIME DEFAULT NULL,
      post_title TEXT DEFAULT NULL,
      keyword TEXT DEFAULT NULL,
      target_url TEXT DEFAULT NULL,
      created_at DATETIME DEFAULT CURRENT_TIMESTAMP
    );
    CREATE TABLE IF NOT EXISTS backlink_queue (
      id INTEGER PRIMARY KEY AUTOINCREMENT,
      project_id INTEGER NOT NULL,
      social_account_id INTEGER DEFAULT 0,
      platform TEXT NOT NULL,
      keyword TEXT DEFAULT NULL,
      target_url TEXT DEFAULT NULL,
      post_image TEXT DEFAULT NULL,
      status TEXT DEFAULT 'pending',
      published_url TEXT DEFAULT NULL,
      error_message TEXT DEFAULT NULL,
      created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
      updated_at DATETIME DEFAULT CURRENT_TIMESTAMP
    );
    CREATE TABLE IF NOT EXISTS keywords (
      id INTEGER PRIMARY KEY AUTOINCREMENT,
      project_id INTEGER NOT NULL,
      keyword TEXT NOT NULL,
      volume INTEGER DEFAULT 0,
      cpc REAL DEFAULT 0.00,
      competition REAL DEFAULT 0.00,
      seo_difficulty INTEGER DEFAULT NULL,
      score INTEGER DEFAULT 0,
      status TEXT DEFAULT 'Pending',
      created_at DATETIME DEFAULT CURRENT_TIMESTAMP
    );
    CREATE TABLE IF NOT EXISTS seo_reports (
      id INTEGER PRIMARY KEY AUTOINCREMENT,
      project_id INTEGER NOT NULL,
      rank INTEGER DEFAULT 0,
      seo_score INTEGER DEFAULT 0,
      report_date DATE NOT NULL,
      created_at DATETIME DEFAULT CURRENT_TIMESTAMP
    );
    CREATE TABLE IF NOT EXISTS project_meta (
      id INTEGER PRIMARY KEY AUTOINCREMENT,
      project_id INTEGER NOT NULL UNIQUE,
      meta_title TEXT DEFAULT NULL,
      meta_description TEXT DEFAULT NULL,
      meta_keywords TEXT DEFAULT NULL,
      og_title TEXT DEFAULT NULL,
      og_description TEXT DEFAULT NULL,
      og_image TEXT DEFAULT NULL,
      h1_suggestion TEXT DEFAULT NULL,
      schema_json TEXT DEFAULT NULL,
      full_head_html TEXT DEFAULT NULL,
      created_at DATETIME DEFAULT CURRENT_TIMESTAMP
    );
    CREATE TABLE IF NOT EXISTS content_queue (
      id INTEGER PRIMARY KEY AUTOINCREMENT,
      project_id INTEGER NOT NULL,
      title TEXT NOT NULL,
      article TEXT NOT NULL,
      status TEXT DEFAULT 'draft',
      created_at DATETIME DEFAULT CURRENT_TIMESTAMP
    );
    CREATE TABLE IF NOT EXISTS onpage_issues (
      id INTEGER PRIMARY KEY AUTOINCREMENT,
      project_id INTEGER NOT NULL,
      type TEXT NOT NULL,
      detail TEXT NOT NULL,
      severity TEXT DEFAULT 'medium',
      fix TEXT DEFAULT NULL,
      status TEXT DEFAULT 'open',
      created_at DATETIME DEFAULT CURRENT_TIMESTAMP
    );
    CREATE TABLE IF NOT EXISTS keyword_rankings_log (
      id INTEGER PRIMARY KEY AUTOINCREMENT,
      project_id INTEGER NOT NULL,
      keyword TEXT NOT NULL,
      position INTEGER NOT NULL,
      logged_at DATETIME DEFAULT CURRENT_TIMESTAMP
    );
    CREATE TABLE IF NOT EXISTS broken_internal_links (
      id INTEGER PRIMARY KEY AUTOINCREMENT,
      project_id INTEGER NOT NULL,
      source_url TEXT NOT NULL,
      broken_url TEXT NOT NULL,
      status_code INTEGER NOT NULL,
      found_at DATETIME DEFAULT CURRENT_TIMESTAMP
    );
    ";
    $pdo->exec($schema);

    // Create default superuser if users table is empty
    $count = (int)$pdo->query("SELECT COUNT(*) FROM users")->fetchColumn();
    if ($count === 0) {
        $hash = password_hash($superPass, PASSWORD_BCRYPT);
        $apiKey = 'sky_' . bin2hex(random_bytes(16));
        $stmt = $pdo->prepare("INSERT INTO users (username, password, email, role, api_key) VALUES (?, ?, ?, 'admin', ?)");
        $stmt->execute([$superUser, $hash, $superEmail, $apiKey]);
    }
}

function getDB() {
    static $pdo = null;
    if ($pdo === null) {
        try {
            if (DB_TYPE === 'sqlite') {
                $dbPath = DB_SQLITE_PATH;
                $isNew = !file_exists($dbPath) || filesize($dbPath) === 0;
                $pdo = new PDO('sqlite:' . $dbPath, null, null, [
                    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                    PDO::ATTR_EMULATE_PREPARES   => false,
                ]);
                $pdo->exec('PRAGMA foreign_keys = ON;');
                $pdo->exec('PRAGMA journal_mode = WAL;');

                // MySQL compatibility functions in SQLite
                if (method_exists($pdo, 'sqliteCreateFunction')) {
                    $pdo->sqliteCreateFunction('NOW', fn() => date('Y-m-d H:i:s'));
                    $pdo->sqliteCreateFunction('CURDATE', fn() => date('Y-m-d'));
                    $pdo->sqliteCreateFunction('IFNULL', fn($a, $b) => $a !== null ? $a : $b);
                }

                if ($isNew) {
                    initSqliteDatabase($pdo);
                }
            } else {
                $pdo = new PDO(
                    'mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=utf8mb4',
                    DB_USER,
                    DB_PASS,
                    [
                        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                        PDO::ATTR_EMULATE_PREPARES   => false,
                    ]
                );
            }
            // Self-healing migration
            try {
                $pdo->exec("ALTER TABLE backlink_queue ADD COLUMN post_image VARCHAR(255) DEFAULT NULL");
            } catch (Throwable $ex) {}
            try {
                $pdo->exec("ALTER TABLE backlink_queue ADD COLUMN published_url TEXT DEFAULT NULL");
            } catch (Throwable $ex) {}
        } catch (Throwable $e) {
            if (!empty($_GET['ajax']) || !empty($_SERVER['HTTP_X_REQUESTED_WITH'])) {
                header('Content-Type: application/json');
                die(json_encode(['error' => 'Database connection failed: ' . $e->getMessage()]));
            }
            die('<div style="font-family:sans-serif;max-width:600px;margin:40px auto;padding:24px;border:1px solid #dc3545;border-radius:8px;">'
                . '<h3>Database connection failed</h3>'
                . '<p>' . htmlspecialchars($e->getMessage()) . '</p>'
                . '<p><a href="setup.php">Run setup check</a> | <a href="reset-all.php">Reset Database</a></p></div>');
        }
    }
    return $pdo;
}

function getDBConnection() {
    return getDB();
}

function requireLogin() {
    if (!isset($_SESSION['user_id'])) {
        header('Location: ' . SITE_URL . '/index.php');
        exit;
    }
}

function requireMenuPermission($menuCode) {
    requireLogin();
    // Admin role gets absolute access to all pages
    if (($_SESSION['role'] ?? 'client') === 'admin') {
        return;
    }
    $userId = $_SESSION['user_id'] ?? 0;
    if ($userId > 0) {
        $db = getDB();
        $stmt = $db->prepare("SELECT allowed_menus FROM users WHERE id = ?");
        $stmt->execute([$userId]);
        $val = $stmt->fetchColumn();
        if ($val !== null && $val !== '') {
            $allowed = array_map('trim', explode(',', strtolower($val)));
            if (!in_array(strtolower($menuCode), $allowed)) {
                setFlash('danger', 'Access denied. You do not have permission to access that page.');
                header('Location: ' . SITE_URL . '/dashboard.php');
                exit;
            }
        }
    }
}

function clean($input) {
    return htmlspecialchars(strip_tags(trim((string) $input)), ENT_QUOTES, 'UTF-8');
}

function getEnqueuedImagePath() {
    static $enqueuedImage = null;
    if (func_num_args() > 0) {
        $enqueuedImage = func_get_arg(0);
    }
    return $enqueuedImage;
}

function resolveProjectImagePath($projectId = 0, $enqueuedImg = null, $projectImg = null) {
    $uploadDir = __DIR__ . '/uploads/';

    if (empty($enqueuedImg)) {
        $enqueuedImg = function_exists('getEnqueuedImagePath') ? getEnqueuedImagePath() : null;
    }

    $tryFile = function($path) use ($uploadDir) {
        if (empty($path)) return '';
        if (file_exists($path) && is_file($path)) return realpath($path);
        $inUploads = $uploadDir . basename($path);
        if (file_exists($inUploads) && is_file($inUploads)) return realpath($inUploads);
        return '';
    };

    // Priority 1: Explicitly enqueued image
    $res = $tryFile($enqueuedImg);
    if (!empty($res)) return $res;

    // Priority 2: Passed project image
    $res = $tryFile($projectImg);
    if (!empty($res)) return $res;

    // Priority 3: Fetch post_image directly from projects DB table if projectId given
    if ($projectId > 0) {
        try {
            $db = getDBConnection();
            if ($db) {
                $stmt = $db->prepare("SELECT post_image FROM projects WHERE id=?");
                $stmt->execute([$projectId]);
                $dbImg = $stmt->fetchColumn();
                $res = $tryFile($dbImg);
                if (!empty($res)) return $res;
            }
        } catch (Throwable $e) {}
    }

    // Priority 4: Search for any project uploaded/generated file in uploads/ matching project_<id>_*
    if ($projectId > 0) {
        $patterns = [
            $uploadDir . 'project_' . $projectId . '_*',
            $uploadDir . 'poster_' . $projectId . '_*',
            $uploadDir . 'auto_img_*' . $projectId . '*'
        ];
        $matchedFiles = [];
        foreach ($patterns as $pattern) {
            $found = glob($pattern);
            if ($found) {
                $matchedFiles = array_merge($matchedFiles, $found);
            }
        }
        if (!empty($matchedFiles)) {
            usort($matchedFiles, fn($a, $b) => filemtime($b) - filemtime($a));
            return realpath($matchedFiles[0]);
        }
    }

    // Priority 5: Latest image file in uploads directory
    $allImages = glob($uploadDir . '*.{jpg,jpeg,png,webp,gif}', GLOB_BRACE);
    if ($allImages) {
        usort($allImages, fn($a, $b) => filemtime($b) - filemtime($a));
        return realpath($allImages[0]);
    }

    // Priority 6: User uploaded logo / branding image in assets/
    $assetsDir = __DIR__ . '/assets/';
    $logoCandidates = [
        $assetsDir . 'logo.jpeg',
        $assetsDir . 'logo.jpg',
        $assetsDir . 'logo.png',
        $assetsDir . 'logo.webp',
    ];
    foreach ($logoCandidates as $cand) {
        if (file_exists($cand) && filesize($cand) > 0) {
            return realpath($cand);
        }
    }

    return '';
}

function formatLocalTime($utcDateTime, $format = 'd M H:i', $timezone = 'Asia/Kolkata') {
    if (empty($utcDateTime)) return '';
    try {
        $dt = new DateTime($utcDateTime, new DateTimeZone('UTC'));
        $dt->setTimezone(new DateTimeZone($timezone));
        return $dt->format($format);
    } catch (Exception $e) {
        return $utcDateTime;
    }
}

function csrfToken() {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function verifyCsrf($token) {
    return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
}

function setFlash($type, $msg) {
    $_SESSION['flash'] = ['type' => $type, 'msg' => $msg];
}

function getFlash() {
    if (isset($_SESSION['flash'])) {
        $f = $_SESSION['flash'];
        unset($_SESSION['flash']);
        return $f;
    }
    return null;
}

function hasApiKey(string $name): bool {
    $v = constant($name);
    return is_string($v) && $v !== '' && strpos($v, 'your-') !== 0 && strpos($v, 'paste-') !== 0;
}

function hasChatGPT(): bool {
    $k = OPENAI_API_KEY;
    return $k !== '' && strpos($k, 'sk-') === 0;
}

function hasGemini(): bool {
    $k = GEMINI_API_KEY;
    return $k !== '' && strpos($k, 'your-') === false;
}

function hasGroq(): bool {
    $k = defined('GROQ_API_KEY') ? GROQ_API_KEY : '';
    return !empty($k) && strpos($k, 'your-') === false;
}

/**
 * Shared utility to check if a backlink webpage links back to client's website.
 */
function verifyBacklink(string $backlinkUrl, string $clientUrl, ?string $targetUrl): string {
    $ch = curl_init();
    curl_setopt_array($ch, [
        CURLOPT_URL            => $backlinkUrl,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_MAXREDIRS      => 5,
        CURLOPT_USERAGENT      => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/115.0.0.0 Safari/537.36',
        CURLOPT_TIMEOUT        => 12,
        CURLOPT_SSL_VERIFYPEER => false,
        CURLOPT_SSL_VERIFYHOST => false
    ]);
    
    $html     = curl_exec($ch);
    $httpCode = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    if ($httpCode < 200 || $httpCode >= 400 || empty($html)) {
        return 'broken';
    }
    
    $clientHost = parse_url($clientUrl, PHP_URL_HOST);
    $targetHost = !empty($targetUrl) ? parse_url($targetUrl, PHP_URL_HOST) : null;
    
    if (empty($clientHost)) {
        return 'broken';
    }
    
    $dom = new DOMDocument();
    libxml_use_internal_errors(true);
    @$dom->loadHTML($html);
    libxml_clear_errors();
    
    $xpath = new DOMXPath($dom);
    $anchors = $xpath->query('//a');
    
    $found      = false;
    $isNofollow = false;
    
    foreach ($anchors as $a) {
        $href = trim($a->getAttribute('href'));
        $rel  = strtolower(trim($a->getAttribute('rel')));
        
        $match = false;
        if (strpos($href, $clientHost) !== false) {
            $match = true;
        }
        if ($targetHost && strpos($href, $targetHost) !== false) {
            $match = true;
        }
        
        if ($match) {
            $found = true;
            if (strpos($rel, 'nofollow') !== false) {
                $isNofollow = true;
            }
        }
    }
    
    if ($found) {
        return $isNofollow ? 'nofollow' : 'active';
    }
    
    return 'broken';
}

/**
 * Pings Google, Bing, and Ping-o-Matic to crawl new backlinks instantly.
 */
function pingSearchEngines(string $backlinkUrl, ?string $title = null): void {
    if (empty($backlinkUrl) || filter_var($backlinkUrl, FILTER_VALIDATE_URL) === false) {
        return;
    }
    $title = $title ?: 'New Backlink Created';
    $encodedUrl = urlencode($backlinkUrl);
    
    // 1. Bing Sitemap Ping
    $ch = curl_init();
    curl_setopt_array($ch, [
        CURLOPT_URL => "https://www.bing.com/ping?sitemap=" . $encodedUrl,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT => 5,
        CURLOPT_SSL_VERIFYPEER => false
    ]);
    curl_exec($ch);
    curl_close($ch);
    
    // 2. Google Sitemap Ping
    $ch = curl_init();
    curl_setopt_array($ch, [
        CURLOPT_URL => "https://www.google.com/ping?sitemap=" . $encodedUrl,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT => 5,
        CURLOPT_SSL_VERIFYPEER => false
    ]);
    curl_exec($ch);
    curl_close($ch);
    
    // 3. Ping-O-Matic XML-RPC Ping (Notifies Bing, Google Blogs, Yahoo, etc.)
    $xml = "<?xml version=\"1.0\"?>
    <methodCall>
      <methodName>weblogUpdates.ping</methodName>
      <params>
        <param><value>" . htmlspecialchars($title) . "</value></param>
        <param><value>" . htmlspecialchars($backlinkUrl) . "</value></param>
      </params>
    </methodCall>";
    
    $ch = curl_init('http://rpc.pingomatic.com/');
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => $xml,
        CURLOPT_HTTPHEADER => ['Content-Type: text/xml'],
        CURLOPT_TIMEOUT => 8,
        CURLOPT_SSL_VERIFYPEER => false
    ]);
    curl_exec($ch);
    curl_close($ch);
}

function getOnboardingToken(int $projectId, string $websiteUrl): string {
    return md5($projectId . $websiteUrl . 'seo_onboarding_secure_salt_2026');
}

function checkGoogleRank($keyword, $targetSite) {
    $domain = parse_url($targetSite, PHP_URL_HOST) ?: $targetSite;
    $domain = str_replace('www.', '', strtolower($domain));

    // ── Method 0: Google Custom Search Engine (CSE) API (Official & 100% Free 100/day) ──
    $googleApiKey = defined('GOOGLE_API_KEY') ? GOOGLE_API_KEY : '';
    $googleCseCx  = defined('GOOGLE_CSE_CX') ? GOOGLE_CSE_CX : '';
    if (!empty($googleApiKey) && !empty($googleCseCx)) {
        $cseUrl = "https://www.googleapis.com/customsearch/v1?key=" . urlencode($googleApiKey) . "&cx=" . urlencode($googleCseCx) . "&q=" . urlencode($keyword) . "&num=10";
        $ch = curl_init($cseUrl);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT        => 15,
            CURLOPT_SSL_VERIFYPEER => false,
        ]);
        $resp = curl_exec($ch);
        curl_close($ch);
        
        $data = json_decode($resp, true);
        $items = $data['items'] ?? [];
        foreach ($items as $pos => $item) {
            $itemUrl = $item['link'] ?? '';
            $ld = str_replace('www.', '', strtolower(parse_url($itemUrl, PHP_URL_HOST) ?: ''));
            if (stripos($ld, $domain) !== false) {
                return $pos + 1;
            }
        }
    }

    // ── Method 1: DataForSEO SERP API (most accurate) ────────
    $login    = defined('DATAFORSEO_LOGIN')    ? DATAFORSEO_LOGIN    : '';
    $password = defined('DATAFORSEO_PASSWORD') ? DATAFORSEO_PASSWORD : '';

    if ($login && $password) {
        $postData = json_encode([[
            'keyword'       => $keyword,
            'location_code' => 2356,   // India
            'language_code' => 'en',
            'device'        => 'desktop',
            'os'            => 'windows',
            'depth'         => 100,
        ]]);

        $ch = curl_init('https://api.dataforseo.com/v3/serp/google/organic/live/advanced');
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST           => true,
            CURLOPT_POSTFIELDS     => $postData,
            CURLOPT_HTTPHEADER     => [
                'Authorization: Basic ' . base64_encode($login . ':' . $password),
                'Content-Type: application/json',
            ],
            CURLOPT_TIMEOUT        => 60,
            CURLOPT_SSL_VERIFYPEER => false,
        ]);
        $resp = curl_exec($ch);
        curl_close($ch);

        $data = json_decode($resp, true);
        $items = $data['tasks'][0]['result'][0]['items'] ?? [];

        foreach ($items as $item) {
            if (($item['type'] ?? '') !== 'organic') continue;
            $itemUrl    = $item['url'] ?? '';
            $itemDomain = parse_url($itemUrl, PHP_URL_HOST) ?: '';
            $itemDomain = str_replace('www.', '', strtolower($itemDomain));
            if (stripos($itemDomain, $domain) !== false ||
                stripos($itemUrl, $domain) !== false) {
                return (int)($item['rank_absolute'] ?? ($item['rank_group'] ?? 0));
            }
        }

        // If API returned results but domain not found → not in top 100
        if (!empty($items)) return 0;
    }

    // ── Method 2: Bing scraping fallback ─────────────────────
    $bingUrl = 'https://www.bing.com/search?q=' . urlencode($keyword) . '&count=50';
    $ch = curl_init();
    curl_setopt_array($ch, [
        CURLOPT_URL            => $bingUrl,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT        => 15,
        CURLOPT_SSL_VERIFYPEER => false,
        CURLOPT_USERAGENT      => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36',
        CURLOPT_HTTPHEADER     => ['Accept-Language: en-US,en;q=0.9'],
    ]);
    $html = curl_exec($ch);
    curl_close($ch);

    if ($html) {
        preg_match_all('/<h2><a[^>]+href="([^"]+)"/i', $html, $m);
        $links = $m[1] ?? [];
        if (empty($links)) {
            preg_match_all('/href="([^"]+)"/i', $html, $m2);
            $links = $m2[1] ?? [];
        }
        
        $pos = 1;
        foreach ($links as $link) {
            if (strpos($link, 'http') !== 0) continue;
            if (strpos($link, 'bing.com') !== false || strpos($link, 'microsoft.com') !== false || strpos($link, 'live.com') !== false || strpos($link, 'go.microsoft.com') !== false) {
                continue;
            }
            
            $ld = str_replace('www.', '', strtolower(parse_url($link, PHP_URL_HOST) ?: ''));
            if (stripos($ld, $domain) !== false) {
                return $pos;
            }
            $pos++;
        }
    }

    return 0;
}

/**
 * Generate a random anchor text for a given keyword and business name to ensure Anchor Text Diversity.
 */
function getRandomAnchorText(string $keyword, string $businessName, string $websiteUrl = ''): string {
    $keyword = trim($keyword);
    $cleanUrl = '';
    if (!empty($websiteUrl)) {
        $cleanUrl = preg_replace('/^https?:\/\/(www\.)?/', '', rtrim($websiteUrl, '/'));
    }
    $branding = !empty($businessName) ? $businessName : (!empty($cleanUrl) ? $cleanUrl : 'our website');
    
    // Weighted selection of anchor types:
    // 20% Exact Match, 30% Branding, 25% Generic, 25% LSI / Partial Match
    $rand = rand(1, 100);
    
    if ($rand <= 20) {
        return ucwords($keyword);
    } elseif ($rand <= 50) {
        return $branding;
    } elseif ($rand <= 75) {
        $genericOptions = ["Click Here", "Visit Website", "Learn More", "Read More", "Official Website", "Check this resource"];
        return $genericOptions[array_rand($genericOptions)];
    } else {
        $lsiOptions = [
            "Best " . ucwords($keyword) . " Guide",
            "Professional " . ucwords($keyword),
            "Expert " . ucwords($keyword) . " Services",
            "About " . ucwords($keyword),
            "Find out more about " . ucwords($keyword)
        ];
        return $lsiOptions[array_rand($lsiOptions)];
    }
}

/**
 * Generate three diverse anchor texts (intro, mid, outro) for a single article.
 */
function getDiverseAnchorTexts(string $keyword, string $businessName, string $websiteUrl = ''): array {
    $cleanUrl = '';
    if (!empty($websiteUrl)) {
        $cleanUrl = preg_replace('/^https?:\/\/(www\.)?/', '', rtrim($websiteUrl, '/'));
    }
    $branding = !empty($businessName) ? $businessName : (!empty($cleanUrl) ? $cleanUrl : 'our website');
    
    // Intro: Exact Match
    $intro = ucwords($keyword);
    
    // Mid: LSI / Partial Match
    $lsiOptions = [
        "Best " . ucwords($keyword) . " Guide",
        "Professional " . ucwords($keyword),
        "Expert " . ucwords($keyword) . " Services",
        "About " . ucwords($keyword)
    ];
    $mid = $lsiOptions[array_rand($lsiOptions)];
    
    // Outro: Generic / Branding / URL
    $genericOptions = ["Click Here", "Visit Website", "Learn More", "Read More", $branding];
    if (!empty($websiteUrl)) {
        $genericOptions[] = $websiteUrl;
    }
    $outro = $genericOptions[array_rand($genericOptions)];
    
    return [
        'intro' => $intro,
        'mid'   => $mid,
        'outro' => $outro
    ];
}

/**
 * Verifies if a given backlink URL is indexed by Google using Custom Search Engine API.
 * Returns: 'indexed', 'not_indexed', or 'unchecked' (on API limits/errors).
 */
function checkUrlGoogleIndexing(string $url): string {
    $apiKey = defined('GOOGLE_API_KEY') ? GOOGLE_API_KEY : '';
    $cseCx  = defined('GOOGLE_CSE_CX') ? GOOGLE_CSE_CX : '';
    
    if (empty($apiKey) || empty($cseCx)) {
        return 'unchecked';
    }
    
    // We search for the exact URL in quotes
    $query = '"' . $url . '"';
    $apiUrl = 'https://www.googleapis.com/customsearch/v1?key=' . urlencode($apiKey) . '&cx=' . urlencode($cseCx) . '&q=' . urlencode($query);
    
    $ch = curl_init($apiUrl);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT        => 10,
        CURLOPT_SSL_VERIFYPEER => false,
    ]);
    
    $resp = curl_exec($ch);
    $httpCode = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    if ($httpCode !== 200) {
        // Quota exceeded, invalid key, or network issue
        return 'unchecked';
    }
    
    $data = json_decode($resp, true);
    if (!empty($data['items'])) {
        // Double check if any item URL matches the backlink URL (ignoring http vs https vs trailing slash)
        $normalizedTarget = preg_replace('/^https?:\/\/(www\.)?/', '', rtrim($url, '/'));
        
        foreach ($data['items'] as $item) {
            $normalizedItemLink = preg_replace('/^https?:\/\/(www\.)?/', '', rtrim($item['link'] ?? '', '/'));
            if (strcasecmp($normalizedTarget, $normalizedItemLink) === 0) {
                return 'indexed';
            }
        }
    }
    
    return 'not_indexed';
}

if (!function_exists('generateWithOpenAI')) {
    function generateWithOpenAI(string $prompt, ?string $apiKey = null): ?string {
        $apiKey = $apiKey ?? (defined('OPENAI_API_KEY') ? OPENAI_API_KEY : '');
        if (empty($apiKey) || strpos($apiKey, 'your-') === 0 || strpos($apiKey, 'sk-') !== 0) {
            return null;
        }

        $model = defined('OPENAI_MODEL') ? OPENAI_MODEL : 'gpt-4o-mini';

        // Add random seed to ensure unique content every time
        $randomSeed = rand(100000, 999999);
        $uniquePrompt = $prompt . "\n\nGenerate completely unique content. Random seed: {$randomSeed}. Current timestamp: " . time() . ". Do not repeat any previous content.";

        $ch = curl_init('https://api.openai.com/v1/chat/completions');
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST           => true,
            CURLOPT_POSTFIELDS     => json_encode([
                'model'       => $model,
                'messages'    => [
                    ['role' => 'system', 'content' => 'You are an expert SEO content writer. Write unique, engaging content every time. Never repeat the same content twice. Use HTML when asked. Each request must produce completely different content.'],
                    ['role' => 'user',   'content' => $uniquePrompt],
                ],
                'temperature' => 1.0, // Maximum randomness
                'top_p' => 0.9,
                'max_tokens'  => 4000,
            ]),
            CURLOPT_HTTPHEADER => [
                'Authorization: Bearer ' . $apiKey,
                'Content-Type: application/json',
            ],
            CURLOPT_TIMEOUT        => 60,
            CURLOPT_SSL_VERIFYPEER => false,
        ]);
        $response = json_decode(curl_exec($ch), true);
        $httpCode = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($httpCode !== 200) {
            return null;
        }

        return $response['choices'][0]['message']['content'] ?? null;
    }
}

if (!function_exists('generateWithDeepSeek')) {
    function generateWithDeepSeek(string $prompt, ?string $apiKey = null, int $maxTokens = 2000): ?string {
        $apiKey = $apiKey ?? (defined('DEEPSEEK_API_KEY') ? DEEPSEEK_API_KEY : '');
        if (empty($apiKey) || strpos($apiKey, 'sk-') !== 0) {
            return null;
        }

        $model = defined('DEEPSEEK_MODEL') ? DEEPSEEK_MODEL : 'deepseek-chat';
        $randomSeed = rand(100000, 999999);
        $uniquePrompt = $prompt . "\n\nGenerate completely unique content. Random seed: {$randomSeed}. Current timestamp: " . time() . ". Do not repeat any previous content.";

        $ch = curl_init('https://api.deepseek.com/v1/chat/completions');
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST           => true,
            CURLOPT_POSTFIELDS     => json_encode([
                'model'       => $model,
                'messages'    => [
                    ['role' => 'system', 'content' => 'You are an expert SEO content writer. Write unique, engaging content every time. Never repeat the same content twice. Use HTML when asked.'],
                    ['role' => 'user',   'content' => $uniquePrompt],
                ],
                'temperature' => 1.0,
                'max_tokens'  => $maxTokens,
            ]),
            CURLOPT_HTTPHEADER => [
                'Authorization: Bearer ' . $apiKey,
                'Content-Type: application/json',
            ],
            CURLOPT_TIMEOUT        => 60,
            CURLOPT_SSL_VERIFYPEER => false,
        ]);
        $response = json_decode(curl_exec($ch), true);
        $httpCode = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($httpCode !== 200) {
            return null;
        }

        return $response['choices'][0]['message']['content'] ?? null;
    }
}

if (!function_exists('generateWithGemini')) {
    function generateWithGemini(string $prompt, ?string $apiKey = null): ?string {
        $apiKey = $apiKey ?? (defined('GEMINI_API_KEY') ? GEMINI_API_KEY : '');
        if (empty($apiKey) || strpos($apiKey, 'your-') === 0) {
            return null;
        }

        $maxRetries = 3;
        $retryDelay = 2; // seconds

        for ($attempt = 1; $attempt <= $maxRetries; $attempt++) {
            $ch = curl_init("https://generativelanguage.googleapis.com/v1/models/gemini-2.5-flash:generateContent?key=" . $apiKey);
            $payload = json_encode([
                'contents' => [['parts' => [['text' => $prompt]]]]
            ]);
            curl_setopt_array($ch, [
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_POST           => true,
                CURLOPT_POSTFIELDS     => $payload,
                CURLOPT_HTTPHEADER     => ['Content-Type: application/json'],
                CURLOPT_TIMEOUT        => 30,
                CURLOPT_SSL_VERIFYPEER => false
            ]);
            
            $res = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);
            
            if ($httpCode === 200) {
                $json = json_decode($res, true);
                return $json['candidates'][0]['content']['parts'][0]['text'] ?? null;
            }

            if ($httpCode === 429 && $attempt < $maxRetries) {
                sleep($retryDelay * $attempt);
                continue;
            }
            break;
        }
        return null;
    }
}

if (!function_exists('generateWithGroq')) {
    function generateWithGroq(string $prompt, ?string $apiKey = null, ?string $model = null, int $maxTokens = 2000): ?string {
        $apiKey = $apiKey ?? (defined('GROQ_API_KEY') ? GROQ_API_KEY : '');
        if (empty($apiKey) || strpos($apiKey, 'your-') === 0) {
            return null;
        }

        // Check if xAI Grok (starts with xai-) or Groq (starts with gsk_ or other)
        $isXaiGrok = (strpos($apiKey, 'xai-') === 0);
        $endpoint = $isXaiGrok ? 'https://api.x.ai/v1/chat/completions' : 'https://api.groq.com/openai/v1/chat/completions';
        
        $candidateModels = [];
        if (!empty($model)) {
            $candidateModels[] = $model;
        }
        if ($isXaiGrok) {
            $candidateModels = array_unique(array_merge($candidateModels, ['grok-beta', 'grok-2']));
        } else {
            $defaultModel = defined('GROQ_MODEL') && !empty(GROQ_MODEL) ? GROQ_MODEL : 'qwen/qwen3.8-27b';
            $candidateModels = array_unique(array_merge($candidateModels, [$defaultModel, 'qwen/qwen3.8-27b', 'openai/gpt-oss-120b', 'openai/gpt-oss-20b', 'llama-3.3-70b-versatile', 'llama-3.1-8b-instant']));
        }

        $randomSeed = rand(100000, 999999);
        $uniquePrompt = $prompt . "\n\nGenerate completely unique content. Random seed: {$randomSeed}. Current timestamp: " . time() . ". Do not repeat any previous content.";

        foreach ($candidateModels as $tryModel) {
            $ch = curl_init($endpoint);
            curl_setopt_array($ch, [
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_POST           => true,
                CURLOPT_POSTFIELDS     => json_encode([
                    'model'       => $tryModel,
                    'messages'    => [
                        ['role' => 'system', 'content' => 'You are an expert SEO content writer. Write unique, engaging content every time. Never repeat the same content twice. Use HTML when asked.'],
                        ['role' => 'user',   'content' => $uniquePrompt],
                    ],
                    'temperature' => 1.0,
                    'max_tokens'  => $maxTokens,
                ]),
                CURLOPT_HTTPHEADER => [
                    'Authorization: Bearer ' . $apiKey,
                    'Content-Type: application/json',
                ],
                CURLOPT_TIMEOUT        => 30,
                CURLOPT_SSL_VERIFYPEER => false,
            ]);
            $res = curl_exec($ch);
            $httpCode = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);

            if ($httpCode === 200) {
                $response = json_decode($res, true);
                $content = $response['choices'][0]['message']['content'] ?? null;
                if (!empty($content)) {
                    return $content;
                }
            }
        }

        return null;
    }
}



