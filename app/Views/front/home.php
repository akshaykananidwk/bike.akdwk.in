<?php
/** Customer home. Expects: $categories, $vehicles, $gu */
?>
<div class="text-center py-3">
  <h4 class="fw-bold"><?= e(__('tagline')) ?></h4>
</div>

<!-- Hero search -->
<div class="card shadow-sm mb-4">
  <div class="card-body">
    <form action="<?= e(base_url('/vehicles')) ?>" method="get" class="row g-2">
      <div class="col-12 col-md-4">
        <label class="form-label small"><?= e(__('vehicles')) ?></label>
        <select name="category" class="form-select">
          <option value=""><?= e(__('vehicles')) ?></option>
          <?php foreach ($categories as $c): ?>
            <option value="<?= e($c['slug']) ?>"><?= e($gu && $c['name_gu'] ? $c['name_gu'] : $c['name']) ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="col-6 col-md-3">
        <label class="form-label small"><?= e(__('pickup_date')) ?></label>
        <input type="datetime-local" name="pickup" class="form-control">
      </div>
      <div class="col-6 col-md-3">
        <label class="form-label small"><?= e(__('drop_date')) ?></label>
        <input type="datetime-local" name="drop" class="form-control">
      </div>
      <div class="col-12 col-md-2 d-flex align-items-end">
        <button class="btn btn-primary w-100 tap"><i class="bi bi-search"></i> <?= e(__('search')) ?></button>
      </div>
    </form>
  </div>
</div>

<!-- Categories -->
<div class="d-flex gap-2 overflow-auto pb-2 mb-3">
  <?php foreach ($categories as $c): ?>
    <a href="<?= e(base_url('/vehicles?category=' . $c['slug'])) ?>" class="btn btn-outline-primary btn-sm flex-shrink-0 tap">
      <i class="bi <?= e($c['icon'] ?: 'bi-scooter') ?>"></i> <?= e($gu && $c['name_gu'] ? $c['name_gu'] : $c['name']) ?>
    </a>
  <?php endforeach; ?>
</div>

<!-- Featured vehicles -->
<h6 class="fw-bold mb-2"><?= e(__('vehicles')) ?></h6>
<div class="row g-3">
  <?php if (!$vehicles): ?>
    <div class="col-12 text-center text-muted py-4">No vehicles available yet.</div>
  <?php endif; ?>
  <?php foreach ($vehicles as $v): ?>
    <div class="col-6 col-lg-3">
      <div class="card h-100 veh-card shadow-sm">
        <img src="<?= $v['main_image'] ? e(upload_url($v['main_image'])) : 'https://placehold.co/400x300?text=' . urlencode($v['name']) ?>" alt="<?= e($v['name']) ?>" loading="lazy">
        <div class="card-body p-2">
          <div class="fw-semibold small"><?= e($gu && $v['name_gu'] ? $v['name_gu'] : $v['name']) ?></div>
          <div class="text-muted" style="font-size:12px"><?= e($v['category_name']) ?></div>
          <div class="text-primary fw-bold mt-1"><?= money($v['price_day']) ?><span class="text-muted fw-normal" style="font-size:11px">/<?= e(__('per_day')) ?></span></div>
          <a href="<?= e(base_url('/vehicle/' . $v['id'])) ?>" class="btn btn-primary btn-sm w-100 mt-2 tap"><?= e(__('book_now')) ?></a>
        </div>
      </div>
    </div>
  <?php endforeach; ?>
</div>
