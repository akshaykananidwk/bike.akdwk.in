<?php $p = $pkg ?? []; $val = fn($k,$d='') => e($p[$k] ?? $d); ?>
<form method="post" enctype="multipart/form-data">
  <?= csrf_field() ?>
  <div class="row g-3">
    <div class="col-md-8">
      <div class="card"><div class="card-header fw-bold">Package Details</div><div class="card-body row g-3">
        <div class="col-md-4"><label class="form-label">Code</label><input class="form-control" value="<?= e($p['code'] ?? $code) ?>" readonly></div>
        <div class="col-md-8"><label class="form-label">Package Name *</label><input name="name" class="form-control" value="<?= $val('name') ?>" required placeholder="e.g. Dwarka Local Darshan"></div>
        <div class="col-md-4"><label class="form-label">Type</label><select name="type" class="form-select">
          <?php foreach (['darshan'=>'Darshan Tour','outstation'=>'Outstation','transfer'=>'Airport / Station Transfer','custom'=>'Custom'] as $k=>$l): ?>
            <option value="<?= $k ?>" <?= ($p['type'] ?? 'darshan')===$k?'selected':'' ?>><?= $l ?></option><?php endforeach; ?>
        </select></div>
        <div class="col-md-4"><label class="form-label">From</label><input name="from_location" class="form-control" value="<?= $val('from_location','Dwarka') ?>"></div>
        <div class="col-md-4"><label class="form-label">To</label><input name="to_location" class="form-control" value="<?= $val('to_location') ?>"></div>
        <div class="col-12"><label class="form-label">Short description</label><input name="short_desc" class="form-control" value="<?= $val('short_desc') ?>" placeholder="One line shown on the card"></div>
        <div class="col-12"><label class="form-label">Full description</label><textarea name="description" class="form-control" rows="3"><?= $val('description') ?></textarea></div>
        <div class="col-12"><label class="form-label">Places covered <small class="text-muted">(comma separated)</small></label>
          <input name="places" class="form-control" value="<?= $val('places') ?>" placeholder="Dwarkadhish Temple, Gomti Ghat, Nageshwar"></div>
        <div class="col-md-6"><label class="form-label">Inclusions <small class="text-muted">(comma separated)</small></label>
          <input name="inclusions" class="form-control" value="<?= $val('inclusions') ?>" placeholder="Car with driver, fuel, tolls"></div>
        <div class="col-md-6"><label class="form-label">Exclusions <small class="text-muted">(comma separated)</small></label>
          <input name="exclusions" class="form-control" value="<?= $val('exclusions') ?>" placeholder="Food, donations, entry tickets"></div>
        <div class="col-md-6"><label class="form-label">Cover image</label><input type="file" name="image" class="form-control" accept="image/*">
          <?php if (!empty($p['image'])): ?><img src="<?= e(upload_url($p['image'])) ?>" class="img-fluid mt-2 rounded border" style="max-height:110px"><?php endif; ?></div>
        <div class="col-md-6"><label class="form-label">Agency (optional)</label><select name="agency_id" class="form-select"><option value="">— platform —</option>
          <?php foreach ($agencies as $a): ?><option value="<?= $a['id'] ?>" <?= ($p['agency_id'] ?? '')==$a['id']?'selected':'' ?>><?= e($a['name']) ?></option><?php endforeach; ?>
        </select></div>
      </div></div>
    </div>

    <div class="col-md-4">
      <div class="card"><div class="card-header fw-bold">Pricing</div><div class="card-body row g-2">
        <div class="col-6"><label class="form-label">Price ₹ *</label><input name="price" type="number" step="0.01" class="form-control" value="<?= $val('price','0') ?>" required></div>
        <div class="col-6"><label class="form-label">Strike price ₹</label><input name="strike_price" type="number" step="0.01" class="form-control" value="<?= $val('strike_price') ?>"></div>
        <div class="col-12"><label class="form-label">Advance % to confirm</label><input name="advance_percent" type="number" step="0.01" class="form-control" value="<?= $val('advance_percent','30') ?>"></div>
      </div></div>
      <div class="card mt-3"><div class="card-header fw-bold">Trip Details</div><div class="card-body row g-2">
        <div class="col-12"><label class="form-label">Duration text</label><input name="duration_text" class="form-control" value="<?= $val('duration_text') ?>" placeholder="8-9 hours"></div>
        <div class="col-6"><label class="form-label">Included km</label><input name="included_km" type="number" class="form-control" value="<?= $val('included_km') ?>"></div>
        <div class="col-6"><label class="form-label">Extra ₹/km</label><input name="extra_km_rate" type="number" step="0.01" class="form-control" value="<?= $val('extra_km_rate','0') ?>"></div>
        <div class="col-7"><label class="form-label">Vehicle type</label><input name="vehicle_type" class="form-control" value="<?= $val('vehicle_type') ?>" placeholder="Sedan (4 seater)"></div>
        <div class="col-5"><label class="form-label">Seats</label><input name="seats" type="number" class="form-control" value="<?= $val('seats') ?>"></div>
        <div class="col-6"><label class="form-label">Sort</label><input name="sort_order" type="number" class="form-control" value="<?= $val('sort_order','0') ?>"></div>
        <div class="col-6"><label class="form-label">Status</label><select name="status" class="form-select">
          <?php foreach (['active','inactive'] as $k): ?><option value="<?= $k ?>" <?= ($p['status'] ?? 'active')===$k?'selected':'' ?>><?= ucfirst($k) ?></option><?php endforeach; ?>
        </select></div>
      </div></div>
    </div>
  </div>
  <div class="mt-3"><button class="btn btn-primary"><i class="bi bi-check-lg"></i> Save Package</button>
    <a href="<?= e(base_url('/admin/packages')) ?>" class="btn btn-outline-secondary">Cancel</a></div>
</form>
