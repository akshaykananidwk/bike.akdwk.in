<?php /** @var array $payments; string $status */ ?>
<div class="d-flex gap-2 mb-3">
  <?php foreach (['' => 'All', 'pending_verification' => 'Pending Verification', 'paid' => 'Paid', 'failed' => 'Failed', 'refunded' => 'Refunded'] as $k => $l): ?>
    <a href="<?= e(base_url('/admin/payments' . ($k ? '?status=' . $k : ''))) ?>" class="btn btn-sm btn-<?= $status===$k?'primary':'outline-secondary' ?>"><?= e($l) ?></a>
  <?php endforeach; ?>
</div>
<div class="card"><div class="table-responsive">
<table class="table table-sm align-middle mb-0">
  <thead class="table-light"><tr><th>#</th><th>Booking</th><th>Customer</th><th>Gateway</th><th>Amount</th><th>UTR</th><th>Proof</th><th>Status</th><th>Date</th><th></th></tr></thead>
  <tbody>
  <?php if (!$payments): ?><tr><td colspan="10" class="text-center text-muted py-3">No payments.</td></tr><?php endif; ?>
  <?php foreach ($payments as $p): ?>
    <tr>
      <td><?= (int)$p['id'] ?></td>
      <td><?= e($p['booking_code']) ?></td>
      <td><?= e($p['customer_name']) ?><br><small class="text-muted"><?= e($p['customer_mobile']) ?></small></td>
      <td><span class="badge bg-secondary"><?= e($p['gateway']) ?></span></td>
      <td><?= money($p['amount']) ?></td>
      <td><?= e($p['utr'] ?: '—') ?></td>
      <td><?php if ($p['screenshot']): ?><a href="<?= e(upload_url($p['screenshot'])) ?>" target="_blank"><i class="bi bi-image"></i> view</a><?php else: ?>—<?php endif; ?></td>
      <td><span class="badge bg-<?= $p['status']==='paid'?'success':($p['status']==='pending_verification'?'warning':($p['status']==='failed'?'danger':'secondary')) ?>"><?= e(str_replace('_',' ',$p['status'])) ?></span></td>
      <td><small><?= e(date('d M h:iA', strtotime($p['created_at']))) ?></small></td>
      <td class="text-nowrap">
        <?php if ($p['status']==='pending_verification'): ?>
          <form method="post" action="<?= e(base_url('/admin/payments/'.$p['id'].'/verify')) ?>" class="d-inline">
            <?= csrf_field() ?>
            <button name="action" value="approve" class="btn btn-sm btn-success" onclick="return confirm('Approve this payment?')"><i class="bi bi-check"></i></button>
            <button name="action" value="reject" class="btn btn-sm btn-outline-danger" onclick="return confirm('Reject this payment?')"><i class="bi bi-x"></i></button>
          </form>
        <?php endif; ?>
      </td>
    </tr>
  <?php endforeach; ?>
  </tbody>
</table>
</div></div>
