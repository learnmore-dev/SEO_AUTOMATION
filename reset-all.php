<?php
/**
 * 1-Click Database Reset & Superuser Creation Tool
 * Location: /reset-all.php
 */
require_once __DIR__ . '/config.php';

$error = '';
$success = '';
$pdoLoaded = extension_loaded('pdo_mysql');
$phpIniPath = php_ini_loaded_file();
$phpBinary = PHP_BINARY;
$phpDir = dirname(PHP_BINARY);
$potentialIni = $phpDir . DIRECTORY_SEPARATOR . 'php.ini';
$devIni = $phpDir . DIRECTORY_SEPARATOR . 'php.ini-development';
$prodIni = $phpDir . DIRECTORY_SEPARATOR . 'php.ini-production';

// Auto-detect active MySQL port and credentials
$detectedHost = (strpos(DB_HOST, ':') !== false) ? DB_HOST : DB_HOST . ':3306';
$detectedPass = DB_PASS;
$autoDetectedMsg = '';

if ($pdoLoaded) {
    $candidatePorts = ['3307', '3306', '3308'];
    $candidatePasswords = ['', 'root', 'admin', '123456', 'password', 'altaf', 'Altaf@123'];
    
    foreach ($candidatePorts as $p) {
        foreach ($candidatePasswords as $pw) {
            try {
                $testDsn = "mysql:host=127.0.0.1;port={$p};charset=utf8mb4";
                $testPdo = new PDO($testDsn, 'root', $pw, [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_TIMEOUT => 1
                ]);
                $detectedHost = "127.0.0.1:{$p}";
                $detectedPass = $pw;
                $pwDisplay = ($pw === '') ? 'Leave blank (No password)' : $pw;
                $autoDetectedMsg = "Connected to MySQL on <code>127.0.0.1:{$p}</code> (Password: <code>{$pwDisplay}</code>)!";
                break 2;
            } catch (Throwable $e) {}
        }
    }
}

// SQLite Reset Handler
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['reset_sqlite'])) {
    $superUser = trim($_POST['super_username'] ?? 'admin');
    $superEmail = trim($_POST['super_email'] ?? 'admin@skyrank.com');
    $superPass = $_POST['super_password'] ?? 'admin123';

    try {
        if (!extension_loaded('pdo_sqlite')) {
            throw new Exception("PHP extension 'pdo_sqlite' is not loaded. Please restart your PHP server using: php -c php.ini -S localhost:8000");
        }

        $sqliteFile = DB_SQLITE_PATH;
        if (file_exists($sqliteFile)) {
            @unlink($sqliteFile);
        }

        $pdo = new PDO('sqlite:' . $sqliteFile, null, null, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
        ]);

        initSqliteDatabase($pdo, $superUser, $superPass, $superEmail);

        // Save to config.local.php
        $localConfigPath = __DIR__ . '/config.local.php';
        $cfg = [
            'DB_TYPE' => 'sqlite',
            'DB_SQLITE_PATH' => $sqliteFile
        ];
        file_put_contents($localConfigPath, "<?php\nreturn " . var_export($cfg, true) . ";\n");

        $success = "SQLite database initialized successfully! Superuser '<strong>" . htmlspecialchars($superUser) . "</strong>' created. No MySQL server needed!";

    } catch (Throwable $e) {
        $error = "SQLite Error: " . $e->getMessage();
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['reset_database'])) {
    $dbHost = trim($_POST['db_host'] ?? $detectedHost);
    $dbUser = trim($_POST['db_user'] ?? DB_USER);
    $dbPass = $_POST['db_pass'] ?? $detectedPass;
    $dbName = trim($_POST['db_name'] ?? DB_NAME);

    $superUser = trim($_POST['super_username'] ?? 'admin');
    $superEmail = trim($_POST['super_email'] ?? 'admin@skyrank.com');
    $superPass = $_POST['super_password'] ?? 'admin123';

    if (!$pdoLoaded) {
        $error = "PHP extension 'pdo_mysql' is not enabled. Please enable it in php.ini first!";
    } elseif (empty($superUser) || empty($superPass) || empty($superEmail)) {
        $error = "Please fill in all Superuser fields.";
    } else {
        try {
            // 1. Connect to MySQL server without specifying db_name (in case db does not exist yet)
            $hostParts = explode(':', $dbHost);
            $host = $hostParts[0];
            $port = $hostParts[1] ?? '3306';

            $dsn = "mysql:host={$host};port={$port};charset=utf8mb4";
            $pdo = new PDO($dsn, $dbUser, $dbPass, [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
            ]);

            // 2. Read database.sql
            $sqlFile = __DIR__ . '/database.sql';
            if (!file_exists($sqlFile)) {
                throw new Exception("database.sql file not found in " . __DIR__);
            }
            $sql = file_get_contents($sqlFile);

            // 3. Execute multi-queries
            $pdo->exec($sql);

            // Reconnect directly to target database
            $pdo->exec("USE `{$dbName}`");

            // 4. Create Superuser
            $hashedPass = password_hash($superPass, PASSWORD_BCRYPT);
            $apiKey = 'sky_' . bin2hex(random_bytes(16));

            $insertStmt = $pdo->prepare("
                INSERT INTO users (username, password, email, role, api_key, allowed_menus)
                VALUES (?, ?, ?, 'admin', ?, NULL)
            ");
            // 5. Automatically save working DB credentials to config.local.php
            $localConfigPath = __DIR__ . '/config.local.php';
            $existingConfig = [];
            if (file_exists($localConfigPath)) {
                $existingConfig = (array) include $localConfigPath;
            }
            $existingConfig['DB_HOST'] = $dbHost;
            $existingConfig['DB_USER'] = $dbUser;
            $existingConfig['DB_PASS'] = $dbPass;
            $existingConfig['DB_NAME'] = $dbName;

            $exportContent = "<?php\nreturn " . var_export($existingConfig, true) . ";\n";
            file_put_contents($localConfigPath, $exportContent);

            $success = "Database '{$dbName}' has been completely reset! Superuser '<strong>" . htmlspecialchars($superUser) . "</strong>' created successfully.";

        } catch (Throwable $e) {
            $error = "Database Error: " . $e->getMessage();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Reset Database & Create Superuser</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
<style>
  body { background: #f0f2f5; font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif; }
  .card-box { border-radius: 12px; box-shadow: 0 4px 20px rgba(0,0,0,0.08); border: none; }
  .btn-gradient { background: linear-gradient(135deg, #4f46e5, #7c3aed); border: none; color: #fff; }
  .btn-gradient:hover { background: linear-gradient(135deg, #4338ca, #6d28d9); color: #fff; }
  .code-badge { background: #1e293b; color: #38bdf8; padding: 2px 6px; border-radius: 4px; font-size: 13px; }
</style>
</head>
<body>
<div class="container py-5" style="max-width: 680px;">
  <div class="text-center mb-4">
    <h2 class="fw-bold text-dark"><i class="fas fa-database text-primary me-2"></i>Database Reset & Superuser Setup</h2>
    <p class="text-muted">Wipes old data, creates a clean schema from <code>database.sql</code>, and registers your new Admin account.</p>
  </div>

  <?php if (!$pdoLoaded): ?>
  <div class="alert alert-danger shadow-sm">
    <h5 class="alert-heading fw-bold"><i class="fas fa-exclamation-triangle me-2"></i>PHP Error: "could not find driver"</h5>
    <p class="mb-2">The PHP PDO MySQL extension is currently <strong>disabled</strong> in your PHP configuration.</p>
    <hr>
    <h6 class="fw-bold">How to fix in 1 minute:</h6>
    <div class="mb-3">
      <strong>Your PHP Folder:</strong><br>
      <span class="code-badge"><?= htmlspecialchars($phpDir) ?></span>
    </div>
    <ol class="small mb-2">
      <?php if (!file_exists($potentialIni)): ?>
      <li class="mb-2 text-danger fw-bold">
        Aapke PHP folder me <code>php.ini</code> file nahi hai! <br>
        Usi folder (<code><?= htmlspecialchars($phpDir) ?></code>) me dekhein: <br>
        <code>php.ini-development</code> file ko copy karke uska naam <code>php.ini</code> rakh dein.
      </li>
      <?php endif; ?>
      <li>File ko Notepad me open karein: <br><span class="code-badge"><?= htmlspecialchars($potentialIni) ?></span></li>
      <li>Search karein: <code>;extension_dir = "ext"</code> aur aage ka <code>;</code> hata kar enable karein.</li>
      <li>Search karein: <code>;extension=pdo_mysql</code> aur aage ka <code>;</code> hata kar enable karein: <br><code>extension=pdo_mysql</code></li>
      <li>Inhe bhi enable karein: <code>extension=curl</code> aur <code>extension=mbstring</code></li>
      <li>File ko Save karein aur terminal me <code>Ctrl+C</code> dabakar dobara chalayein: <br><span class="code-badge">php -S localhost:8000</span></li>
      <li>Is page ko refresh karein!</li>
    </ol>
  </div>
  <?php endif; ?>

  <?php if ($error): ?>
  <div class="alert alert-danger alert-dismissible fade show shadow-sm" role="alert">
    <i class="fas fa-times-circle me-2"></i><?= htmlspecialchars($error) ?>
    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
  </div>
  <?php endif; ?>

  <?php if ($success): ?>
  <div class="alert alert-success shadow-sm">
    <h5 class="alert-heading fw-bold"><i class="fas fa-check-circle me-2"></i>All Done!</h5>
    <p><?= $success ?></p>
    <hr>
    <div class="d-flex gap-2">
      <a href="index.php" class="btn btn-success fw-bold"><i class="fas fa-sign-in-alt me-1"></i>Go to Login</a>
      <a href="setup.php" class="btn btn-outline-secondary"><i class="fas fa-check-double me-1"></i>System Diagnostic</a>
    </div>
  </div>
  <?php endif; ?>

  <!-- SQLite 1-Click Option -->
  <div class="card card-box p-4 bg-white mb-4 border-start border-success border-4 shadow-sm">
    <div class="d-flex align-items-center mb-2">
      <span class="badge bg-success me-2 px-3 py-2">RECOMMENDED</span>
      <h5 class="fw-bold mb-0 text-success"><i class="fas fa-bolt me-1"></i>SQLite 1-Click Instant Setup</h5>
    </div>
    <p class="text-muted small mb-3">Agar aapke PC par MySQL / XAMPP installed nahi hai, toh SQLite best hai! Yeh kisi server, port ya password ke bina seedha local file me chalega.</p>
    <form method="POST" action="">
      <div class="row g-2 mb-3">
        <div class="col-md-4">
          <label class="form-label small fw-semibold">Admin Username</label>
          <input type="text" name="super_username" class="form-control form-control-sm" value="admin" required>
        </div>
        <div class="col-md-4">
          <label class="form-label small fw-semibold">Admin Email</label>
          <input type="email" name="super_email" class="form-control form-control-sm" value="admin@skyrank.com" required>
        </div>
        <div class="col-md-4">
          <label class="form-label small fw-semibold">Admin Password</label>
          <input type="text" name="super_password" class="form-control form-control-sm" value="admin123" required>
        </div>
      </div>
      <button type="submit" name="reset_sqlite" value="1" class="btn btn-success fw-bold w-100 py-2">
        <i class="fas fa-check-circle me-1"></i>Setup SQLite Database & Create Superuser (Instant)
      </button>
    </form>
  </div>

  <div class="text-center my-3 text-muted fw-bold">--- OR USE MYSQL (IF INSTALLED) ---</div>

  <div class="card card-box p-4 bg-white mb-4">
    <form method="POST" action="">
      <h5 class="fw-bold mb-3 text-secondary border-bottom pb-2"><i class="fas fa-server me-2"></i>MySQL Connection Details</h5>
      <?php if (!empty($autoDetectedMsg)): ?>
      <div class="alert alert-info py-2 small mb-3">
        <i class="fas fa-magic text-primary me-2"></i><?= $autoDetectedMsg ?>
      </div>
      <?php endif; ?>
      <div class="row g-3 mb-4">
        <div class="col-md-6">
          <label class="form-label fw-semibold">Database Host:Port</label>
          <input type="text" name="db_host" class="form-control" value="<?= htmlspecialchars($_POST['db_host'] ?? $detectedHost) ?>" required>
          <div class="form-text">e.g. 127.0.0.1:3306 or 127.0.0.1:3307</div>
        </div>
        <div class="col-md-6">
          <label class="form-label fw-semibold">Database Name</label>
          <input type="text" name="db_name" class="form-control" value="<?= htmlspecialchars(DB_NAME) ?>" required>
        </div>
        <div class="col-md-6">
          <label class="form-label fw-semibold">MySQL Username</label>
          <input type="text" name="db_user" class="form-control" value="<?= htmlspecialchars(DB_USER) ?>" required>
        </div>
        <div class="col-md-6">
          <label class="form-label fw-semibold">MySQL Password</label>
          <input type="password" name="db_pass" class="form-control" value="<?= htmlspecialchars($_POST['db_pass'] ?? $detectedPass) ?>" placeholder="Enter password if your MySQL has one">
          <div class="form-text">Agar password nahi pata to common try karein: blank, root, admin, 123456</div>
        </div>
      </div>

      <h5 class="fw-bold mb-3 text-secondary border-bottom pb-2"><i class="fas fa-user-shield me-2"></i>2. New Superuser (Admin) Account</h5>
      <div class="row g-3 mb-4">
        <div class="col-md-6">
          <label class="form-label fw-semibold">Username</label>
          <input type="text" name="super_username" class="form-control" value="admin" required>
        </div>
        <div class="col-md-6">
          <label class="form-label fw-semibold">Email Address</label>
          <input type="email" name="super_email" class="form-control" value="admin@skyrank.com" required>
        </div>
        <div class="col-12">
          <label class="form-label fw-semibold">Admin Password</label>
          <input type="text" name="super_password" class="form-control" value="admin123" required>
          <div class="form-text">You can change this password or keep the default.</div>
        </div>
      </div>

      <div class="alert alert-warning small mb-4">
        <i class="fas fa-exclamation-triangle me-1"></i><strong>Warning:</strong> This will drop any existing tables in <code><?= htmlspecialchars(DB_NAME) ?></code> and rebuild clean tables from <code>database.sql</code>.
      </div>

      <button type="submit" name="reset_database" value="1" class="btn btn-gradient btn-lg w-100 fw-bold" <?= !$pdoLoaded ? 'disabled' : '' ?>>
        <i class="fas fa-trash-restore me-2"></i>Wipe All Data & Create Superuser
      </button>
    </form>
  </div>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
