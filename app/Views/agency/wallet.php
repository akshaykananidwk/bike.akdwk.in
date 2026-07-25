<?php /** @var array $wallet,$txns,$payouts,$agency; float $minWithdrawal */ ?>
<div class="card mb-3"><div class="card-body text-center">
  <div class="text-muted small">Available to withdraw</div>
  <div class="display-6 fw-bold text-success"><?= money($wallet['balance']) ?></div>
  <?php if (($wallet['pending_balance'] ?? 0) > 0): ?>
    <div class="badge bg-warning text-dark mt-1"><i class="bi bi-hourglass-split"></i> <?= money($wallet['pending_balance']) ?> on hold (48h settlement)</div>
  <?php endif; ?>
  <div class="d-flex justify-content-around mt-2 small text-muted">
    <div>Earned<br><span class="fw-bold text-dark"><?= money($wallet['total_earned'] ?? 0) ?></span></div>
    <div>Withdrawn<br><span class="fw-bold text-dark"><?= money($wallet['total_withdrawn'] ?? 0) ?></span></div>
  </div>
</div></div>
<div class="d-flex gap-2 mb-3">
  <button class="btn btn-warning flex-grow-1" data-bs-toggle="modal" data-bs-target="#wd"><i class="bi bi-cash"></i> Withdraw</button>
  <button class="btn btn-outline-secondary flex-grow-1" data-bs-toggle="modal" data-bs-target="#bank"><i class="bi bi-bank"></i> Bank</button>
</div>

<h6 class="small text-muted">Withdrawal Requests</h6>
<?php if (!$payouts): ?><p class="text-muted small">None yet.</p><?php endif; ?>
<?php foreach ($payouts as $po): ?>
  <div class="card mb-1"><div class="card-body py-2 d-flex justify-content-between">
    <div><div class="fw-semibold"><?= money($po['amount']) ?></div><small class="text-muted"><?= e(date('d M', strtotime($po['created_at']))) ?></small></div>
    <span class="badge bg-<?= $po['status']==='paid'?'success':($po['status']==='rejected'?'danger':'warning') ?> align-self-center"><?= e($po['status']) ?></span>
  </div></div>
<?php endforeach; ?>

<h6 class="small text-muted mt-3">Transactions</h6>
<?php foreach ($txns as $t): ?>
  <div class="d-flex justify-content-between border-bottom py-1 small">
    <span><?= e($t['note'] ?: $t['ref_type']) ?><br><span class="text-muted"><?= e(date('d M h:iA', strtotime($t['created_at']))) ?></span></span>
    <span class="<?= $t['direction']==='credit'?'text-success':'text-danger' ?> fw-bold"><?= $t['direction']==='credit'?'+':'−' ?><?= money($t['amount']) ?></span>
  </div>
<?php endforeach; ?>

<div class="modal fade" id="wd"><div class="modal-dialog modal-dialog-centered"><div class="modal-content">
  <form method="post" action="<?= e(base_url('/agency/withdraw')) ?>"><?= csrf_field() ?>
    <div class="modal-header"><h6 class="modal-title">Withdraw</h6><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
    <div class="modal-body"><p class="small text-muted">Min <?= money($minWithdrawal) ?>. Available <?= money($wallet['balance']) ?>.</p>
      <input name="amount" type="number" step="0.01" min="<?= $minWithdrawal ?>" max="<?= $wallet['balance'] ?>" class="form-control form-control-lg" required></div>
    <div class="modal-footer"><button class="btn btn-warning w-100">Request</button></div>
  </form>
</div></div></div>

<div class="modal fade" id="bank"><div class="modal-dialog modal-dialog-centered"><div class="modal-content">
  <form method="post" action="<?= e(base_url('/agency/withdraw')) ?>"><?= csrf_field() ?><input type="hidden" name="save_bank" value="1">
    <div class="modal-header"><h6 class="modal-title">Bank Details</h6><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
    <div class="modal-body">
      <div class="mb-2"><label class="form-label small">Account Holder</label><input name="bank_holder" class="form-control" value="<?= e($agency['bank_holder']) ?>"></div>
      <div class="mb-2"><label class="form-label small">Account Number</label><input name="bank_account" class="form-control" value="<?= e($agency['bank_account']) ?>"></div>
      <div class="mb-2"><label class="form-label small">IFSC</label><input name="bank_ifsc" class="form-control text-uppercase" value="<?= e($agency['bank_ifsc']) ?>"></div>
      <div class="mb-2"><label class="form-label small">Bank Name</label><input name="bank_name" class="form-control" value="<?= e($agency['bank_name']) ?>"></div>
    </div>
    <div class="modal-footer"><button class="btn btn-primary w-100">Save</button></div>
  </form>
</div></div></div>
