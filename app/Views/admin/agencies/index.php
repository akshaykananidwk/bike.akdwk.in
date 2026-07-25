<div class="d-flex justify-content-between align-items-center mb-3">
  <h5 class="mb-0">Agencies / Vehicle Owners</h5>
  <a href="<?= e(base_url('/admin/agencies/create')) ?>" class="btn btn-sm btn-primary"><i class="bi bi-plus-lg"></i> Add Agency</a>
</div>
<div class="card"><div class="table-responsive">
<table class="table table-sm table-hover align-middle mb-0">
  <thead class="table-light"><tr><th>Code</th><th>Name</th><th>Mobile</th><th>UPI</th><th>Vehicles</th><th>Wallet</th><th>Status</th><th></th></tr></thead>
  <tbody>
  <?php if (!$agencies): ?><tr><td colspan="8" class="text-center text-muted py-3">No agencies yet.</td></tr><?php endif; ?>
  <?php foreach ($agencies as $a): ?>
    <tr>
      <td><span class="badge bg-dark"><?= e($a['code']) ?></span></td>
      <td><?= e($a['name']) ?><?php if ($a['name_gu']): ?><br><small class="text-muted"><?= e($a['name_gu']) ?></small><?php endif; ?></td>
      <td><?= e($a['mobile']) ?></td>
      <td><?= $a['upi_qr_image'] ? '<i class="bi bi-qr-code text-success"></i>' : ($a['upi_id'] ? e($a['upi_id']) : '<span class="text-muted">—</span>') ?></td>
      <td><?= (int)$a['vehicles'] ?></td>
      <td><?= money($a['wallet'] ?? 0) ?></td>
      <td><span class="badge bg-<?= $a['status']==='active'?'success':'secondary' ?>"><?= e($a['status']) ?></span></td>
      <td class="text-nowrap"><div class="btn-group btn-group-sm">
        <a class="btn btn-outline-dark" href="<?= e(base_url('/admin/agencies/'.$a['id'].'/login-as')) ?>" title="Login as this agency"><i class="bi bi-box-arrow-in-right"></i></a>
        <a class="btn btn-outline-primary" href="<?= e(base_url('/admin/agencies/'.$a['id'].'/edit')) ?>"><i class="bi bi-pencil"></i></a>
        <form method="post" action="<?= e(base_url('/admin/agencies/'.$a['id'].'/delete')) ?>" onsubmit="return confirm('Delete this agency?')"><?= csrf_field() ?><button class="btn btn-outline-danger"><i class="bi bi-trash"></i></button></form>
      </div></td>
    </tr>
  <?php endforeach; ?>
  </tbody>
</table>
</div></div>
