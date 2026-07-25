<?php $a = $agency ?? []; $val = fn($k,$d='') => e($a[$k] ?? old($k,$d));
$errs = \App\Core\Session::flash('errors') ?? []; ?>
<?php if ($errs): ?><div class="alert alert-danger"><?php foreach ($errs as $e): ?><div><?= e($e[0]) ?></div><?php endforeach; ?></div><?php endif; ?>
<form method="post" enctype="multipart/form-data">
  <?= csrf_field() ?>
  <div class="row g-3">
    <div class="col-md-8"><div class="card"><div class="card-header fw-bold">Agency Details</div><div class="card-body row g-3">
      <div class="col-md-4"><label class="form-label">Code</label><input name="code" class="form-control" value="<?= e($a['code'] ?? $code) ?>" <?= $agency?'readonly':'' ?>></div>
      <div class="col-md-4"><label class="form-label">Name *</label><input name="name" class="form-control" value="<?= $val('name') ?>" required></div>
      <div class="col-md-4"><label class="form-label">Name (ગુજરાતી)</label><input name="name_gu" class="form-control" value="<?= $val('name_gu') ?>"></div>
      <div class="col-md-4"><label class="form-label">Owner Name</label><input name="owner_name" class="form-control" value="<?= $val('owner_name') ?>"></div>
      <div class="col-md-4"><label class="form-label">Mobile</label><input name="mobile" class="form-control" value="<?= $val('mobile') ?>"></div>
      <div class="col-md-4"><label class="form-label">WhatsApp (booking alerts)</label><input name="whatsapp" class="form-control" value="<?= $val('whatsapp') ?>"></div>
      <div class="col-md-6"><label class="form-label">Email</label><input name="email" type="email" class="form-control" value="<?= $val('email') ?>"></div>
      <div class="col-md-6"><label class="form-label">Address</label><input name="address" class="form-control" value="<?= $val('address') ?>"></div>
    </div></div>
    <div class="card mt-3"><div class="card-header fw-bold">Bank / Settlement</div><div class="card-body row g-3">
      <div class="col-md-6"><label class="form-label">Account Holder</label><input name="bank_holder" class="form-control" value="<?= $val('bank_holder') ?>"></div>
      <div class="col-md-6"><label class="form-label">Account Number</label><input name="bank_account" class="form-control" value="<?= $val('bank_account') ?>"></div>
      <div class="col-md-4"><label class="form-label">IFSC</label><input name="bank_ifsc" class="form-control text-uppercase" value="<?= $val('bank_ifsc') ?>"></div>
      <div class="col-md-4"><label class="form-label">Bank Name</label><input name="bank_name" class="form-control" value="<?= $val('bank_name') ?>"></div>
      <div class="col-md-4"><label class="form-label">Settlement Rate (%)</label><input name="settlement_rate" type="number" step="0.01" class="form-control" value="<?= $val('settlement_rate','0') ?>"></div>
    </div></div>
    </div>
    <div class="col-md-4">
      <div class="card"><div class="card-header fw-bold">Payment Routing (UPI)</div><div class="card-body">
        <label class="form-label">UPI ID</label><input name="upi_id" class="form-control mb-2" value="<?= $val('upi_id') ?>">
        <label class="form-label">UPI QR Image</label><input type="file" name="upi_qr_image" accept="image/*" class="form-control">
        <?php if (!empty($a['upi_qr_image'])): ?><img src="<?= e(upload_url($a['upi_qr_image'])) ?>" class="img-fluid mt-2 border rounded" style="max-height:160px"><?php endif; ?>
      </div></div>
      <div class="card mt-3"><div class="card-header fw-bold">Agency Login</div><div class="card-body">
        <label class="form-label">Set / Reset Password</label>
        <input type="password" name="login_password" class="form-control" placeholder="Min 6 chars (leave blank to keep)">
        <small class="text-muted">Login = agency mobile. Logs in at /agency/login.</small>
      </div></div>

      <div class="card mt-3"><div class="card-header fw-bold">Commission</div><div class="card-body">
        <label class="form-label">Type</label>
        <select name="commission_type" class="form-select mb-2"><?php foreach (['inherit'=>'Inherit Global','percent'=>'Percentage %','fixed'=>'Fixed ₹'] as $k=>$l): ?><option value="<?= $k ?>" <?= ($a['commission_type'] ?? 'inherit')===$k?'selected':'' ?>><?= $l ?></option><?php endforeach; ?></select>
        <label class="form-label">Value</label><input name="commission_value" type="number" step="0.01" class="form-control mb-2" value="<?= $val('commission_value','0') ?>">
        <label class="form-label">Status</label>
        <select name="status" class="form-select"><?php foreach (['active','inactive'] as $k): ?><option value="<?= $k ?>" <?= ($a['status'] ?? 'active')===$k?'selected':'' ?>><?= ucfirst($k) ?></option><?php endforeach; ?></select>
      </div></div>
    </div>
  </div>
  <div class="mt-3"><button class="btn btn-primary"><i class="bi bi-check-lg"></i> Save Agency</button> <a href="<?= e(base_url('/admin/agencies')) ?>" class="btn btn-outline-secondary">Cancel</a></div>
</form>
