<?php
/** Admin panel layout. Expects: $title, $content, $active (menu key). */
use App\Core\Auth;
$active = $active ?? '';
$primary = setting('primary_color', '#0d6efd');
$menu = [
    'dashboard' => ['/admin', 'Dashboard', 'bi-speedometer2'],
    'shops'     => ['/admin/shops', 'Shops', 'bi-shop'],
    'agencies'  => ['/admin/agencies', 'Agencies', 'bi-people'],
    'categories'=> ['/admin/categories', 'Categories', 'bi-tags'],
    'vehicles'  => ['/admin/vehicles', 'Vehicles', 'bi-scooter'],
    'packages'  => ['/admin/packages', 'Packages', 'bi-map'],
    'reviews'   => ['/admin/reviews', 'Reviews', 'bi-star'],
    'bookings'  => ['/admin/bookings', 'Bookings', 'bi-calendar-check'],
    'payments'  => ['/admin/payments', 'Payments', 'bi-cash-stack'],
    'payouts'   => ['/admin/payouts', 'Payouts', 'bi-wallet2'],
    'coupons'   => ['/admin/coupons', 'Coupons', 'bi-ticket-perforated'],
    'reports'   => ['/admin/reports', 'Reports', 'bi-graph-up'],
    'whatsapp'  => ['/admin/whatsapp', 'WhatsApp', 'bi-whatsapp'],
    'settings'  => ['/admin/settings', 'Settings', 'bi-gear'],
    'staff'     => ['/admin/staff', 'Staff & Roles', 'bi-person-badge'],
    'activity'  => ['/admin/activity', 'Activity Log', 'bi-clock-history'],
    'updates'   => ['/admin/updates', 'Update Manager', 'bi-arrow-repeat'],
];
$u = Auth::user();
?>
<!doctype html>
<html lang="<?= e(\App\Core\Lang::locale()) ?>">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<meta name="csrf-token" content="<?= e(csrf_token()) ?>">
<script>window.CSRF=<?= json_encode(csrf_token()) ?>;</script>
<title><?= e($title ?? 'Admin') ?> — <?= e(setting('site_name', 'Dwarka Rental')) ?></title>
<link href="<?= e(asset('css/bootstrap.min.css')) ?>" rel="stylesheet">
<link href="<?= e(asset('css/bootstrap-icons.css')) ?>" rel="stylesheet">
<style>
 :root{--p:<?= e($primary) ?>;}
 body{font-family:system-ui,'Noto Sans Gujarati',sans-serif;background:#f1f5f9}
 .sidebar{position:fixed;top:0;left:0;bottom:0;width:240px;background:#0f172a;color:#cbd5e1;overflow-y:auto;transition:.2s;z-index:1040}
 .sidebar .brand{padding:16px;font-weight:700;color:#fff;font-size:18px;border-bottom:1px solid #1e293b}
 .sidebar a{display:flex;align-items:center;gap:10px;padding:11px 16px;color:#cbd5e1;text-decoration:none;font-size:14px}
 .sidebar a:hover,.sidebar a.active{background:#1e293b;color:#fff;border-left:3px solid var(--p)}
 .main{margin-left:240px;transition:.2s}
 .topbar{background:#fff;padding:10px 18px;display:flex;align-items:center;justify-content:space-between;box-shadow:0 1px 3px rgba(0,0,0,.08);position:sticky;top:0;z-index:1030}
 .content{padding:20px}
 .stat-card{border-radius:12px;padding:16px;color:#fff}
 .toggle{display:none;background:none;border:none;font-size:22px}
 @media(max-width:900px){.sidebar{transform:translateX(-100%)}.sidebar.open{transform:none}.main{margin-left:0}.toggle{display:block}}
</style>
</head>
<body>
<nav class="sidebar" id="sb">
  <div class="brand">🛵 <?= e(setting('site_name', 'Dwarka Rental')) ?></div>
  <?php foreach ($menu as $key => [$url, $label, $icon]): ?>
    <a href="<?= e(base_url($url)) ?>" class="<?= $active === $key ? 'active' : '' ?>"><i class="bi <?= e($icon) ?>"></i><?= e($label) ?></a>
  <?php endforeach; ?>
  <a href="<?= e(base_url('/admin/logout')) ?>"><i class="bi bi-box-arrow-right"></i>Logout</a>
</nav>
<div class="main">
  <div class="topbar">
    <button class="toggle" onclick="document.getElementById('sb').classList.toggle('open')"><i class="bi bi-list"></i></button>
    <div class="fw-bold"><?= e($title ?? 'Dashboard') ?></div>
    <div class="d-flex align-items-center gap-3">
      <div class="btn-group btn-group-sm">
        <a href="<?= e(base_url('/lang/en')) ?>" class="btn btn-outline-secondary <?= \App\Core\Lang::locale()==='en'?'active':'' ?>">EN</a>
        <a href="<?= e(base_url('/lang/gu')) ?>" class="btn btn-outline-secondary <?= \App\Core\Lang::locale()==='gu'?'active':'' ?>">ગુ</a>
      </div>
      <span class="small text-muted"><i class="bi bi-person-circle"></i> <?= e($u['name'] ?? '') ?></span>
    </div>
  </div>
  <div class="content">
    <?php if ($f = \App\Core\Session::flash('success')): ?>
      <div class="alert alert-success"><?= e($f) ?></div>
    <?php endif; ?>
    <?php if ($f = \App\Core\Session::flash('error')): ?>
      <div class="alert alert-danger"><?= e($f) ?></div>
    <?php endif; ?>
    <?= $content ?>
  </div>
</div>
<script src="<?= e(asset('js/bootstrap.bundle.min.js')) ?>"></script>
<script>
// Attach CSRF token to all fetch POSTs by default.
window.CSRF = document.querySelector('meta[name=csrf-token]').content;
</script>
</body>
</html>
