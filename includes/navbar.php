<?php
// Modern Universal Sidebar Navigation — SEO 80/20 System
$currentPage = basename($_SERVER['PHP_SELF'] ?? '');
$navUser = clean($_SESSION['username'] ?? 'User');
$navUserId = $_SESSION['user_id'] ?? 0;
$navUserRole = $_SESSION['role'] ?? 'client';

$navProjId = 0;
$navPendingCount = 0;
if ($navUserId > 0) {
    try {
        $db = getDB();
        $navProjStmt = $db->prepare("SELECT id FROM projects WHERE user_id=? LIMIT 1");
        $navProjStmt->execute([$navUserId]);
        $navProjId = (int)$navProjStmt->fetchColumn();
        
        // Count pending tasks in queue for this user's projects
        $navPendingStmt = $db->prepare("
            SELECT COUNT(*) FROM backlink_queue q 
            JOIN projects p ON q.project_id = p.id 
            WHERE p.user_id = ? AND q.status = 'pending'
        ");
        $navPendingStmt->execute([$navUserId]);
        $navPendingCount = (int)$navPendingStmt->fetchColumn();
    } catch (Exception $e) {}
}

// Fetch allowed menus for standard users (admins see everything)
$allowedMenus = null;
if ($navUserId > 0 && $navUserRole !== 'admin') {
    try {
        $db = getDB();
        $menuStmt = $db->prepare("SELECT allowed_menus FROM users WHERE id = ?");
        $menuStmt->execute([$navUserId]);
        $menuVal = $menuStmt->fetchColumn();
        if ($menuVal !== null && $menuVal !== '') {
            $allowedMenus = array_map('trim', explode(',', strtolower($menuVal)));
        }
    } catch (Exception $e) {}
}

if (!function_exists('isNavAllowed')) {
    function isNavAllowed($menuCode, $allowedMenus) {
        if ($allowedMenus === null) {
            return true;
        }
        return in_array(strtolower($menuCode), $allowedMenus);
    }
}
?>

<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
<style>
:root {
  --sidebar-w: 250px;
  --sidebar-collapsed: 76px;
  --app-bg: #f8fafc;
  --app-blue: #2563eb;
  --app-blue-soft: #eff6ff;
  --app-ink: #0f172a;
  --app-muted: #64748b;
  --sb-bg: #f8fafc;
  --sb-border: #edf2f7;
  --sb-text: #475569;
  --sb-muted: #94a3b8;
  --sb-hover: #f1f5f9;
}

@media (min-width: 992px) {
  body {
    padding-left: var(--sidebar-w) !important;
    transition: padding-left 0.22s ease;
  }
  body.sb-collapsed-body {
    padding-left: var(--sidebar-collapsed) !important;
  }
}

.universal-sidebar {
  width: var(--sidebar-w);
  height: 100vh;
  position: fixed;
  top: 0;
  left: 0;
  background: var(--sb-bg);
  border-right: 1px solid var(--sb-border);
  border-radius: 0;
  display: flex;
  flex-direction: column;
  padding: 22px 14px 14px;
  z-index: 1045;
  box-shadow: none;
  transition: width 0.22s ease, transform 0.3s ease;
  color: var(--sb-text);
}

.universal-sidebar.collapsed {
  width: var(--sidebar-collapsed);
  padding-left: 10px;
  padding-right: 10px;
}

@media (max-width: 991px) {
  .universal-sidebar {
    height: 100vh;
    top: 0;
    left: 0;
    border-radius: 0;
    transform: translateX(-110%);
  }
  .universal-sidebar.show {
    transform: translateX(0);
  }
}

.sb-brand {
  display: flex;
  align-items: center;
  gap: 10px;
  padding: 4px 36px 14px 6px;
  text-decoration: none;
  margin-bottom: 4px;
}
.sb-brand-icon {
  width: 40px;
  height: 40px;
  border-radius: 12px;
  background: var(--app-blue-soft);
  border: 1px solid #bfdbfe;
  color: var(--app-blue);
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 16px;
  flex-shrink: 0;
  box-shadow: none;
}
.sb-brand-text h4 {
  font-size: 17px;
  font-weight: 800;
  margin: 0;
  letter-spacing: -0.5px;
  color: var(--app-ink);
  line-height: 1.1;
}
.sb-brand-text span { color: var(--app-blue); }
.sb-brand-sub {
  font-size: 10px;
  color: var(--sb-muted);
  font-weight: 500;
  display: block;
}
.sb-collapse-btn {
  position: absolute;
  top: 28px;
  right: 10px;
  width: 26px;
  height: 26px;
  border: 1px solid #e2e8f0;
  background: #fff;
  color: var(--app-muted);
  border-radius: 8px;
  cursor: pointer;
  display: none;
  align-items: center;
  justify-content: center;
  z-index: 5;
  box-shadow: none;
  padding: 0;
}
@media (min-width: 992px) {
  .sb-collapse-btn { display: inline-flex; }
}
.sb-collapse-btn:hover { background: var(--app-blue-soft); color: var(--app-blue); border-color: #bfdbfe; }
.sb-collapse-btn i { font-size: 11px; }
.universal-sidebar.collapsed .sb-collapse-btn i { transform: rotate(180deg); }

.universal-sidebar.collapsed .sb-brand-text,
.universal-sidebar.collapsed .sb-hide-label,
.universal-sidebar.collapsed .sb-section,
.universal-sidebar.collapsed .sb-search input,
.universal-sidebar.collapsed .sb-user-meta,
.universal-sidebar.collapsed .sb-agent-meta {
  display: none !important;
}
.universal-sidebar.collapsed .sb-brand { justify-content: center; padding-left: 0; padding-right: 0; }
.universal-sidebar.collapsed .sb-search { justify-content: center; padding: 0; }
.universal-sidebar.collapsed .sb-link { justify-content: center; padding: 10px 0; }
.universal-sidebar.collapsed .sb-user-card { justify-content: center; }

.sb-search {
  display: flex;
  align-items: center;
  gap: 8px;
  background: #fff;
  border: 1px solid #e2e8f0;
  border-radius: 12px;
  padding: 8px 12px;
  margin: 0 4px 10px;
  color: var(--sb-muted);
}
.sb-search i { font-size: 13px; }
.sb-search input {
  flex: 1;
  background: transparent;
  border: 0;
  outline: none;
  color: var(--app-ink);
  font-size: 13px;
  min-width: 0;
}
.sb-search input::placeholder { color: #94a3b8; }

.sb-menu {
  display: flex;
  flex-direction: column;
  gap: 2px;
  flex-grow: 1;
  overflow-y: auto;
  padding: 0 2px 8px;
}
.sb-menu::-webkit-scrollbar { width: 4px; }
.sb-menu::-webkit-scrollbar-thumb { background: #e2e8f0; border-radius: 4px; }

.sb-section {
  font-size: 10px;
  letter-spacing: .12em;
  text-transform: uppercase;
  color: #94a3b8;
  font-weight: 700;
  padding: 14px 12px 6px;
}

.sb-link {
  display: flex;
  align-items: center;
  gap: 12px;
  padding: 10px 12px;
  border-radius: 10px;
  color: #475569;
  font-size: 13.5px;
  font-weight: 600;
  text-decoration: none;
  transition: all 0.15s ease;
  position: relative;
}
.sb-link:hover {
  background: var(--sb-hover);
  color: var(--app-ink);
}
.sb-link.active {
  background: #fff;
  color: var(--app-blue);
  font-weight: 700;
  box-shadow: 0 1px 2px rgba(15,23,42,.04);
  border: 1px solid #e8eef5;
}
.sb-link i {
  font-size: 15px;
  width: 18px;
  text-align: center;
  color: #94a3b8;
  flex-shrink: 0;
}
.sb-link:hover i { color: var(--app-ink); }
.sb-link.active i { color: var(--app-blue); }

.sb-link .badge-pending {
  background: #eff6ff;
  color: var(--app-blue);
  font-size: 10px;
  min-width: 22px;
  text-align: center;
  padding: 2px 7px;
  border-radius: 10px;
  margin-left: auto;
  font-weight: 700;
}
.sb-link.active .badge-pending {
  background: var(--app-blue-soft);
  color: var(--app-blue);
}

.universal-sidebar.collapsed .sb-link[data-tip]::after {
  content: attr(data-tip);
  position: absolute;
  left: calc(100% + 10px);
  top: 50%;
  transform: translateY(-50%);
  background: #fff;
  color: #111;
  font-size: 12px;
  font-weight: 600;
  padding: 6px 10px;
  border-radius: 8px;
  white-space: nowrap;
  opacity: 0;
  pointer-events: none;
  box-shadow: 0 6px 18px rgba(0,0,0,.18);
}
.universal-sidebar.collapsed .sb-link:hover::after { opacity: 1; }

.sb-bottom {
  margin-top: auto;
  padding-top: 8px;
  display: flex;
  flex-direction: column;
  gap: 6px;
}

.sb-agent-box {
  background: #f8fafc;
  border: 1px solid #e2e8f0;
  border-radius: 14px;
  padding: 10px 12px;
  display: flex;
  align-items: center;
  justify-content: space-between;
  text-decoration: none;
  color: #475569;
}
.sb-agent-box:hover { background: #f1f5f9; color: var(--app-ink); }
.sb-agent-dot {
  width: 8px;
  height: 8px;
  border-radius: 50%;
  background: #10b981;
  display: inline-block;
  box-shadow: 0 0 0 2px rgba(16,185,129,.2);
}
.sb-agent-box strong, .sb-agent-box small { color: inherit; }

.sb-user-card {
  display: flex;
  align-items: center;
  justify-content: space-between;
  padding: 8px 8px;
  background: transparent;
  border: 0;
  border-radius: 14px;
}
.sb-user-card:hover { background: var(--sb-hover); }
.sb-user-avatar {
  width: 34px;
  height: 34px;
  border-radius: 50%;
  background: var(--app-blue);
  color: #fff;
  display: flex;
  align-items: center;
  justify-content: center;
  font-weight: 700;
  font-size: 13px;
  overflow: hidden;
}
.sb-user-card strong { color: var(--app-ink); }
.sb-user-card small { color: var(--sb-muted); }
.sb-user-card .fa-ellipsis { color: var(--sb-muted); font-size: 14px; }
.sb-user-card.dropdown-toggle::after { display: none; }

.dropdown-menu.sb-dark-menu {
  background: #fff;
  color: var(--app-ink);
  border: 1px solid var(--sb-border);
  border-radius: 12px;
}
.dropdown-menu.sb-dark-menu .dropdown-item { color: #334155; }
.dropdown-menu.sb-dark-menu .dropdown-item:hover { background: #f8fafc; color: var(--app-ink); }
.dropdown-menu.sb-dark-menu .dropdown-divider { border-color: #e2e8f0; }

body {
  background: var(--app-bg) !important;
  font-family: 'Plus Jakarta Sans', system-ui, -apple-system, sans-serif;
  color: #475569;
  -webkit-font-smoothing: antialiased;
}
@media (min-width: 992px) {
  body {
    padding: 0 0 0 var(--sidebar-w) !important;
    min-height: 100vh;
  }
  body.sb-collapsed-body {
    padding-left: var(--sidebar-collapsed) !important;
  }
}
body > .container-fluid,
body > .container,
body > .dashboard-layout,
body > .main-content {
  max-width: none;
}
body > .dashboard-layout {
  display: block;
  min-height: auto;
}
body > .dashboard-layout > .sidebar { display: none !important; }
.main-content {
  width: 100% !important;
  max-width: none !important;
  padding: 24px 32px 32px !important;
}

.card {
  border: 0 !important;
  border-radius: 18px !important;
  background: #fff !important;
  box-shadow: 0 1px 2px rgba(28,28,30,.04), 0 8px 24px rgba(28,28,30,.04) !important;
}
.card.bg-light,
.card.border-info,
.card.border-success,
.card.border-primary {
  background: #fff !important;
}
.card-header {
  background: transparent !important;
  border-bottom: 1px solid #f0f0f2 !important;
  color: #1c1c1e !important;
  border-radius: 18px 18px 0 0 !important;
  font-weight: 700;
}
.card-header.bg-success,
.card-header.bg-info,
.card-header.bg-primary,
.card-header.bg-dark,
.card-header.text-white {
  background: #f6f6f8 !important;
  color: #1c1c1e !important;
}
.card-header.bg-success *,
.card-header.bg-info *,
.card-header.text-white * {
  color: #1c1c1e !important;
}

.alert {
  border: 0 !important;
  border-radius: 16px !important;
}
.alert-success,
.alert-info {
  background: #fff !important;
  color: #1c1c1e !important;
  box-shadow: 0 1px 2px rgba(28,28,30,.04), 0 8px 24px rgba(28,28,30,.04);
}

.btn {
  border-radius: 12px !important;
  font-weight: 600;
  box-shadow: none !important;
}
.btn-primary,
.btn-info,
.btn-dark {
  background: var(--app-blue) !important;
  border-color: var(--app-blue) !important;
  color: #fff !important;
}
.btn-primary:hover,
.btn-info:hover,
.btn-dark:hover {
  background: #1d4ed8 !important;
  border-color: #1d4ed8 !important;
  color: #fff !important;
}
.btn-success {
  background: #10b981 !important;
  border-color: #10b981 !important;
  color: #fff !important;
}
.btn-outline-secondary,
.btn-secondary {
  background: #fff !important;
  border: 1px solid #e4e4e7 !important;
  color: #1c1c1e !important;
}
.btn-outline-secondary:hover,
.btn-secondary:hover {
  background: #f4f4f5 !important;
  color: #1c1c1e !important;
}

.form-select, .form-control {
  border-radius: 12px !important;
  border-color: #e4e4e7 !important;
  background: #fff;
}
.form-select:focus, .form-control:focus {
  border-color: var(--app-blue) !important;
  box-shadow: 0 0 0 3px rgba(37,99,235,.12) !important;
}

.badge.bg-primary,
.badge.bg-info {
  background: var(--app-blue) !important;
}
.badge.bg-secondary {
  background: #e8e8ed !important;
  color: #1c1c1e !important;
}

h1, h2, h3, h4, h5 {
  letter-spacing: -0.03em;
  color: var(--app-ink);
}

.mobile-topbar { display: none; }
@media (max-width: 991px) {
  .mobile-topbar {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 12px 18px;
    background: #fff;
    border-bottom: 1px solid #edf2f7;
    position: sticky;
    top: 0;
    z-index: 1030;
    color: var(--app-ink);
  }
  .mobile-topbar a { color: var(--app-ink); }
}
</style>

<div class="mobile-topbar">
  <div class="d-flex align-items-center gap-2">
    <button class="btn btn-sm btn-light border py-1 px-2" type="button" onclick="toggleUniversalSidebar()">
      <i class="fas fa-bars"></i>
    </button>
    <a href="dashboard.php" class="text-decoration-none fw-bold" style="font-size: 16px; color:#0f172a;">
      SEO <span style="color:#2563eb;">80/20</span>
    </a>
  </div>
  <div class="d-flex align-items-center gap-2">
    <?php if ($navPendingCount > 0): ?>
      <a href="submission-manager.php" class="badge bg-secondary text-decoration-none p-1 px-2">
        <i class="fas fa-clock me-1"></i><?= $navPendingCount ?>
      </a>
    <?php endif; ?>
    <a href="logout.php" class="btn btn-sm btn-outline-light py-0 px-2" title="Logout">
      <i class="fas fa-sign-out-alt"></i>
    </a>
  </div>
</div>

<aside class="universal-sidebar" id="universalAppSidebar">
  <div class="sb-brand">
    <a href="dashboard.php" class="d-flex align-items-center gap-2 text-decoration-none">
      <div class="sb-brand-icon">
        <i class="fas fa-chart-line"></i>
      </div>
      <div class="sb-brand-text">
        <h4>SEO <span>80/20</span></h4>
      </div>
    </a>
    <button type="button" class="sb-collapse-btn" id="sbCollapseBtn" title="Expand sidebar" onclick="toggleSidebarCollapse()">
      <i class="fas fa-chevron-left"></i>
    </button>
  </div>

  <label class="sb-search">
    <i class="fas fa-search"></i>
    <input type="search" id="sbMenuSearch" placeholder="Search" oninput="filterSidebarMenu(this.value)" autocomplete="off">
  </label>

  <nav class="sb-menu" id="sbMenuList">
    <div class="sb-section">Dashboard</div>
    <?php if (isNavAllowed('dashboard', $allowedMenus)): ?>
    <a href="dashboard.php" data-tip="Overview" class="sb-link <?= $currentPage === 'dashboard.php' ? 'active' : '' ?>">
      <i class="fas fa-th-large"></i>
      <span class="sb-hide-label">Overview</span>
    </a>
    <?php endif; ?>

    <?php if ($navProjId > 0): ?>
    <a href="client-profile.php?id=<?= $navProjId ?>" data-tip="Client Profile" class="sb-link <?= $currentPage === 'client-profile.php' ? 'active' : '' ?>">
      <i class="fas fa-id-card"></i>
      <span class="sb-hide-label">Client Profile</span>
    </a>
    <?php endif; ?>

    <?php if (isNavAllowed('add-project', $allowedMenus)): ?>
    <a href="add-project.php" data-tip="Add Project" class="sb-link <?= $currentPage === 'add-project.php' ? 'active' : '' ?>">
      <i class="fas fa-folder-plus"></i>
      <span class="sb-hide-label">Add Project</span>
    </a>
    <?php endif; ?>

    <?php if (isNavAllowed('submissions', $allowedMenus)): ?>
    <a href="submission-manager.php" data-tip="Submissions" class="sb-link <?= $currentPage === 'submission-manager.php' ? 'active' : '' ?>">
      <i class="fas fa-paper-plane"></i>
      <span class="sb-hide-label">Submissions</span>
      <?php if ($navPendingCount > 0): ?>
        <span class="badge-pending" title="<?= $navPendingCount ?> pending tasks"><?= $navPendingCount ?></span>
      <?php endif; ?>
    </a>
    <?php endif; ?>

    <a href="daily-reports.php" data-tip="Daily Reports" class="sb-link <?= $currentPage === 'daily-reports.php' ? 'active' : '' ?>">
      <i class="fas fa-chart-bar"></i>
      <span class="sb-hide-label">Daily Reports</span>
    </a>

    <a href="rank-tracker.php" data-tip="Keywords" class="sb-link <?= $currentPage === 'rank-tracker.php' ? 'active' : '' ?>">
      <i class="fas fa-chart-line"></i>
      <span class="sb-hide-label">Keywords & Rankings</span>
    </a>

    <div class="sb-section">Tools</div>
    <?php if (isNavAllowed('client-pitch', $allowedMenus)): ?>
    <a href="client-pitch.php" data-tip="Client Pitch" class="sb-link <?= $currentPage === 'client-pitch.php' ? 'active' : '' ?>">
      <i class="fas fa-file-invoice"></i>
      <span class="sb-hide-label">Client Pitch</span>
    </a>
    <?php endif; ?>

    <?php if (isNavAllowed('ai-workflow', $allowedMenus)): ?>
    <a href="workflow-dashboard.php" data-tip="AI Workflow" class="sb-link <?= $currentPage === 'workflow-dashboard.php' ? 'active' : '' ?>">
      <i class="fas fa-sync"></i>
      <span class="sb-hide-label">AI Workflow</span>
    </a>
    <?php endif; ?>

    <?php if (isNavAllowed('auto-schedule', $allowedMenus)): ?>
    <a href="schedule-setup.php" data-tip="Auto-Schedule" class="sb-link <?= $currentPage === 'schedule-setup.php' ? 'active' : '' ?>">
      <i class="fas fa-clock"></i>
      <span class="sb-hide-label">Auto-Schedule</span>
    </a>
    <?php endif; ?>

    <?php if (isNavAllowed('cost-ranking', $allowedMenus)): ?>
    <a href="seo-costs.php" data-tip="Cost & Ranking" class="sb-link <?= $currentPage === 'seo-costs.php' ? 'active' : '' ?>">
      <i class="fas fa-rupee-sign"></i>
      <span class="sb-hide-label">Cost & Ranking</span>
    </a>
    <?php endif; ?>

    <?php if (isNavAllowed('google-console', $allowedMenus)): ?>
    <a href="google-integration.php" data-tip="Google Console" class="sb-link <?= $currentPage === 'google-integration.php' ? 'active' : '' ?>">
      <i class="fab fa-google"></i>
      <span class="sb-hide-label">Google Console</span>
    </a>
    <?php endif; ?>

    <?php if (isNavAllowed('git-push-agent', $allowedMenus)): ?>
    <a href="git-deployer.php" data-tip="Git Push Agent" class="sb-link <?= $currentPage === 'git-deployer.php' ? 'active' : '' ?>">
      <i class="fab fa-github"></i>
      <span class="sb-hide-label">Git Push Agent</span>
    </a>
    <?php endif; ?>

    <?php if (isNavAllowed('api-keys', $allowedMenus)): ?>
    <a href="api-setup.php" data-tip="API Keys" class="sb-link <?= $currentPage === 'api-setup.php' ? 'active' : '' ?>">
      <i class="fas fa-key"></i>
      <span class="sb-hide-label">API Keys</span>
    </a>
    <?php endif; ?>

    <?php if (($navUserRole === 'admin') && isNavAllowed('admin-panel', $allowedMenus)): ?>
    <a href="admin-dashboard.php" data-tip="Admin Panel" class="sb-link <?= $currentPage === 'admin-dashboard.php' ? 'active' : '' ?>">
      <i class="fas fa-user-shield"></i>
      <span class="sb-hide-label">Admin Panel</span>
    </a>
    <?php endif; ?>
  </nav>

  <div class="sb-bottom">
    <?php if (isNavAllowed('how-to-use', $allowedMenus)): ?>
    <a href="how-to-use.php" data-tip="How to Use" class="sb-link <?= $currentPage === 'how-to-use.php' ? 'active' : '' ?>">
      <i class="fas fa-cog"></i>
      <span class="sb-hide-label">Settings</span>
    </a>
    <?php endif; ?>

    <a href="download-agent.php" class="sb-agent-box" title="PC Agent">
      <div class="d-flex align-items-center gap-2">
        <span class="sb-agent-dot"></span>
        <div class="sb-agent-meta">
          <strong class="d-block" style="font-size: 11.5px; line-height: 1.1;">PC Agent</strong>
          <small class="fw-bold" style="font-size: 10px; color:#10b981;">Online</small>
        </div>
      </div>
    </a>

    <div class="dropdown">
      <div class="sb-user-card dropdown-toggle" role="button" data-bs-toggle="dropdown" aria-expanded="false" style="cursor: pointer;" data-bs-display="static">
        <div class="d-flex align-items-center gap-2">
          <div class="sb-user-avatar">
            <?= strtoupper(substr($navUser, 0, 1)) ?>
          </div>
          <div class="sb-user-meta">
            <strong class="d-block" style="font-size: 12px; line-height: 1.2;"><?= $navUser ?></strong>
            <small style="font-size: 10.5px; text-transform: capitalize;"><?= $navUserRole ?></small>
          </div>
        </div>
        <i class="fas fa-ellipsis sb-hide-label"></i>
      </div>
      <ul class="dropdown-menu dropdown-menu-end shadow-sm sb-dark-menu" style="font-size: 13px;">
        <li><a class="dropdown-item" href="client-profile.php"><i class="fas fa-id-card me-2"></i>Profile</a></li>
        <li><a class="dropdown-item" href="setup.php"><i class="fas fa-cog me-2"></i>System Check</a></li>
        <li><hr class="dropdown-divider"></li>
        <li><a class="dropdown-item text-danger" href="logout.php"><i class="fas fa-sign-out-alt me-2"></i>Logout</a></li>
      </ul>
    </div>
  </div>
</aside>

<script>
function toggleUniversalSidebar() {
  const sb = document.getElementById('universalAppSidebar');
  if (sb) sb.classList.toggle('show');
}
function applySidebarCollapse(on) {
  const sb = document.getElementById('universalAppSidebar');
  if (!sb) return;
  sb.classList.toggle('collapsed', on);
  document.body.classList.toggle('sb-collapsed-body', on);
  localStorage.setItem('seo_sidebar_collapsed', on ? '1' : '0');
  const btn = document.getElementById('sbCollapseBtn');
  if (btn) btn.title = on ? 'Sidebar bada karo' : 'Sidebar chhota karo';
}
function toggleSidebarCollapse() {
  const sb = document.getElementById('universalAppSidebar');
  applySidebarCollapse(!(sb && sb.classList.contains('collapsed')));
}
function filterSidebarMenu(q) {
  q = (q || '').toLowerCase().trim();
  document.querySelectorAll('#sbMenuList .sb-link').forEach(function (el) {
    const t = (el.innerText || '').toLowerCase();
    el.style.display = !q || t.indexOf(q) !== -1 ? '' : 'none';
  });
}
(function () {
  if (localStorage.getItem('seo_sidebar_collapsed') === '1' && window.innerWidth >= 992) {
    applySidebarCollapse(true);
  }
  const brand = document.querySelector('#universalAppSidebar .sb-brand a');
  if (brand) {
    brand.addEventListener('click', function (e) {
      const sb = document.getElementById('universalAppSidebar');
      if (sb && sb.classList.contains('collapsed') && window.innerWidth >= 992) {
        e.preventDefault();
        applySidebarCollapse(false);
      }
    });
  }
})();
</script>
