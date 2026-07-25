<?php /** Admin login page (standalone, no layout). */ ?>
<!doctype html>
<html lang="en"><head>
<meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1">
<title>Admin Login — <?= e(setting('site_name', 'Dwarka Rental')) ?></title>
<link href="<?= e(asset('css/bootstrap.min.css')) ?>" rel="stylesheet">
<link href="<?= e(asset('css/bootstrap-icons.css')) ?>" rel="stylesheet">
<style>body{font-family:system-ui,'Noto Sans Gujarati',sans-serif;background:linear-gradient(135deg,#0f172a,#1e3a8a);min-height:100vh;display:flex;align-items:center}
.card{border:none;border-radius:16px;box-shadow:0 20px 50px rgba(0,0,0,.4)}</style>
</head><body>
<div class="container" style="max-width:420px">
  <div class="text-center text-white mb-3"><h3>🛵 <?= e(setting('site_name', 'Dwarka Rental')) ?></h3><small>Admin Panel</small></div>
  <div class="card p-4">
    <?php if ($error): ?><div class="alert alert-danger py-2"><?= e($error) ?></div><?php endif; ?>
    <form method="post" action="<?= e(base_url('/admin/login')) ?>">
      <?= csrf_field() ?>
      <div class="mb-3">
        <label class="form-label">Mobile or Email</label>
        <input name="login" class="form-control form-control-lg" autofocus required>
      </div>
      <div class="mb-3">
        <label class="form-label">Password</label>
        <input type="password" name="password" class="form-control form-control-lg" required>
      </div>
      <button class="btn btn-primary btn-lg w-100"><i class="bi bi-box-arrow-in-right"></i> Login</button>
    </form>
  </div>
</div>
</body></html>
