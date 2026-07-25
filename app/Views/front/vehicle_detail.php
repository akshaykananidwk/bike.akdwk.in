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
    <h4><?= e($vehicle['name']) ?></h4>
    <?php if (($rating['count'] ?? 0) > 0): ?>
      <div style="color:#f59e0b;font-size:14px" class="mb-1">
        <?php for ($i=1;$i<=5;$i++): ?><i class="bi bi-star<?= $rating['avg'] >= $i ? '-fill' : ($rating['avg'] >= $i-0.5 ? '-half' : '') ?>"></i><?php endfor; ?>
        <span class="text-muted small ms-1"><?= $rating['avg'] ?> · <?= $rating['count'] ?> review<?= $rating['count']>1?'s':'' ?></span>
      </div>
    <?php endif; ?>
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

<?php if (!empty($reviews)): ?>
<div class="card border-0 shadow-soft mt-3" style="border-radius:16px"><div class="card-body">
  <h2 class="section-title" style="font-size:18px">Customer reviews</h2>
  <?php foreach ($reviews as $r): ?>
    <div class="border-bottom py-2">
      <div style="color:#f59e0b;font-size:13px">
        <?php for($i=1;$i<=5;$i++): ?><i class="bi bi-star<?= (int)$r['rating']>=$i?'-fill':'' ?>"></i><?php endfor; ?>
        <span class="fw-semibold text-dark ms-1"><?= e($r['customer_name']) ?></span>
        <span class="text-muted small ms-1"><?= e(date('d M Y', strtotime($r['created_at']))) ?></span>
      </div>
      <?php if ($r['comment']): ?><div class="small text-muted"><?= nl2br(e($r['comment'])) ?></div><?php endif; ?>
      <?php if ($r['admin_reply']): ?><div class="small text-primary mt-1">↳ <?= e($r['admin_reply']) ?></div><?php endif; ?>
    </div>
  <?php endforeach; ?>
</div></div>
<?php endif; ?>
