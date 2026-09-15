<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width,initial-scale=1.0">
  <title><?= e($pageTitle ?? 'Amazing Feet') ?> · <?= APP_NAME ?></title>
  <link rel="stylesheet" href="<?= BASE_PATH ?>/assets/css/app.css">
  <link rel="stylesheet" href="<?= BASE_PATH ?>/assets/css/app-compact.css">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css" crossorigin="anonymous" referrerpolicy="no-referrer">
  <link rel="icon" href="<?= e(af_favicon_data_uri()) ?>">
  <?= $extraHead ?? '' ?>
  <script>
    (function () {
      try {
        if (localStorage.getItem('af_sidebar') === 'collapsed' && window.innerWidth > 768) {
          document.documentElement.classList.add('sidebar-collapsed');
        }
      } catch (e) {}
    })();
  </script>
</head>
<body>
<div class="shell" id="shell">
  <div class="sidebar-backdrop" id="sidebarBackdrop" hidden></div>

  <!-- Sidebar -->
  <aside class="sidebar" id="sidebar" aria-label="Main navigation">
    <div class="sidebar-logo">
      <div class="logo-mark" aria-hidden="true"><i class="fa-solid fa-shoe-prints logo-mark-svg"></i></div>
      <div class="sidebar-brand">
        <div class="app-name">Amazing Feet</div>
        <div class="app-sub"><?= ucfirst($_SESSION['user_role']??'') ?> Panel</div>
      </div>
      <button type="button" class="sidebar-collapse-btn no-print" id="sidebarCollapseBtn" title="Collapse sidebar" aria-label="Collapse sidebar">
        <i class="fa-solid fa-angles-left" aria-hidden="true"></i>
      </button>
    </div>

    <nav class="sidebar-nav">
      <?php if (isOwner()): ?>
      <div class="nav-group">
        <span class="nav-label">Overview</span>
        <a href="<?= BASE_PATH ?>/dashboard" class="nav-item <?= str_ends_with($cp??'','/dashboard')?'active':'' ?>" title="Dashboard">
          <span class="ni"><i class="fa-solid fa-chart-pie" aria-hidden="true"></i></span>
          <span class="nav-text">Dashboard</span>
        </a>
      </div>
      <div class="nav-group">
        <span class="nav-label">Sales</span>
        <a href="<?= BASE_PATH ?>/pos" class="nav-item <?= str_contains($cp??'','/pos')?'active':'' ?>" title="POS Terminal">
          <span class="ni"><i class="fa-solid fa-cart-shopping" aria-hidden="true"></i></span>
          <span class="nav-text">POS Terminal</span>
        </a>
        <a href="<?= BASE_PATH ?>/sales" class="nav-item <?= str_contains($cp??'','/sales')?'active':'' ?>" title="All Sales">
          <span class="ni"><i class="fa-solid fa-clipboard-list" aria-hidden="true"></i></span>
          <span class="nav-text">All Sales</span>
        </a>
      </div>
      <div class="nav-group">
        <span class="nav-label">Inventory</span>
        <a href="<?= BASE_PATH ?>/products" class="nav-item <?= str_contains($cp??'','/products')?'active':'' ?>" title="Products">
          <span class="ni"><i class="fa-solid fa-box" aria-hidden="true"></i></span>
          <span class="nav-text">Products</span>
          <?php if (($alertCount??0) > 0): ?><span class="nav-badge"><?= $alertCount ?></span><?php endif; ?>
        </a>
        <a href="<?= BASE_PATH ?>/categories" class="nav-item <?= str_contains($cp??'','/categories')?'active':'' ?>" title="Categories">
          <span class="ni"><i class="fa-solid fa-tag" aria-hidden="true"></i></span>
          <span class="nav-text">Categories</span>
        </a>
      </div>
      <div class="nav-group">
        <span class="nav-label">Reports</span>
        <a href="<?= BASE_PATH ?>/targets" class="nav-item <?= str_contains($cp??'','/targets')?'active':'' ?>" title="Sales Targets">
          <span class="ni"><i class="fa-solid fa-bullseye" aria-hidden="true"></i></span>
          <span class="nav-text">Sales Targets</span>
        </a>
        <a href="<?= BASE_PATH ?>/reports/daily" class="nav-item <?= str_contains($cp??'','/reports/daily')?'active':'' ?>" title="Daily Report">
          <span class="ni"><i class="fa-solid fa-calendar-day" aria-hidden="true"></i></span>
          <span class="nav-text">Daily Report</span>
        </a>
        <a href="<?= BASE_PATH ?>/reports/weekly" class="nav-item <?= str_contains($cp??'','/reports/weekly')?'active':'' ?>" title="Weekly Report">
          <span class="ni"><i class="fa-solid fa-calendar-week" aria-hidden="true"></i></span>
          <span class="nav-text">Weekly Report</span>
        </a>
        <a href="<?= BASE_PATH ?>/reports/monthly" class="nav-item <?= str_contains($cp??'','/reports/monthly')?'active':'' ?>" title="Monthly Report">
          <span class="ni"><i class="fa-solid fa-calendar-days" aria-hidden="true"></i></span>
          <span class="nav-text">Monthly Report</span>
        </a>
        <a href="<?= BASE_PATH ?>/reports/staff" class="nav-item <?= str_contains($cp??'','/reports/staff')?'active':'' ?>" title="Staff Report">
          <span class="ni"><i class="fa-solid fa-user" aria-hidden="true"></i></span>
          <span class="nav-text">Staff Report</span>
        </a>
        <a href="<?= BASE_PATH ?>/reports/locations" class="nav-item <?= str_contains($cp??'','/reports/locations')?'active':'' ?>" title="Location Report">
          <span class="ni"><i class="fa-solid fa-location-dot" aria-hidden="true"></i></span>
          <span class="nav-text">Location Report</span>
        </a>
      </div>
      <div class="nav-group">
        <span class="nav-label">Manage</span>
        <a href="<?= BASE_PATH ?>/staff" class="nav-item <?= str_contains($cp??'','/staff')?'active':'' ?>" title="Staff">
          <span class="ni"><i class="fa-solid fa-users" aria-hidden="true"></i></span>
          <span class="nav-text">Staff</span>
        </a>
        <a href="<?= BASE_PATH ?>/customers" class="nav-item <?= str_contains($cp??'','/customers')?'active':'' ?>" title="Customers">
          <span class="ni"><i class="fa-solid fa-address-book" aria-hidden="true"></i></span>
          <span class="nav-text">Customers</span>
        </a>
        <a href="<?= BASE_PATH ?>/locations" class="nav-item <?= str_contains($cp??'','/locations')?'active':'' ?>" title="Locations">
          <span class="ni"><i class="fa-solid fa-location-dot" aria-hidden="true"></i></span>
          <span class="nav-text">Locations</span>
        </a>
      </div>
      <?php else: ?>
      <div class="nav-group">
        <span class="nav-label">Sales</span>
        <a href="<?= BASE_PATH ?>/pos" class="nav-item active" title="POS Terminal">
          <span class="ni"><i class="fa-solid fa-cart-shopping" aria-hidden="true"></i></span>
          <span class="nav-text">POS Terminal</span>
        </a>
      </div>
      <?php endif; ?>
    </nav>

    <div class="sidebar-footer">
      <div class="user-pill">
        <div class="user-av"><?= strtoupper(substr($_SESSION['user_name']??'U',0,1)) ?></div>
        <div class="user-meta">
          <div class="user-nm"><?= e($_SESSION['user_name']??'') ?></div>
          <div class="user-rl"><?= e($_SESSION['user_role']??'') ?></div>
        </div>
        <a href="<?= BASE_PATH ?>/logout" class="logout-btn" title="Logout" aria-label="Logout"><i class="fa-solid fa-right-from-bracket" aria-hidden="true"></i></a>
      </div>
    </div>
  </aside>

  <!-- Main -->
  <div class="main">
    <header class="topbar">
      <button type="button" class="btn btn-ghost btn-sm no-print topbar-icon-btn" id="menuBtn" title="Menu" aria-label="Open menu">
        <i class="fa-solid fa-bars" aria-hidden="true"></i>
      </button>
      <div class="topbar-heading">
        <span class="topbar-title"><?= e($pageTitle??'') ?></span>
      </div>
      <div class="topbar-actions">
        <a href="<?= BASE_PATH ?>/pos" class="btn btn-primary btn-sm no-print topbar-pos-btn"><i class="fa-solid fa-cart-shopping" aria-hidden="true"></i> <span>Open POS</span></a>
        <?php if (isOwner()): ?>
        <div class="topbar-alerts">
          <button type="button" class="btn btn-ghost btn-sm topbar-icon-btn" id="alertBtn" title="Low stock alerts" aria-label="Low stock alerts"><i class="fa-solid fa-triangle-exclamation" aria-hidden="true"></i></button>
          <span id="alertDot" class="alert-dot" hidden></span>
          <div id="alertPanel" class="alert-panel" hidden>
            <div class="alert-panel-head">Low Stock Alerts</div>
            <div id="alertList" class="alert-panel-list"></div>
          </div>
        </div>
        <?php endif; ?>
        <?php if ($backUrl??''): ?>
        <a href="<?= e($backUrl) ?>" class="btn btn-ghost btn-sm no-print topbar-back"><i class="fa-solid fa-arrow-left" aria-hidden="true"></i> Back</a>
        <?php endif; ?>
      </div>
    </header>

    <div class="page page-enter">
      <?php if ($s=flash('success')): ?><div class="alert alert-success"><span class="alert-ic"><i class="fa-solid fa-check" aria-hidden="true"></i></span> <?= e($s) ?></div><?php endif; ?>
      <?php if ($s=flash('error')): ?><div class="alert alert-error"><span class="alert-ic"><i class="fa-solid fa-circle-xmark" aria-hidden="true"></i></span> <?= e($s) ?></div><?php endif; ?>
      <?= $content??'' ?>
    </div>
  </div>
</div>

<script>
const BASE = '<?= BASE_PATH ?>';
const shell = document.getElementById('shell');
const sidebar = document.getElementById('sidebar');
const backdrop = document.getElementById('sidebarBackdrop');
const menuBtn = document.getElementById('menuBtn');
const collapseBtn = document.getElementById('sidebarCollapseBtn');

function isMobile(){ return window.innerWidth <= 768; }

function setCollapsed(on){
  document.documentElement.classList.toggle('sidebar-collapsed', on);
  try { localStorage.setItem('af_sidebar', on ? 'collapsed' : 'expanded'); } catch(e){}
  if (collapseBtn) {
    collapseBtn.title = on ? 'Expand sidebar' : 'Collapse sidebar';
    collapseBtn.setAttribute('aria-label', collapseBtn.title);
  }
}

function openMobileSidebar(){
  sidebar.classList.add('open');
  backdrop.hidden = false;
  requestAnimationFrame(() => backdrop.classList.add('show'));
  document.body.style.overflow = 'hidden';
}
function closeMobileSidebar(){
  sidebar.classList.remove('open');
  backdrop.classList.remove('show');
  backdrop.hidden = true;
  document.body.style.overflow = '';
}

menuBtn.addEventListener('click', () => {
  if (isMobile()) {
    sidebar.classList.contains('open') ? closeMobileSidebar() : openMobileSidebar();
  } else {
    setCollapsed(!document.documentElement.classList.contains('sidebar-collapsed'));
  }
});

if (collapseBtn) {
  collapseBtn.addEventListener('click', () => {
    if (isMobile()) closeMobileSidebar();
    else setCollapsed(!document.documentElement.classList.contains('sidebar-collapsed'));
  });
}

backdrop.addEventListener('click', closeMobileSidebar);

window.addEventListener('resize', () => {
  if (!isMobile()) {
    closeMobileSidebar();
  } else {
    document.documentElement.classList.remove('sidebar-collapsed');
  }
});

<?php if (isOwner()): ?>
async function toggleAlerts(){
  const p = document.getElementById('alertPanel');
  const open = p.hasAttribute('hidden');
  if (open) { p.removeAttribute('hidden'); await loadAlerts(); }
  else p.setAttribute('hidden','');
}
document.getElementById('alertBtn').addEventListener('click', e => { e.stopPropagation(); toggleAlerts(); });
document.addEventListener('click', e => {
  if (!e.target.closest('#alertBtn') && !e.target.closest('#alertPanel')) {
    document.getElementById('alertPanel')?.setAttribute('hidden','');
  }
});
async function loadAlerts(){
  const r = await fetch(BASE+'/api/alerts');
  const d = await r.json();
  const dot = document.getElementById('alertDot');
  if (d.count > 0) dot.removeAttribute('hidden'); else dot.setAttribute('hidden','');
  const list = document.getElementById('alertList');
  if (!d.alerts.length) {
    list.innerHTML = '<div class="alert-panel-empty">All stock levels OK.</div>';
    return;
  }
  list.innerHTML = d.alerts.map(a => `
    <div class="alert-panel-item">
      <div class="alert-panel-item-title">${a.name} – ${a.gender} ${a.design??''} <span style="color:var(--muted)">Sz ${a.size}</span></div>
      <div class="alert-panel-item-qty">${a.quantity} left (threshold: ${a.low_stock_threshold})</div>
    </div>
  `).join('');
}
loadAlerts();
setInterval(loadAlerts, 60000);
<?php endif; ?>
</script>
<?= $extraScript??'' ?>
</body>
</html>
