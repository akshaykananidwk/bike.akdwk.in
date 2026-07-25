<div class="d-flex justify-content-between align-items-center mb-3">
  <h5 class="mb-0">Vehicles</h5>
  <a href="<?= e(base_url('/admin/vehicles/create')) ?>" class="btn btn-sm btn-primary"><i class="bi bi-plus-lg"></i> Add Vehicle</a>
</div>
<div class="card"><div class="table-responsive">
<table class="table table-sm table-hover align-middle mb-0">
  <thead class="table-light"><tr><th>Image</th><th>Name</th><th>Category</th><th>Agency</th><th>Type</th><th>Day</th><th>Hour</th><th>Deposit</th><th>Units</th><th>Status</th><th></th></tr></thead>
  <tbody>
  <?php if (!$vehicles): ?><tr><td colspan="11" class="text-center text-muted py-3">No vehicles yet.</td></tr><?php endif; ?>
  <?php foreach ($vehicles as $v): ?>
    <tr>
      <td><img src="<?= $v['main_image'] ? e(upload_url($v['main_image'])) : 'https://placehold.co/60x45?text=%20' ?>" style="width:60px;height:45px;object-fit:cover;border-radius:6px"></td>
      <td><?= e($v['name']) ?><?php if ($v['reg_number']): ?><br><small class="text-muted"><?= e($v['reg_number']) ?></small><?php endif; ?></td>
      <td><?= e($v['category_name']) ?></td>
      <td><?= e($v['agency_name']) ?></td>
      <td><small><?= e(str_replace('_',' ',$v['transmission'])) ?> · <?= e($v['fuel']) ?></small></td>
      <td><?= money($v['price_day']) ?></td>
      <td><?= money($v['price_hour']) ?></td>
      <td><?= money($v['deposit']) ?></td>
      <td><?= (int)$v['units'] ?></td>
      <td><span class="badge bg-<?= $v['status']==='active'?'success':'secondary' ?>"><?= e($v['status']) ?></span></td>
      <td class="text-nowrap"><div class="btn-group btn-group-sm">
        <a class="btn btn-outline-primary" href="<?= e(base_url('/admin/vehicles/'.$v['id'].'/edit')) ?>"><i class="bi bi-pencil"></i></a>
        <form method="post" action="<?= e(base_url('/admin/vehicles/'.$v['id'].'/delete')) ?>" onsubmit="return confirm('Delete vehicle?')"><?= csrf_field() ?><button class="btn btn-outline-danger"><i class="bi bi-trash"></i></button></form>
      </div></td>
    </tr>
  <?php endforeach; ?>
  </tbody>
</table>
</div></div>
