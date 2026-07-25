<?php /** @var array $vehicles, $categories, $filters, bool $gu */ ?>
<div class="d-flex justify-content-between align-items-center mb-2">
  <h5 class="mb-0"><?= e(__('vehicles')) ?></h5>
  <button class="btn btn-sm btn-outline-secondary" data-bs-toggle="collapse" data-bs-target="#filters"><i class="bi bi-funnel"></i> Filter</button>
</div>

<form method="get" class="collapse show mb-3" id="filters">
  <div class="card card-body">
    <div class="row g-2">
      <div class="col-6 col-md-3">
        <select name="category" class="form-select form-select-sm">
          <option value="">All categories</option>
          <?php foreach ($categories as $c): ?><option value="<?= e($c['slug']) ?>" <?= $filters['category']===$c['slug']?'selected':'' ?>><?= e($gu && $c['name_gu'] ? $c['name_gu'] : $c['name']) ?></option><?php endforeach; ?>
        </select>
      </div>
      <div class="col-6 col-md-3">
        <select name="transmission" class="form-select form-select-sm">
          <option value="">Any transmission</option>
          <?php foreach (['gear'=>'Gear','non_gear'=>'Non-Gear','manual'=>'Manual','automatic'=>'Automatic'] as $k=>$l): ?><option value="<?= $k ?>" <?= $filters['transmission']===$k?'selected':'' ?>><?= $l ?></option><?php endforeach; ?>
        </select>
      </div>
      <div class="col-6 col-md-3">
        <select name="fuel" class="form-select form-select-sm">
          <option value="">Any fuel</option>
          <?php foreach (['petrol','diesel','ev','cng'] as $k): ?><option value="<?= $k ?>" <?= $filters['fuel']===$k?'selected':'' ?>><?= ucfirst($k) ?></option><?php endforeach; ?>
        </select>
      </div>
      <div class="col-6 col-md-3">
        <select name="sort" class="form-select form-select-sm">
          <option value="">Sort</option>
          <option value="price_low" <?= $filters['sort']==='price_low'?'selected':'' ?>>Price: Low to High</option>
          <option value="price_high" <?= $filters['sort']==='price_high'?'selected':'' ?>>Price: High to Low</option>
        </select>
      </div>
      <div class="col-12"><button class="btn btn-primary btn-sm w-100 tap"><i class="bi bi-search"></i> <?= e(__('search')) ?></button></div>
    </div>
  </div>
</form>

<div class="row g-3">
  <?php if (!$vehicles): ?><div class="col-12 text-center text-muted py-5">No vehicles match your filters.</div><?php endif; ?>
  <?php foreach ($vehicles as $v): $avail = $v['available_now'] ?? null; ?>
    <div class="col-6 col-lg-3">
      <div class="card h-100 veh-card shadow-sm">
        <div class="position-relative">
          <img src="<?= $v['main_image'] ? e(upload_url($v['main_image'])) : 'https://placehold.co/400x300?text='.urlencode($v['name']) ?>" loading="lazy" alt="<?= e($v['name']) ?>">
          <?php if ($avail !== null): ?>
            <span class="badge bg-<?= $avail?'success':'danger' ?> position-absolute top-0 end-0 m-2"><?= $avail?e(__('available')):e(__('not_available')) ?></span>
          <?php endif; ?>
        </div>
        <div class="card-body p-2">
          <div class="fw-semibold small"><?= e($gu && $v['name_gu'] ? $v['name_gu'] : $v['name']) ?></div>
          <div class="text-muted" style="font-size:11px"><?= e($v['category_name']) ?> · <?= e(str_replace('_',' ',$v['transmission'])) ?></div>
          <div class="text-primary fw-bold mt-1"><?= money($v['price_day']) ?><span class="fw-normal text-muted" style="font-size:11px">/<?= e(__('per_day')) ?></span></div>
          <?php if ($v['price_hour']>0): ?><div class="text-muted" style="font-size:11px"><?= money($v['price_hour']) ?>/<?= e(__('per_hour')) ?></div><?php endif; ?>
          <a href="<?= e(base_url('/vehicle/'.$v['id'])) ?>" class="btn btn-primary btn-sm w-100 mt-2 tap"><?= e(__('book_now')) ?></a>
        </div>
      </div>
    </div>
  <?php endforeach; ?>
</div>
