<?php /** Public reviews page. Expects: $reviews, $rating */ ?>
<section style="background:linear-gradient(135deg,var(--p),#151a3a 120%);color:#fff">
  <div class="container py-4 text-center">
    <span class="jai" style="color:var(--gold)">🙏 Jai Dwarkadhish</span>
    <h1 class="mt-1" style="font-size:26px">Customer Reviews</h1>
    <?php if ($rating['count'] > 0): ?>
      <div style="color:var(--gold);font-size:22px">
        <?php for ($i=1;$i<=5;$i++): ?><i class="bi bi-star<?= $rating['avg'] >= $i ? '-fill' : ($rating['avg'] >= $i-0.5 ? '-half' : '') ?>"></i><?php endfor; ?>
      </div>
      <div style="opacity:.9"><?= $rating['avg'] ?> out of 5 · <?= $rating['count'] ?> review<?= $rating['count']>1?'s':'' ?></div>
    <?php else: ?>
      <p style="opacity:.9;margin:0">Be our first reviewer — book a ride and tell us how it went!</p>
    <?php endif; ?>
  </div>
</section>

<div class="container py-4">
  <div class="row g-3">
    <?php if (!$reviews): ?>
      <div class="col-12 text-center text-muted py-4">No reviews yet.</div>
    <?php endif; ?>
    <?php foreach ($reviews as $r): ?>
      <div class="col-12 col-md-6 col-lg-4">
        <div class="card border-0 shadow-soft h-100" style="border-radius:16px"><div class="card-body">
          <div style="color:var(--gold)">
            <?php for ($i=1;$i<=5;$i++): ?><i class="bi bi-star<?= (int)$r['rating'] >= $i ? '-fill' : '' ?>"></i><?php endfor; ?>
          </div>
          <div class="fw-semibold mt-1"><?= e($r['customer_name']) ?></div>
          <div class="text-muted" style="font-size:12px"><?= e($r['vehicle_name'] ?: $r['package_name']) ?> · <?= e(date('d M Y', strtotime($r['created_at']))) ?></div>
          <?php if ($r['comment']): ?><p class="small mt-2 mb-0"><?= nl2br(e($r['comment'])) ?></p><?php endif; ?>
          <?php if ($r['admin_reply']): ?>
            <div class="mt-2 p-2 rounded" style="background:#f1f5f9;font-size:12px"><strong>Our reply:</strong> <?= e($r['admin_reply']) ?></div>
          <?php endif; ?>
        </div></div>
      </div>
    <?php endforeach; ?>
  </div>
</div>
