<?php /** @var array $shop; string $qrData,$shareUrl */
$waShare = rawurlencode("Rent a bike/car in Dwarka! Scan or tap: " . $shareUrl); ?>
<div class="text-center">
  <div class="card mb-3"><div class="card-body">
    <img src="<?= e($qrData) ?>" alt="QR" style="max-width:240px" class="border rounded p-2">
    <div class="mt-2 fw-bold"><?= e($shop['name']) ?></div>
    <div class="badge bg-dark"><?= e($shop['code']) ?></div>
    <div class="text-muted small mt-1 text-break"><?= e($shareUrl) ?></div>
  </div></div>
  <div class="d-grid gap-2">
    <a href="<?= e(base_url('/shop/poster?format=pdf&size=A4')) ?>" class="btn btn-primary" download><i class="bi bi-file-earmark-pdf"></i> Download Poster (PDF)</a>
    <a href="<?= e(base_url('/shop/poster?format=png')) ?>" class="btn btn-outline-primary" download><i class="bi bi-image"></i> Download Poster (PNG)</a>
    <a href="https://wa.me/?text=<?= $waShare ?>" target="_blank" class="btn btn-success"><i class="bi bi-whatsapp"></i> Share on WhatsApp</a>
  </div>
  <p class="text-muted small mt-2">Note: poster download requires the QR link to work — make sure your Site URL is set by admin.</p>
</div>
