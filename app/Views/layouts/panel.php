<?php
/** Shared mobile-first panel layout for shop & agency. Expects: $title, $content, $nav (array), $base (url prefix), $userName. */
use App\Core\Lang;
$primary = setting('primary_color', '#0d6efd');
?>
<!doctype html>
<html lang="<?= e(Lang::locale()) ?>">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<meta name="csrf-token" content="<?= e(csrf_token()) ?>">
<script>window.CSRF=<?= json_encode(csrf_token()) ?>;</script>
<title><?= e($title ?? 'Panel') ?> — <?= e(setting('site_name','Dwarka Rental')) ?></title>
<link href="<?= e(asset('css/bootstrap.min.css')) ?>" rel="stylesheet">
<link href="<?= e(asset('css/bootstrap-icons.css')) ?>" rel="stylesheet">
<link href="https://fonts.googleapis.com/css2?family=Noto+Sans+Gujarati:wght@400;600;700&display=swap" rel="stylesheet">
<style>
 :root{--p:<?= e($primary) ?>;}
 body{font-family:'Noto Sans Gujarati',system-ui,sans-serif;background:#f1f5f9;padding-bottom:70px}
 .btn-primary{background:var(--p);border-color:var(--p)}
 .text-primary{color:var(--p)!important}
 .topbar{background:var(--p);color:#fff;padding:12px 16px;display:flex;justify-content:space-between;align-items:center;position:sticky;top:0;z-index:1030}
 .bottomnav{position:fixed;bottom:0;left:0;right:0;background:#fff;border-top:1px solid #e2e8f0;display:flex;z-index:1040}
 .bottomnav a{flex:1;text-align:center;padding:8px 2px;color:#64748b;text-decoration:none;font-size:11px}
 .bottomnav a.active{color:var(--p)}
 .bottomnav a i{display:block;font-size:20px}
 .stat{border-radius:12px;padding:14px;color:#fff}
</style>
</head>
<body>
<div class="topbar">
  <div class="fw-bold"><?= e($title ?? '') ?></div>
  <div class="d-flex align-items-center gap-2">
    <div class="btn-group btn-group-sm">
      <a href="<?= e(base_url('/lang/en')) ?>" class="btn btn-light py-0 <?= Lang::locale()==='en'?'active':'' ?>">EN</a>
      <a href="<?= e(base_url('/lang/gu')) ?>" class="btn btn-light py-0 <?= Lang::locale()==='gu'?'active':'' ?>">ગુ</a>
    </div>
    <a href="<?= e(base_url($base.'/logout')) ?>" class="text-white"><i class="bi bi-box-arrow-right"></i></a>
  </div>
</div>
<main class="container py-3">
  <?php if ($f = \App\Core\Session::flash('success')): ?><div class="alert alert-success py-2"><?= e($f) ?></div><?php endif; ?>
  <?php if ($f = \App\Core\Session::flash('error')): ?><div class="alert alert-danger py-2"><?= e($f) ?></div><?php endif; ?>
  <?= $content ?>
</main>
<nav class="bottomnav">
  <?php foreach ($nav as $item): ?>
    <a href="<?= e(base_url($item['url'])) ?>" class="<?= !empty($item['active'])?'active':'' ?>"><i class="bi <?= e($item['icon']) ?>"></i><?= e($item['label']) ?></a>
  <?php endforeach; ?>
</nav>
<script src="<?= e(asset('js/bootstrap.bundle.min.js')) ?>"></script>
<script>window.CSRF=document.querySelector('meta[name=csrf-token]').content;</script>
</body>
</html>
