<?php /** @var array $payouts; string $status */ ?>
<div class="d-flex gap-2 mb-3">
  <?php foreach (['pending'=>'Pending','approved'=>'Approved','paid'=>'Paid','rejected'=>'Rejected',''=>'All'] as $k=>$l): ?>
    <a href="<?= e(base_url('/admin/payouts'.($k?'?status='.$k:''))) ?>" class="btn btn-sm btn-<?= $status===$k?'primary':'outline-secondary' ?>"><?= e($l) ?></a>
  <?php endforeach; ?>
</div>
<div class="card"><div class="table-responsive">
<table class="table table-sm align-middle mb-0">
  <thead class="table-light"><tr><th>#</th><th>Owner</th><th>Type</th><th>Amount</th><th>Balance</th><th>Bank</th><th>Status</th><th>UTR</th><th></th></tr></thead>
  <tbody>
  <?php if (!$payouts): ?><tr><td colspan="9" class="text-center text-muted py-3">No payout requests.</td></tr><?php endif; ?>
  <?php foreach ($payouts as $po): $bank = json_decode($po['bank_snapshot'] ?? '{}', true) ?: []; ?>
    <tr>
      <td><?= (int)$po['id'] ?></td>
      <td><?= e($po['owner_name']) ?></td>
      <td><span class="badge bg-secondary"><?= e($po['owner_type']) ?></span></td>
      <td class="fw-bold"><?= money($po['amount']) ?></td>
      <td><?= money($po['balance'] ?? 0) ?></td>
      <td style="font-size:12px">
        <?php if (!empty($bank['bank_account'])): ?><?= e($bank['bank_holder'] ?? '') ?><br><?= e(mask_account($bank['bank_account'])) ?> · <?= e($bank['bank_ifsc'] ?? '') ?><?php endif; ?>
        <?php if (!empty($bank['upi_id'])): ?><br>UPI: <?= e($bank['upi_id']) ?><?php endif; ?>
      </td>
      <td><span class="badge bg-<?= $po['status']==='paid'?'success':($po['status']==='rejected'?'danger':($po['status']==='approved'?'info':'warning')) ?>"><?= e($po['status']) ?></span></td>
      <td><?= e($po['utr'] ?: '—') ?></td>
      <td class="text-nowrap">
        <?php if (in_array($po['status'], ['pending','approved'], true)): ?>
          <div class="btn-group btn-group-sm">
            <?php if ($po['status']==='pending'): ?>
            <form method="post" action="<?= e(base_url('/admin/payouts/'.$po['id'].'/process')) ?>"><?= csrf_field() ?><button name="action" value="approve" class="btn btn-outline-info">Approve</button></form>
            <?php endif; ?>
            <button class="btn btn-success" data-bs-toggle="modal" data-bs-target="#paid<?= $po['id'] ?>">Mark Paid</button>
            <form method="post" action="<?= e(base_url('/admin/payouts/'.$po['id'].'/process')) ?>" onsubmit="return confirm('Reject?')"><?= csrf_field() ?><button name="action" value="reject" class="btn btn-outline-danger">Reject</button></form>
          </div>
          <!-- Mark paid modal -->
          <div class="modal fade" id="paid<?= $po['id'] ?>"><div class="modal-dialog"><div class="modal-content">
            <form method="post" action="<?= e(base_url('/admin/payouts/'.$po['id'].'/process')) ?>" enctype="multipart/form-data">
              <?= csrf_field() ?><input type="hidden" name="action" value="paid">
              <div class="modal-header"><h6 class="modal-title">Mark Paid — <?= money($po['amount']) ?></h6><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
              <div class="modal-body text-start">
                <div class="mb-2"><label class="form-label">UTR / Reference</label><input name="utr" class="form-control" required></div>
                <div class="mb-2"><label class="form-label">Proof (optional)</label><input type="file" name="proof_image" class="form-control" accept="image/*,application/pdf"></div>
                <p class="small text-muted">This debits the wallet by <?= money($po['amount']) ?>.</p>
              </div>
              <div class="modal-footer"><button class="btn btn-success">Confirm Paid</button></div>
            </form>
          </div></div></div>
        <?php endif; ?>
      </td>
    </tr>
  <?php endforeach; ?>
  </tbody>
</table>
</div></div>
