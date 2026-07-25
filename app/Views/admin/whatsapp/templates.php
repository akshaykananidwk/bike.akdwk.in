<?php /** @var array $templates */
$vars = '{customer_name} {booking_code} {vehicle_name} {pickup_time} {drop_time} {amount} {balance} {shop_name} {today_commission} {total_commission} {agency_mobile} {map_link} {receipt_link}';
?>
<ul class="nav nav-tabs mb-3">
  <li class="nav-item"><a class="nav-link" href="<?= e(base_url('/admin/whatsapp')) ?>">Settings</a></li>
  <li class="nav-item"><a class="nav-link active" href="<?= e(base_url('/admin/whatsapp/templates')) ?>">Templates</a></li>
  <li class="nav-item"><a class="nav-link" href="<?= e(base_url('/admin/whatsapp/queue')) ?>">Queue</a></li>
  <li class="nav-item"><a class="nav-link" href="<?= e(base_url('/admin/whatsapp/inbox')) ?>">Inbox</a></li>
</ul>
<div class="alert alert-info small"><strong>Variables:</strong> <code><?= e($vars) ?></code></div>
<div class="row g-2">
<?php foreach ($templates as $t): ?>
  <div class="col-md-6">
    <form method="post" class="card"><div class="card-body">
      <?= csrf_field() ?><input type="hidden" name="id" value="<?= (int)$t['id'] ?>">
      <div class="d-flex justify-content-between align-items-center mb-1">
        <div><span class="badge bg-dark"><?= e($t['key']) ?></span> <span class="badge bg-<?= $t['locale']==='gu'?'warning':'secondary' ?>"><?= e($t['locale']) ?></span></div>
        <div class="form-check form-switch"><input type="checkbox" name="is_active" value="1" class="form-check-input" <?= $t['is_active']?'checked':'' ?>></div>
      </div>
      <textarea name="body" class="form-control" rows="3" style="font-size:13px"><?= e($t['body']) ?></textarea>
      <button class="btn btn-sm btn-primary mt-2">Save</button>
    </div></form>
  </div>
<?php endforeach; ?>
</div>
