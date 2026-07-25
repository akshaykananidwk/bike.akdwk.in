<?php /** @var array $staff,$permissions */ ?>
<div class="row g-3">
  <div class="col-md-5">
    <form method="post" class="card"><div class="card-header fw-bold" id="sTitle">Add Staff</div><div class="card-body">
      <?= csrf_field() ?><input type="hidden" name="id" id="s_id"><input type="hidden" name="action" value="save">
      <div class="mb-2"><label class="form-label">Name</label><input name="name" id="s_name" class="form-control" required></div>
      <div class="mb-2"><label class="form-label">Mobile (login)</label><input name="mobile" id="s_mobile" class="form-control" required></div>
      <div class="mb-2"><label class="form-label">Email</label><input name="email" id="s_email" type="email" class="form-control"></div>
      <div class="mb-2"><label class="form-label">Password <small class="text-muted">(blank = keep)</small></label><input name="password" id="s_pass" type="password" class="form-control"></div>
      <label class="form-label">Permissions</label>
      <div class="row g-1 mb-2">
        <?php foreach ($permissions as $k=>$l): ?>
          <div class="col-6"><div class="form-check"><input type="checkbox" name="permissions[]" value="<?= $k ?>" class="form-check-input perm" id="p_<?= $k ?>"><label class="form-check-label small" for="p_<?= $k ?>"><?= e($l) ?></label></div></div>
        <?php endforeach; ?>
      </div>
      <div class="mb-2"><label class="form-label">Status</label><select name="status" id="s_status" class="form-select"><option value="active">Active</option><option value="inactive">Inactive</option></select></div>
      <button class="btn btn-primary btn-sm">Save</button>
      <button type="button" class="btn btn-outline-secondary btn-sm" onclick="resetS()">Clear</button>
    </div></form>
  </div>
  <div class="col-md-7"><div class="card"><div class="table-responsive"><table class="table table-sm align-middle mb-0">
    <thead class="table-light"><tr><th>Name</th><th>Role</th><th>Mobile</th><th>Permissions</th><th>Status</th><th></th></tr></thead>
    <tbody>
    <?php foreach ($staff as $s): $perms = json_decode($s['permissions'] ?? '[]', true) ?: []; ?>
      <tr>
        <td><?= e($s['name']) ?></td>
        <td><span class="badge bg-<?= $s['role']==='super_admin'?'dark':'secondary' ?>"><?= e($s['role']) ?></span></td>
        <td><?= e($s['mobile']) ?></td>
        <td style="font-size:11px"><?= $s['role']==='super_admin'?'All':e(implode(', ', $perms)) ?></td>
        <td><span class="badge bg-<?= $s['status']==='active'?'success':'secondary' ?>"><?= e($s['status']) ?></span></td>
        <td class="text-nowrap">
          <?php if ($s['role']==='staff'): ?>
            <button class="btn btn-sm btn-outline-primary" onclick='editS(<?= json_encode(['id'=>$s['id'],'name'=>$s['name'],'mobile'=>$s['mobile'],'email'=>$s['email'],'status'=>$s['status'],'permissions'=>$perms]) ?>)'><i class="bi bi-pencil"></i></button>
            <form method="post" class="d-inline" onsubmit="return confirm('Remove staff?')"><?= csrf_field() ?><input type="hidden" name="action" value="delete"><input type="hidden" name="id" value="<?= (int)$s['id'] ?>"><button class="btn btn-sm btn-outline-danger"><i class="bi bi-trash"></i></button></form>
          <?php endif; ?>
        </td>
      </tr>
    <?php endforeach; ?>
    </tbody>
  </table></div></div></div>
</div>
<script>
function resetS(){document.querySelector('form').reset();s_id.value='';sTitle.textContent='Add Staff';document.querySelectorAll('.perm').forEach(c=>c.checked=false);}
function editS(s){sTitle.textContent='Edit Staff';s_id.value=s.id;s_name.value=s.name;s_mobile.value=s.mobile;s_email.value=s.email||'';s_status.value=s.status;document.querySelectorAll('.perm').forEach(c=>c.checked=(s.permissions||[]).includes(c.value));window.scrollTo(0,0);}
</script>
