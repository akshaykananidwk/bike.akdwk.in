<?php $v = $vehicle ?? []; $val = fn($k,$d='') => e($v[$k] ?? old($k,$d));
$errs = \App\Core\Session::flash('errors') ?? []; ?>
<?php if ($errs): ?><div class="alert alert-danger"><?php foreach ($errs as $e): ?><div><?= e($e[0]) ?></div><?php endforeach; ?></div><?php endif; ?>
<form method="post" enctype="multipart/form-data">
  <?= csrf_field() ?>
  <div class="row g-3">
    <div class="col-md-8"><div class="card"><div class="card-header fw-bold">Vehicle Details</div><div class="card-body row g-3">
      <div class="col-md-6"><label class="form-label">Name (English) *</label><input name="name" class="form-control" value="<?= $val('name') ?>" required></div>
      <div class="col-md-6"><label class="form-label">Name (ગુજરાતી)</label><input name="name_gu" class="form-control" value="<?= $val('name_gu') ?>"></div>
      <div class="col-md-4"><label class="form-label">Category</label><select name="category_id" class="form-select"><option value="">—</option><?php foreach ($categories as $c): ?><option value="<?= $c['id'] ?>" <?= ($v['category_id'] ?? '')==$c['id']?'selected':'' ?>><?= e($c['name']) ?></option><?php endforeach; ?></select></div>
      <div class="col-md-4"><label class="form-label">Agency</label><select name="agency_id" class="form-select"><option value="">—</option><?php foreach ($agencies as $a): ?><option value="<?= $a['id'] ?>" <?= ($v['agency_id'] ?? '')==$a['id']?'selected':'' ?>><?= e($a['name']) ?></option><?php endforeach; ?></select></div>
      <div class="col-md-4"><label class="form-label">Reg. Number</label><input name="reg_number" class="form-control" value="<?= $val('reg_number') ?>"></div>
      <div class="col-md-4"><label class="form-label">Brand</label><input name="brand" class="form-control" value="<?= $val('brand') ?>"></div>
      <div class="col-md-4"><label class="form-label">Model</label><input name="model" class="form-control" value="<?= $val('model') ?>"></div>
      <div class="col-md-4"><label class="form-label">Seats</label><input name="seats" type="number" class="form-control" value="<?= $val('seats') ?>"></div>
      <div class="col-md-4"><label class="form-label">Transmission</label><select name="transmission" class="form-select"><?php foreach (['na'=>'N/A','gear'=>'Gear','non_gear'=>'Non-Gear','manual'=>'Manual','automatic'=>'Automatic'] as $k=>$l): ?><option value="<?= $k ?>" <?= ($v['transmission'] ?? 'na')===$k?'selected':'' ?>><?= $l ?></option><?php endforeach; ?></select></div>
      <div class="col-md-4"><label class="form-label">Fuel</label><select name="fuel" class="form-select"><?php foreach (['petrol','diesel','ev','cng','none'] as $k): ?><option value="<?= $k ?>" <?= ($v['fuel'] ?? 'petrol')===$k?'selected':'' ?>><?= ucfirst($k) ?></option><?php endforeach; ?></select></div>
      <div class="col-md-4"><label class="form-label">Units (identical)</label><input name="units" type="number" min="1" class="form-control" value="<?= $val('units','1') ?>"></div>
      <div class="col-12"><label class="form-label">Description</label><textarea name="description" class="form-control" rows="2"><?= $val('description') ?></textarea></div>
    </div></div>

    <div class="card mt-3"><div class="card-header fw-bold">Images</div><div class="card-body row g-3">
      <div class="col-md-6"><label class="form-label">Main Image</label><input type="file" name="main_image" accept="image/*" class="form-control">
        <?php if (!empty($v['main_image'])): ?><img src="<?= e(upload_url($v['main_image'])) ?>" class="img-fluid mt-2 rounded border" style="max-height:120px"><?php endif; ?></div>
      <div class="col-md-6"><label class="form-label">Gallery (multiple)</label><input type="file" name="gallery[]" accept="image/*" class="form-control" multiple>
        <?php if (!empty($images)): ?><div class="d-flex gap-1 mt-2 flex-wrap"><?php foreach ($images as $img): ?><img src="<?= e(upload_url($img['image'])) ?>" style="height:50px" class="rounded border"><?php endforeach; ?></div><?php endif; ?></div>
    </div></div>
    </div>

    <div class="col-md-4">
      <div class="card"><div class="card-header fw-bold">Pricing Slabs (₹)</div><div class="card-body row g-2">
        <div class="col-6"><label class="form-label">Per Hour</label><input name="price_hour" type="number" step="0.01" class="form-control" value="<?= $val('price_hour','0') ?>"></div>
        <div class="col-6"><label class="form-label">Per Day *</label><input name="price_day" type="number" step="0.01" class="form-control" value="<?= $val('price_day','0') ?>" required></div>
        <div class="col-6"><label class="form-label">Per Week</label><input name="price_week" type="number" step="0.01" class="form-control" value="<?= $val('price_week','0') ?>"></div>
        <div class="col-6"><label class="form-label">Per Month</label><input name="price_month" type="number" step="0.01" class="form-control" value="<?= $val('price_month','0') ?>"></div>
        <div class="col-12"><label class="form-label">Security Deposit</label><input name="deposit" type="number" step="0.01" class="form-control" value="<?= $val('deposit','0') ?>"></div>
      </div></div>
      <div class="card mt-3"><div class="card-header fw-bold">Commission &amp; Status</div><div class="card-body">
        <label class="form-label">Commission Type</label>
        <select name="commission_type" class="form-select mb-2"><?php foreach (['inherit'=>'Inherit','percent'=>'Percentage %','fixed'=>'Fixed ₹'] as $k=>$l): ?><option value="<?= $k ?>" <?= ($v['commission_type'] ?? 'inherit')===$k?'selected':'' ?>><?= $l ?></option><?php endforeach; ?></select>
        <label class="form-label">Commission Value</label><input name="commission_value" type="number" step="0.01" class="form-control mb-2" value="<?= $val('commission_value','0') ?>">
        <div class="row"><div class="col-6"><label class="form-label">Sort</label><input name="sort_order" type="number" class="form-control" value="<?= $val('sort_order','0') ?>"></div>
        <div class="col-6"><label class="form-label">Status</label><select name="status" class="form-select"><?php foreach (['active','inactive'] as $k): ?><option value="<?= $k ?>" <?= ($v['status'] ?? 'active')===$k?'selected':'' ?>><?= ucfirst($k) ?></option><?php endforeach; ?></select></div></div>
      </div></div>
    </div>
  </div>
  <div class="mt-3"><button class="btn btn-primary"><i class="bi bi-check-lg"></i> Save Vehicle</button> <a href="<?= e(base_url('/admin/vehicles')) ?>" class="btn btn-outline-secondary">Cancel</a></div>
</form>
