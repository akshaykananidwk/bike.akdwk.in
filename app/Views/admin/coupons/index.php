<?php /** @var array $coupons */ ?>
<div class="row g-3">
  <div class="col-md-4">
    <form method="post" class="card"><div class="card-header fw-bold" id="cTitle">Add Coupon</div><div class="card-body">
      <?= csrf_field() ?><input type="hidden" name="id" id="c_id"><input type="hidden" name="action" value="save">
      <div class="mb-2"><label class="form-label">Code</label><input name="code" id="c_code" class="form-control text-uppercase" required></div>
      <div class="row g-2">
        <div class="col-6 mb-2"><label class="form-label">Type</label><select name="type" id="c_type" class="form-select"><option value="percent">Percent %</option><option value="fixed">Fixed ₹</option></select></div>
        <div class="col-6 mb-2"><label class="form-label">Value</label><input name="value" id="c_value" type="number" step="0.01" class="form-control" required></div>
      </div>
      <div class="row g-2">
        <div class="col-6 mb-2"><label class="form-label">Min Amount</label><input name="min_amount" id="c_min" type="number" step="0.01" class="form-control" value="0"></div>
        <div class="col-6 mb-2"><label class="form-label">Max Discount</label><input name="max_discount" id="c_max" type="number" step="0.01" class="form-control"></div>
      </div>
      <div class="mb-2"><label class="form-label">Usage Limit</label><input name="usage_limit" id="c_limit" type="number" class="form-control"></div>
      <div class="row g-2">
        <div class="col-6 mb-2"><label class="form-label">Starts</label><input name="starts_at" id="c_starts" type="datetime-local" class="form-control"></div>
        <div class="col-6 mb-2"><label class="form-label">Ends</label><input name="ends_at" id="c_ends" type="datetime-local" class="form-control"></div>
      </div>
      <div class="mb-2"><label class="form-label">Status</label><select name="status" id="c_status" class="form-select"><option value="active">Active</option><option value="inactive">Inactive</option></select></div>
      <button class="btn btn-primary btn-sm">Save</button>
      <button type="button" class="btn btn-outline-secondary btn-sm" onclick="document.querySelector('form').reset();c_id.value='';cTitle.textContent='Add Coupon'">Clear</button>
    </div></form>
  </div>
  <div class="col-md-8"><div class="card"><div class="table-responsive"><table class="table table-sm align-middle mb-0">
    <thead class="table-light"><tr><th>Code</th><th>Discount</th><th>Min</th><th>Used</th><th>Valid</th><th>Status</th><th></th></tr></thead>
    <tbody>
    <?php if (!$coupons): ?><tr><td colspan="7" class="text-center text-muted py-3">No coupons.</td></tr><?php endif; ?>
    <?php foreach ($coupons as $c): ?>
      <tr>
        <td><span class="badge bg-dark"><?= e($c['code']) ?></span></td>
        <td><?= $c['type']==='percent' ? $c['value'].'%' : money($c['value']) ?><?= $c['max_discount']?' (max '.money($c['max_discount']).')':'' ?></td>
        <td><?= money($c['min_amount']) ?></td>
        <td><?= (int)$c['used_count'] ?><?= $c['usage_limit']!==null?'/'.$c['usage_limit']:'' ?></td>
        <td style="font-size:11px"><?= $c['ends_at']?e(date('d M Y',strtotime($c['ends_at']))):'—' ?></td>
        <td><span class="badge bg-<?= $c['status']==='active'?'success':'secondary' ?>"><?= e($c['status']) ?></span></td>
        <td class="text-nowrap">
          <button class="btn btn-sm btn-outline-primary" onclick='editC(<?= json_encode($c) ?>)'><i class="bi bi-pencil"></i></button>
          <form method="post" class="d-inline" onsubmit="return confirm('Delete?')"><?= csrf_field() ?><input type="hidden" name="action" value="delete"><input type="hidden" name="id" value="<?= (int)$c['id'] ?>"><button class="btn btn-sm btn-outline-danger"><i class="bi bi-trash"></i></button></form>
        </td>
      </tr>
    <?php endforeach; ?>
    </tbody>
  </table></div></div></div>
</div>
<script>
function editC(c){cTitle.textContent='Edit Coupon';c_id.value=c.id;c_code.value=c.code;c_type.value=c.type;c_value.value=c.value;c_min.value=c.min_amount;c_max.value=c.max_discount||'';c_limit.value=c.usage_limit||'';c_status.value=c.status;c_starts.value=c.starts_at?c.starts_at.replace(' ','T').slice(0,16):'';c_ends.value=c.ends_at?c.ends_at.replace(' ','T').slice(0,16):'';window.scrollTo(0,0);}
</script>
