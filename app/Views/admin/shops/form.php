<?php
/** @var array|null $shop */
$s = $shop ?? [];
$val = fn($k, $d = '') => e($s[$k] ?? old($k, $d));
$errs = \App\Core\Session::flash('errors') ?? [];
?>
<?php if ($errs): ?><div class="alert alert-danger"><?php foreach ($errs as $e): ?><div><?= e($e[0]) ?></div><?php endforeach; ?></div><?php endif; ?>

<form method="post" enctype="multipart/form-data">
  <?= csrf_field() ?>
  <div class="row g-3">
    <div class="col-md-8">
      <div class="card"><div class="card-header fw-bold">Shop Details</div><div class="card-body row g-3">
        <div class="col-md-4"><label class="form-label">Shop Code</label>
          <input name="code" class="form-control" value="<?= e($shop['code'] ?? $code) ?>" <?= $shop ? 'readonly' : '' ?>></div>
        <div class="col-md-4"><label class="form-label">Name (English) *</label><input name="name" class="form-control" value="<?= $val('name') ?>" required></div>
        <div class="col-md-4"><label class="form-label">Name (ગુજરાતી)</label><input name="name_gu" class="form-control" value="<?= $val('name_gu') ?>"></div>
        <div class="col-md-4"><label class="form-label">Owner Name</label><input name="owner_name" class="form-control" value="<?= $val('owner_name') ?>"></div>
        <div class="col-md-4"><label class="form-label">Mobile</label><input name="mobile" class="form-control" value="<?= $val('mobile') ?>"></div>
        <div class="col-md-4"><label class="form-label">WhatsApp</label><input name="whatsapp" class="form-control" value="<?= $val('whatsapp') ?>"></div>
        <div class="col-md-6"><label class="form-label">Email</label><input name="email" type="email" class="form-control" value="<?= $val('email') ?>"></div>
        <div class="col-md-6"><label class="form-label">Area</label><input name="area" class="form-control" value="<?= $val('area') ?>"></div>
        <div class="col-12"><label class="form-label">Address</label><input name="address" class="form-control" value="<?= $val('address') ?>"></div>
      </div></div>

      <div class="card mt-3"><div class="card-header fw-bold">Bank / Payout</div><div class="card-body row g-3">
        <div class="col-md-6"><label class="form-label">Account Holder</label><input name="bank_holder" class="form-control" value="<?= $val('bank_holder') ?>"></div>
        <div class="col-md-6"><label class="form-label">Account Number</label><input name="bank_account" class="form-control" value="<?= $val('bank_account') ?>"></div>
        <div class="col-md-4"><label class="form-label">IFSC</label><input name="bank_ifsc" class="form-control text-uppercase" value="<?= $val('bank_ifsc') ?>"></div>
        <div class="col-md-4"><label class="form-label">Bank Name</label><input name="bank_name" class="form-control" value="<?= $val('bank_name') ?>"></div>
        <div class="col-md-4"><label class="form-label">UPI ID</label><input name="upi_id" class="form-control" value="<?= $val('upi_id') ?>"></div>
        <div class="col-md-6"><label class="form-label">Cancelled Cheque (optional)</label><input type="file" name="cheque_image" accept="image/*,application/pdf" class="form-control"></div>
        <div class="col-md-6"><label class="form-label">KYC Document (optional)</label><input type="file" name="kyc_doc" accept="image/*,application/pdf" class="form-control"></div>
      </div></div>
    </div>

    <div class="col-md-4">
      <div class="card"><div class="card-header fw-bold">Commission</div><div class="card-body">
        <label class="form-label">Type</label>
        <select name="commission_type" class="form-select mb-2" onchange="document.getElementById('cv').disabled=this.value==='inherit'">
          <?php foreach (['inherit'=>'Inherit Global','percent'=>'Percentage %','fixed'=>'Fixed ₹'] as $k=>$lbl): ?>
            <option value="<?= $k ?>" <?= ($s['commission_type'] ?? 'inherit')===$k?'selected':'' ?>><?= $lbl ?></option>
          <?php endforeach; ?>
        </select>
        <label class="form-label">Value</label>
        <input id="cv" name="commission_value" type="number" step="0.01" class="form-control" value="<?= $val('commission_value','0') ?>" <?= ($s['commission_type'] ?? 'inherit')==='inherit'?'disabled':'' ?>>
      </div></div>

      <div class="card mt-3"><div class="card-header fw-bold">Status</div><div class="card-body">
        <label class="form-label">KYC Status</label>
        <select name="kyc_status" class="form-select mb-2">
          <?php foreach (['pending','approved','rejected'] as $k): ?><option value="<?= $k ?>" <?= ($s['kyc_status'] ?? 'pending')===$k?'selected':'' ?>><?= ucfirst($k) ?></option><?php endforeach; ?>
        </select>
        <div class="form-check mb-2"><input type="checkbox" name="is_verified" value="1" class="form-check-input" id="iv" <?= !empty($s['is_verified'])?'checked':'' ?>><label class="form-check-label" for="iv">Bank verified</label></div>
        <label class="form-label">Active</label>
        <select name="status" class="form-select"><?php foreach (['active','inactive'] as $k): ?><option value="<?= $k ?>" <?= ($s['status'] ?? 'active')===$k?'selected':'' ?>><?= ucfirst($k) ?></option><?php endforeach; ?></select>
      </div></div>
    </div>
  </div>

  <div class="mt-3 d-flex gap-2">
    <button class="btn btn-primary"><i class="bi bi-check-lg"></i> Save Shop</button>
    <a href="<?= e(base_url('/admin/shops')) ?>" class="btn btn-outline-secondary">Cancel</a>
  </div>
</form>
