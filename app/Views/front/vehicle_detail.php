<?php /** @var array $vehicle, $images, bool $gu */ ?>
<a href="<?= e(base_url('/vehicles')) ?>" class="btn btn-sm btn-link px-0"><i class="bi bi-arrow-left"></i> <?= e(__('vehicles')) ?></a>
<div class="row g-3">
  <div class="col-md-6">
    <div id="veh-carousel" class="carousel slide" data-bs-ride="carousel">
      <div class="carousel-inner rounded shadow-sm">
        <div class="carousel-item active">
          <img src="<?= $vehicle['main_image'] ? e(upload_url($vehicle['main_image'])) : 'https://placehold.co/700x500?text='.urlencode($vehicle['name']) ?>" class="d-block w-100" style="height:280px;object-fit:cover">
        </div>
        <?php foreach ($images as $img): ?>
          <div class="carousel-item"><img src="<?= e(upload_url($img['image'])) ?>" class="d-block w-100" style="height:280px;object-fit:cover"></div>
        <?php endforeach; ?>
      </div>
      <?php if ($images): ?>
      <button class="carousel-control-prev" data-bs-target="#veh-carousel" data-bs-slide="prev"><span class="carousel-control-prev-icon"></span></button>
      <button class="carousel-control-next" data-bs-target="#veh-carousel" data-bs-slide="next"><span class="carousel-control-next-icon"></span></button>
      <?php endif; ?>
    </div>
  </div>
  <div class="col-md-6">
    <h4><?= e($gu && $vehicle['name_gu'] ? $vehicle['name_gu'] : $vehicle['name']) ?></h4>
    <div class="text-muted mb-2"><?= e($vehicle['category_name']) ?> · <?= e($vehicle['brand']) ?> <?= e($vehicle['model']) ?></div>
    <div class="d-flex gap-2 flex-wrap mb-3">
      <span class="badge bg-light text-dark border"><i class="bi bi-gear"></i> <?= e(str_replace('_',' ',$vehicle['transmission'])) ?></span>
      <span class="badge bg-light text-dark border"><i class="bi bi-fuel-pump"></i> <?= e($vehicle['fuel']) ?></span>
      <?php if ($vehicle['seats']): ?><span class="badge bg-light text-dark border"><i class="bi bi-people"></i> <?= (int)$vehicle['seats'] ?> seats</span><?php endif; ?>
    </div>
    <div class="card card-body mb-3">
      <div class="row text-center">
        <div class="col"><div class="text-primary fw-bold fs-5"><?= money($vehicle['price_day']) ?></div><small class="text-muted"><?= e(__('per_day')) ?></small></div>
        <?php if ($vehicle['price_hour']>0): ?><div class="col border-start"><div class="fw-bold fs-5"><?= money($vehicle['price_hour']) ?></div><small class="text-muted"><?= e(__('per_hour')) ?></small></div><?php endif; ?>
        <div class="col border-start"><div class="fw-bold fs-5"><?= money($vehicle['deposit']) ?></div><small class="text-muted"><?= e(__('deposit')) ?></small></div>
      </div>
    </div>
    <?php if ($vehicle['description']): ?><p class="text-muted"><?= nl2br(e($vehicle['description'])) ?></p><?php endif; ?>
    <a href="<?= e(base_url('/book/'.$vehicle['id'])) ?>" class="btn btn-primary btn-lg w-100 tap"><i class="bi bi-calendar-check"></i> <?= e(__('book_now')) ?></a>
  </div>
</div>
