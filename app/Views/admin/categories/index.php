<div class="row g-3">
  <div class="col-md-5">
    <div class="card"><div class="card-header fw-bold" id="formTitle">Add Category</div><div class="card-body">
      <form method="post" id="catForm">
        <?= csrf_field() ?><input type="hidden" name="id" id="cat_id"><input type="hidden" name="action" value="save">
        <div class="mb-2"><label class="form-label">Name (English) *</label><input name="name" id="cat_name" class="form-control" required></div>
        <div class="mb-2"><label class="form-label">Name (ગુજરાતી)</label><input name="name_gu" id="cat_name_gu" class="form-control"></div>
        <div class="mb-2"><label class="form-label">Icon (Bootstrap Icon class)</label><input name="icon" id="cat_icon" class="form-control" placeholder="bi-scooter" value="bi-scooter"></div>
        <div class="row">
          <div class="col-6 mb-2"><label class="form-label">Sort</label><input name="sort_order" id="cat_sort" type="number" class="form-control" value="0"></div>
          <div class="col-6 mb-2"><label class="form-label">Status</label><select name="status" id="cat_status" class="form-select"><option value="active">Active</option><option value="inactive">Inactive</option></select></div>
        </div>
        <button class="btn btn-primary btn-sm"><i class="bi bi-check-lg"></i> Save</button>
        <button type="button" class="btn btn-outline-secondary btn-sm" onclick="resetCat()">Clear</button>
      </form>
    </div></div>
  </div>
  <div class="col-md-7">
    <div class="card"><div class="table-responsive">
    <table class="table table-sm align-middle mb-0">
      <thead class="table-light"><tr><th>#</th><th>Name</th><th>Icon</th><th>Status</th><th></th></tr></thead>
      <tbody>
      <?php foreach ($categories as $c): ?>
        <tr>
          <td><?= (int)$c['sort_order'] ?></td>
          <td><i class="bi <?= e($c['icon']) ?>"></i> <?= e($c['name']) ?> <small class="text-muted"><?= e($c['name_gu']) ?></small></td>
          <td><code><?= e($c['icon']) ?></code></td>
          <td><span class="badge bg-<?= $c['status']==='active'?'success':'secondary' ?>"><?= e($c['status']) ?></span></td>
          <td class="text-nowrap">
            <button class="btn btn-sm btn-outline-primary" onclick='editCat(<?= json_encode($c) ?>)'><i class="bi bi-pencil"></i></button>
            <form method="post" class="d-inline" onsubmit="return confirm('Delete category?')"><?= csrf_field() ?><input type="hidden" name="action" value="delete"><input type="hidden" name="id" value="<?= (int)$c['id'] ?>"><button class="btn btn-sm btn-outline-danger"><i class="bi bi-trash"></i></button></form>
          </td>
        </tr>
      <?php endforeach; ?>
      </tbody>
    </table>
    </div></div>
  </div>
</div>
<script>
function editCat(c){
  document.getElementById('formTitle').textContent='Edit Category';
  cat_id.value=c.id; cat_name.value=c.name; cat_name_gu.value=c.name_gu||''; cat_icon.value=c.icon||'';
  cat_sort.value=c.sort_order; cat_status.value=c.status; window.scrollTo(0,0);
}
function resetCat(){document.getElementById('catForm').reset();cat_id.value='';document.getElementById('formTitle').textContent='Add Category';}
</script>
