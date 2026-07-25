<?php /** @var array $vehicles */ ?>
<h6 class="mb-2">My Vehicles</h6>
<?php if (!$vehicles): ?><p class="text-muted small">No vehicles assigned. Contact admin to add vehicles.</p><?php endif; ?>
<?php foreach ($vehicles as $v): ?>
  <div class="card mb-2"><div class="card-body py-2 d-flex justify-content-between align-items-center">
    <div class="d-flex gap-2 align-items-center">
      <img src="<?= $v['main_image'] ? e(upload_url($v['main_image'])) : 'https://placehold.co/60x45' ?>" style="width:56px;height:42px;object-fit:cover;border-radius:6px">
      <div><div class="fw-semibold small"><?= e($v['name']) ?></div><div class="text-muted" style="font-size:12px"><?= e($v['category_name']) ?> · <?= money($v['price_day']) ?>/day · <?= (int)$v['units'] ?> unit(s)</div></div>
    </div>
    <form method="post" action="<?= e(base_url('/agency/vehicles/'.$v['id'].'/toggle')) ?>">
      <?= csrf_field() ?>
      <button class="btn btn-sm btn-<?= $v['status']==='active'?'success':'outline-secondary' ?>">
        <i class="bi bi-<?= $v['status']==='active'?'toggle-on':'toggle-off' ?>"></i> <?= $v['status']==='active'?'On':'Off' ?>
      </button>
    </form>
  </div></div>
<?php endforeach; ?>
