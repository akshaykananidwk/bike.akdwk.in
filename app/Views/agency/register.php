<?php /** Agency self-registration (standalone page). */
$old = \App\Core\Session::flash('old') ?? [];
$v = fn($k) => e($old[$k] ?? '');
?>
<!doctype html>
<html lang="en"><head>
<meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1">
<meta name="csrf-token" content="<?= e(csrf_token()) ?>">
<title>Agency Registration — <?= e(setting('site_name','Dwarka Rental')) ?></title>
<meta name="description" content="List your bikes, cars, taxis or tempo travellers on <?= e(setting('site_name','Dwarka Rental')) ?>. Register your rental agency in Dwarka and get online bookings.">
<link href="<?= e(asset('css/bootstrap.min.css')) ?>" rel="stylesheet">
<link href="<?= e(asset('css/bootstrap-icons.css')) ?>" rel="stylesheet">
<style>
 body{font-family:system-ui,sans-serif;background:linear-gradient(135deg,#7c2d12,#f59e0b);min-height:100vh;padding:24px 0}
 .card{border:none;border-radius:16px;box-shadow:0 20px 50px rgba(0,0,0,.25)}
 .perk{background:#fffbeb;border-radius:12px;padding:10px;font-size:13px}
</style>
</head><body>
<div class="container" style="max-width:560px">
  <div class="text-center text-white mb-3">
    <h4 class="fw-bold"><i class="bi bi-people"></i> List Your Vehicles</h4>
    <small>🙏 Jai Dwarkadhish · <?= e(setting('site_name','Dwarka Rental')) ?></small>
  </div>

  <div class="card p-4">
    <div class="perk mb-3">
      <div class="fw-bold mb-1">Why list with us?</div>
      <div>✔ Get online bookings for your bikes, cars, taxis &amp; tempos</div>
      <div>✔ Instant WhatsApp alerts for every new booking</div>
      <div>✔ Settlement to your bank after each ride</div>
    </div>

    <?php if ($error): ?><div class="alert alert-danger py-2"><?= e($error) ?></div><?php endif; ?>

    <form method="post" action="<?= e(base_url('/agency/register')) ?>">
      <?= csrf_field() ?>
      <div class="row g-2">
        <div class="col-12"><label class="form-label small fw-semibold">Agency / Business Name *</label>
          <input name="name" class="form-control" value="<?= $v('name') ?>" required placeholder="e.g. Dwarka Wheels"></div>
        <div class="col-md-6"><label class="form-label small fw-semibold">Owner Name *</label>
          <input name="owner_name" class="form-control" value="<?= $v('owner_name') ?>" required></div>
        <div class="col-md-6"><label class="form-label small fw-semibold">Mobile (this is your login) *</label>
          <input name="mobile" class="form-control" value="<?= $v('mobile') ?>" maxlength="10" inputmode="numeric" required placeholder="10-digit mobile"></div>
        <div class="col-md-6"><label class="form-label small fw-semibold">Email (optional)</label>
          <input type="email" name="email" class="form-control" value="<?= $v('email') ?>"></div>
        <div class="col-md-6"><label class="form-label small fw-semibold">Address</label>
          <input name="address" class="form-control" value="<?= $v('address') ?>"></div>
        <div class="col-12"><label class="form-label small fw-semibold">Create Password *</label>
          <input type="password" name="password" class="form-control" minlength="8" required placeholder="Minimum 8 characters"></div>
        <div class="col-12">
          <div class="form-check"><input type="checkbox" name="terms" value="1" class="form-check-input" id="t" required>
            <label class="form-check-label small" for="t">I accept the <a href="<?= e(base_url('/page/terms')) ?>" target="_blank">Terms &amp; Conditions</a></label></div>
        </div>
      </div>
      <button class="btn btn-warning btn-lg w-100 mt-3"><i class="bi bi-check-lg"></i> Register My Agency</button>
    </form>

    <div class="text-center mt-3 small">
      Already registered? <a href="<?= e(base_url('/agency/login')) ?>">Login here</a>
    </div>
  </div>
  <div class="text-center mt-2"><a href="<?= e(base_url('/')) ?>" class="text-white small">← Back to website</a></div>
</div>
</body></html>
