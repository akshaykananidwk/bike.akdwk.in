<?php /** @var array $shop,$user */ ?>
<div class="card mb-3"><div class="card-body">
  <div class="fw-bold fs-5"><?= e($shop['name']) ?></div>
  <div class="badge bg-dark mb-2"><?= e($shop['code']) ?></div>
  <table class="table table-sm mb-0">
    <tr><td class="text-muted">Owner</td><td><?= e($shop['owner_name']) ?></td></tr>
    <tr><td class="text-muted">Mobile</td><td><?= e($shop['mobile']) ?></td></tr>
    <tr><td class="text-muted">Area</td><td><?= e($shop['area']) ?>, <?= e($shop['city']) ?></td></tr>
    <tr><td class="text-muted">Commission</td><td><?= $shop['commission_type']==='inherit'?'Global':($shop['commission_type']==='percent'?$shop['commission_value'].'%':money($shop['commission_value'])) ?></td></tr>
    <tr><td class="text-muted">KYC</td><td><span class="badge bg-<?= $shop['kyc_status']==='approved'?'success':'secondary' ?>"><?= e($shop['kyc_status']) ?></span></td></tr>
  </table>
</div></div>
<div class="card"><div class="card-body">
  <h6 class="fw-bold">Change Password</h6>
  <form method="post" action="<?= e(base_url('/shop/profile')) ?>">
    <?= csrf_field() ?>
    <div class="mb-2"><input type="password" name="password" class="form-control" placeholder="New password (min 8 chars)" minlength="8" required></div>
    <button class="btn btn-primary">Update Password</button>
  </form>
</div></div>
