<?php /** Shop partner login (standalone). */ ?>
<!doctype html>
<html lang="en"><head>
<meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1">
<title>Shop Login — <?= e(setting('site_name','Dwarka Rental')) ?></title>
<link href="<?= e(asset('css/bootstrap.min.css')) ?>" rel="stylesheet">
<link href="<?= e(asset('css/bootstrap-icons.css')) ?>" rel="stylesheet">
<style>body{font-family:system-ui,'Noto Sans Gujarati',sans-serif;background:linear-gradient(135deg,#065f46,#10b981);min-height:100vh;display:flex;align-items:center}</style>
</head><body>
<div class="container" style="max-width:400px">
  <div class="text-center text-white mb-3"><h4><i class="bi bi-shop"></i> Shop Partner</h4><small><?= e(setting('site_name','Dwarka Rental')) ?></small></div>
  <div class="card border-0 shadow p-4">
    <?php if ($error): ?><div class="alert alert-danger py-2"><?= e($error) ?></div><?php endif; ?>
    <form method="post" action="<?= e(base_url('/shop/login')) ?>">
      <?= csrf_field() ?>
      <div class="mb-3"><label class="form-label">Mobile / Email</label><input name="login" class="form-control form-control-lg" required autofocus></div>
      <div class="mb-3"><label class="form-label">Password</label><input type="password" name="password" class="form-control form-control-lg" required></div>
      <button class="btn btn-success btn-lg w-100">Login</button>
    </form>
  </div>
  <div class="text-center mt-2"><a href="<?= e(base_url('/')) ?>" class="text-white small">← Back to site</a></div>
</div>
</body></html>
