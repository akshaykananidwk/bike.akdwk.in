<?php /** @var array $vehicles,$shops */ ?>
<a href="<?= e(base_url('/admin/bookings')) ?>" class="btn btn-sm btn-link px-0">&larr; Bookings</a>
<form method="post" class="card card-body" style="max-width:640px">
  <?= csrf_field() ?>
  <h5 class="mb-3">Walk-in Booking</h5>
  <div class="row g-3">
    <div class="col-md-6"><label class="form-label">Vehicle *</label><select name="vehicle_id" class="form-select" required><option value="">Select…</option><?php foreach ($vehicles as $v): ?><option value="<?= $v['id'] ?>"><?= e($v['name']) ?> (<?= money($v['price_day']) ?>/day)</option><?php endforeach; ?></select></div>
    <div class="col-md-6"><label class="form-label">Refer to Shop (optional)</label><select name="shop_id" class="form-select"><option value="">Direct</option><?php foreach ($shops as $s): ?><option value="<?= $s['id'] ?>"><?= e($s['name']) ?></option><?php endforeach; ?></select></div>
    <div class="col-md-6"><label class="form-label">Pickup *</label><input type="datetime-local" name="pickup" class="form-control" required></div>
    <div class="col-md-6"><label class="form-label">Drop *</label><input type="datetime-local" name="drop" class="form-control" required></div>
    <div class="col-md-6"><label class="form-label">Customer Name *</label><input name="customer_name" class="form-control" required></div>
    <div class="col-md-6"><label class="form-label">Customer Mobile *</label><input name="customer_mobile" class="form-control" required></div>
    <div class="col-md-6"><label class="form-label">Amount Paid Now</label><input name="paid_amount" type="number" step="0.01" class="form-control" value="0"></div>
  </div>
  <div class="mt-3"><button class="btn btn-primary">Create Booking</button></div>
</form>
