<?php /** @var array $reviews,$counts; string $status */ ?>
<div class="d-flex gap-2 mb-3">
  <?php foreach (['pending'=>'Pending','approved'=>'Approved','rejected'=>'Rejected',''=>'All'] as $k=>$l): ?>
    <a href="<?= e(base_url('/admin/reviews'.($k?'?status='.$k:'?status='))) ?>" class="btn btn-sm btn-<?= $status===$k?'primary':'outline-secondary' ?>">
      <?= e($l) ?><?php if ($k && isset($counts[$k])): ?> <span class="badge bg-light text-dark"><?= $counts[$k] ?></span><?php endif; ?>
    </a>
  <?php endforeach; ?>
</div>
<div class="card"><div class="table-responsive">
<table class="table table-sm align-middle mb-0">
  <thead class="table-light"><tr><th>Rating</th><th>Customer</th><th>For</th><th>Comment</th><th>Status</th><th>Date</th><th></th></tr></thead>
  <tbody>
  <?php if (!$reviews): ?><tr><td colspan="7" class="text-center text-muted py-3">No reviews here.</td></tr><?php endif; ?>
  <?php foreach ($reviews as $r): ?>
    <tr>
      <td style="color:#f59e0b;white-space:nowrap"><?php for($i=1;$i<=5;$i++): ?><i class="bi bi-star<?= (int)$r['rating']>=$i?'-fill':'' ?>"></i><?php endfor; ?></td>
      <td><?= e($r['customer_name']) ?><br><small class="text-muted"><?= e($r['customer_mobile']) ?></small></td>
      <td><small><?= e($r['vehicle_name'] ?: $r['package_name']) ?><br><span class="badge bg-dark"><?= e($r['booking_code']) ?></span></small></td>
      <td style="max-width:320px"><small><?= e($r['comment']) ?></small>
        <?php if ($r['admin_reply']): ?><div class="small text-primary mt-1">↳ <?= e($r['admin_reply']) ?></div><?php endif; ?></td>
      <td><span class="badge bg-<?= $r['status']==='approved'?'success':($r['status']==='rejected'?'danger':'warning') ?>"><?= e($r['status']) ?></span></td>
      <td><small><?= e(date('d M', strtotime($r['created_at']))) ?></small></td>
      <td class="text-nowrap">
        <form method="post" action="<?= e(base_url('/admin/reviews/'.$r['id'].'/moderate')) ?>" class="d-inline"><?= csrf_field() ?>
          <?php if ($r['status'] !== 'approved'): ?><button name="action" value="approve" class="btn btn-sm btn-success"><i class="bi bi-check"></i></button><?php endif; ?>
          <?php if ($r['status'] !== 'rejected'): ?><button name="action" value="reject" class="btn btn-sm btn-outline-warning"><i class="bi bi-x"></i></button><?php endif; ?>
          <button name="action" value="delete" class="btn btn-sm btn-outline-danger" onclick="return confirm('Delete this review?')"><i class="bi bi-trash"></i></button>
        </form>
        <button class="btn btn-sm btn-outline-primary" data-bs-toggle="modal" data-bs-target="#rep<?= $r['id'] ?>"><i class="bi bi-reply"></i></button>
        <div class="modal fade" id="rep<?= $r['id'] ?>"><div class="modal-dialog"><div class="modal-content">
          <form method="post" action="<?= e(base_url('/admin/reviews/'.$r['id'].'/moderate')) ?>"><?= csrf_field() ?><input type="hidden" name="action" value="reply">
            <div class="modal-header"><h6 class="modal-title">Reply publicly</h6><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
            <div class="modal-body text-start"><textarea name="admin_reply" class="form-control" rows="3"><?= e($r['admin_reply']) ?></textarea></div>
            <div class="modal-footer"><button class="btn btn-primary w-100">Save reply</button></div>
          </form>
        </div></div></div>
      </td>
    </tr>
  <?php endforeach; ?>
  </tbody>
</table>
</div></div>
