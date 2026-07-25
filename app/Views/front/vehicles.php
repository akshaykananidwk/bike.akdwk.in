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
          <?php foreach ($categories as $c): ?><option value="<?= e($c['slug']) ?>" <?= $filters['category']===$c['slug']?'selected':'' ?>><?= e($c['name']) ?></option><?php endforeach; ?>
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
      <div class="veh-card h-100">
        <div class="imgwrap d-flex align-items-center justify-content-center" style="background:linear-gradient(135deg,#eef2f8,#dbe4f0)">
          <?php if ($v['main_image']): ?>
            <img src="<?= e(upload_url($v['main_image'])) ?>" loading="lazy" alt="<?= e($v['name']) ?> on rent in Dwarka">
          <?php else: ?>
            <i class="bi bi-scooter" style="font-size:46px;color:#9db0c8"></i>
          <?php endif; ?>
          <span class="pill position-absolute top-0 start-0 m-2" style="background:rgba(0,0,0,.6);color:#fff"><?= e($v['category_name']) ?></span>
          <?php if ($avail !== null): ?>
            <span class="pill position-absolute top-0 end-0 m-2" style="background:<?= $avail?'var(--s)':'#dc3545' ?>;color:#fff"><?= $avail?e(__('available')):e(__('not_available')) ?></span>
          <?php endif; ?>
        </div>
        <div class="p-2 p-md-3">
          <div class="fw-semibold text-truncate"><?= e($v['name']) ?></div>
          <div class="text-muted" style="font-size:11px"><?= e(str_replace('_',' ',$v['transmission'])) ?> · <?= e($v['fuel']) ?></div>
          <div class="mt-1"><span class="price-tag fs-5"><?= money($v['price_day']) ?></span><span class="text-muted small">/<?= e(__('per_day')) ?></span>
            <?php if ($v['price_hour']>0): ?><span class="text-muted small ms-1">· <?= money($v['price_hour']) ?>/<?= e(__('per_hour')) ?></span><?php endif; ?></div>
          <a href="<?= e(base_url('/vehicle/'.$v['id'])) ?>" class="btn btn-primary btn-sm w-100 mt-2 tap"><?= e(__('book_now')) ?></a>
        </div>
      </div>
    </div>
  <?php endforeach; ?>
</div>
