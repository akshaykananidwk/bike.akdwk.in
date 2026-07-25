<?php /** @var array $packages */ ?>
<div class="d-flex justify-content-between align-items-center mb-3">
  <h5 class="mb-0">Tour Packages</h5>
  <a href="<?= e(base_url('/admin/packages/create')) ?>" class="btn btn-sm btn-primary"><i class="bi bi-plus-lg"></i> Add Package</a>
</div>
<div class="card"><div class="table-responsive">
<table class="table table-sm table-hover align-middle mb-0">
  <thead class="table-light"><tr><th>Code</th><th>Name</th><th>Type</th><th>Route</th><th>Duration</th><th>Price</th><th>Bookings</th><th>Status</th><th></th></tr></thead>
  <tbody>
  <?php if (!$packages): ?><tr><td colspan="9" class="text-center text-muted py-3">No packages yet.</td></tr><?php endif; ?>
  <?php foreach ($packages as $p): ?>
    <tr>
      <td><span class="badge bg-dark"><?= e($p['code']) ?></span></td>
      <td><?= e($p['name']) ?><br><small class="text-muted"><?= e(mb_substr((string)$p['short_desc'], 0, 50)) ?></small></td>
      <td><span class="badge bg-secondary"><?= e($p['type']) ?></span></td>
      <td><small><?= e($p['from_location']) ?><?= $p['to_location'] ? ' → ' . e($p['to_location']) : '' ?></small></td>
      <td><small><?= e($p['duration_text']) ?></small></td>
      <td class="fw-bold"><?= money($p['price']) ?></td>
      <td><?= (int)$p['bookings'] ?></td>
      <td><span class="badge bg-<?= $p['status']==='active'?'success':'secondary' ?>"><?= e($p['status']) ?></span></td>
      <td class="text-nowrap"><div class="btn-group btn-group-sm">
        <a class="btn btn-outline-secondary" href="<?= e(base_url('/package/'.$p['slug'])) ?>" target="_blank" title="View"><i class="bi bi-eye"></i></a>
        <a class="btn btn-outline-primary" href="<?= e(base_url('/admin/packages/'.$p['id'].'/edit')) ?>"><i class="bi bi-pencil"></i></a>
        <form method="post" action="<?= e(base_url('/admin/packages/'.$p['id'].'/delete')) ?>" onsubmit="return confirm('Delete this package?')"><?= csrf_field() ?><button class="btn btn-outline-danger"><i class="bi bi-trash"></i></button></form>
      </div></td>
    </tr>
  <?php endforeach; ?>
  </tbody>
</table>
</div></div>
