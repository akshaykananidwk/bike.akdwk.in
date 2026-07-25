<?php /** @var array $agency,$user */ ?>
<div class="card mb-3"><div class="card-body">
  <div class="fw-bold fs-5"><?= e($agency['name']) ?></div>
  <div class="badge bg-dark mb-2"><?= e($agency['code']) ?></div>
  <table class="table table-sm mb-0">
    <tr><td class="text-muted">Owner</td><td><?= e($agency['owner_name']) ?></td></tr>
    <tr><td class="text-muted">Mobile</td><td><?= e($agency['mobile']) ?></td></tr>
  </table>
</div></div>

<div class="card mb-3"><div class="card-body">
  <h6 class="fw-bold">Payment Routing (UPI QR)</h6>
  <form method="post" action="<?= e(base_url('/agency/profile')) ?>" enctype="multipart/form-data">
    <?= csrf_field() ?>
    <div class="mb-2"><label class="form-label small">UPI ID</label><input name="upi_id" class="form-control" value="<?= e($agency['upi_id']) ?>"></div>
    <div class="mb-2"><label class="form-label small">UPI QR Image</label><input type="file" name="upi_qr_image" class="form-control" accept="image/*">
      <?php if ($agency['upi_qr_image']): ?><img src="<?= e(upload_url($agency['upi_qr_image'])) ?>" class="img-fluid mt-2 border rounded" style="max-height:140px"><?php endif; ?></div>
    <button class="btn btn-primary btn-sm">Save UPI</button>
  </form>
</div></div>

<div class="card"><div class="card-body">
  <h6 class="fw-bold">Change Password</h6>
  <form method="post" action="<?= e(base_url('/agency/profile')) ?>">
    <?= csrf_field() ?>
    <div class="mb-2"><input type="password" name="password" class="form-control" placeholder="New password (min 8)" minlength="8" required></div>
    <button class="btn btn-primary btn-sm">Update Password</button>
  </form>
</div></div>
