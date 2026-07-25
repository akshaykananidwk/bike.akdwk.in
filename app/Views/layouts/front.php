<?php
/** Customer-facing layout. Expects: $title, $content, optional $meta. */
use App\Core\Lang;
use App\Core\Session;
$primary = setting('primary_color', '#0d6efd');
$secondary = setting('secondary_color', '#20c997');
$refShop = Session::get('ref_shop_name');
?>
<!doctype html>
<html lang="<?= e(Lang::locale()) ?>">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1">
<meta name="csrf-token" content="<?= e(csrf_token()) ?>">
<meta name="theme-color" content="<?= e($primary) ?>">
<title><?= e($title ?? setting('site_name', 'Dwarka Rental')) ?></title>
<?= $meta ?? '' ?>
<link rel="manifest" href="<?= e(base_url('/manifest.webmanifest')) ?>">
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
<link href="https://fonts.googleapis.com/css2?family=Noto+Sans+Gujarati:wght@400;600;700&display=swap" rel="stylesheet">
<style>
 :root{--p:<?= e($primary) ?>;--s:<?= e($secondary) ?>;}
 body{font-family:'Noto Sans Gujarati',system-ui,sans-serif;background:#f8fafc;padding-bottom:70px}
 .navbar-brand{font-weight:700}
 .btn-primary{background:var(--p);border-color:var(--p)}
 .text-primary{color:var(--p)!important}
 .ref-banner{background:var(--s);color:#053b2c;text-align:center;padding:7px;font-size:14px;font-weight:600}
 .wa-float{position:fixed;right:16px;bottom:80px;background:#25d366;color:#fff;width:52px;height:52px;border-radius:50%;display:flex;align-items:center;justify-content:center;font-size:26px;box-shadow:0 4px 14px rgba(0,0,0,.25);z-index:1050}
 .veh-card img{height:170px;object-fit:cover;width:100%}
 .tap{min-height:44px}
</style>
</head>
<body>
<?php if ($refShop): ?>
  <div class="ref-banner"><i class="bi bi-shop"></i> <?= e(__('booking_via', ['shop' => $refShop])) ?></div>
<?php endif; ?>
<nav class="navbar navbar-expand-lg bg-white shadow-sm sticky-top">
  <div class="container">
    <a class="navbar-brand text-primary" href="<?= e(base_url('/')) ?>">🛵 <?= e(setting('site_name', 'Dwarka Rental')) ?></a>
    <div class="d-flex align-items-center gap-2">
      <div class="btn-group btn-group-sm">
        <a href="<?= e(base_url('/lang/en')) ?>" class="btn btn-outline-secondary <?= Lang::locale()==='en'?'active':'' ?>">EN</a>
        <a href="<?= e(base_url('/lang/gu')) ?>" class="btn btn-outline-secondary <?= Lang::locale()==='gu'?'active':'' ?>">ગુ</a>
      </div>
      <a href="<?= e(base_url('/my-bookings')) ?>" class="btn btn-sm btn-outline-primary tap"><i class="bi bi-bag-check"></i></a>
    </div>
  </div>
</nav>

<main class="container py-3">
  <?php if ($f = Session::flash('success')): ?><div class="alert alert-success"><?= e($f) ?></div><?php endif; ?>
  <?php if ($f = Session::flash('error')): ?><div class="alert alert-danger"><?= e($f) ?></div><?php endif; ?>
  <?= $content ?>
</main>

<?php if ($wa = setting('contact_whatsapp')): ?>
  <a class="wa-float" href="https://wa.me/91<?= e($wa) ?>" target="_blank" rel="noopener"><i class="bi bi-whatsapp"></i></a>
<?php endif; ?>

<footer class="text-center small text-muted py-3">
  <a href="<?= e(base_url('/page/terms')) ?>" class="text-muted">Terms</a> ·
  <a href="<?= e(base_url('/page/privacy')) ?>" class="text-muted">Privacy</a> ·
  <a href="<?= e(base_url('/page/cancellation')) ?>" class="text-muted">Cancellation</a> ·
  <a href="<?= e(base_url('/page/about')) ?>" class="text-muted">About</a>
  <div class="mt-1">© <?= date('Y') ?> <?= e(setting('site_name', 'Dwarka Rental')) ?></div>
</footer>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
window.CSRF = document.querySelector('meta[name=csrf-token]').content;
if ('serviceWorker' in navigator) { navigator.serviceWorker.register('<?= e(base_url('/sw.js')) ?>').catch(function(){}); }
</script>
</body>
</html>
