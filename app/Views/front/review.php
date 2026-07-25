<?php
/** Token-gated review form. Expects: $booking, $existing, $submitted, $googleUrl */
$name = $booking['vehicle_name'] ?: ($booking['package_name'] ?: 'your ride');
?>
<div class="container py-4" style="max-width:520px">
  <?php if ($submitted || $existing): ?>
    <div class="card border-0 shadow-soft text-center" style="border-radius:16px"><div class="card-body p-4">
      <div class="display-4 text-success"><i class="bi bi-check-circle-fill"></i></div>
      <h1 style="font-size:22px">Thank you<?= $booking['customer_name'] ? ', ' . e($booking['customer_name']) : '' ?>! 🙏</h1>
      <div style="color:var(--gold);font-size:26px">
        <?php for ($i=1;$i<=5;$i++): ?><i class="bi bi-star<?= (int)$existing['rating'] >= $i ? '-fill' : '' ?>"></i><?php endfor; ?>
      </div>
      <p class="text-muted small mt-2">
        <?= $existing['status'] === 'approved'
            ? 'Your review is now live on our website.'
            : 'Your review has been received and will appear after a quick check.' ?>
      </p>
      <?php if ($googleUrl && (int)$existing['rating'] >= 4): ?>
        <hr>
        <p class="small mb-2">Would you share the same on Google? It helps other pilgrims find us. 🙏</p>
        <a href="<?= e($googleUrl) ?>" target="_blank" rel="noopener" class="btn btn-primary w-100 tap"><i class="bi bi-google"></i> Rate us on Google</a>
      <?php endif; ?>
      <a href="<?= e(base_url('/')) ?>" class="btn btn-link mt-2">Back to website</a>
    </div></div>
  <?php else: ?>
    <div class="card border-0 shadow-soft" style="border-radius:16px"><div class="card-body p-4">
      <div class="text-center mb-3">
        <span class="jai" style="color:var(--saffron)">🙏 Jai Dwarkadhish</span>
        <h1 style="font-size:22px" class="mt-1">How was your ride?</h1>
        <div class="text-muted small"><?= e($name) ?> · <span class="badge bg-dark"><?= e($booking['code']) ?></span></div>
      </div>

      <form method="post">
        <?= csrf_field() ?>
        <div class="text-center mb-3" id="stars" style="font-size:40px;color:var(--gold);cursor:pointer">
          <?php for ($i=1;$i<=5;$i++): ?><i class="bi bi-star" data-v="<?= $i ?>"></i><?php endfor; ?>
        </div>
        <input type="hidden" name="rating" id="rating" value="5">
        <div class="mb-3">
          <label class="form-label small fw-semibold">Tell us more (optional)</label>
          <textarea name="comment" class="form-control" rows="3" placeholder="Vehicle condition, service, driver…"></textarea>
        </div>
        <button class="btn btn-primary w-100 btn-lg tap">Submit review</button>
      </form>
    </div></div>
  <?php endif; ?>
</div>

<script>
(function(){
  var wrap=document.getElementById('stars'); if(!wrap) return;
  var input=document.getElementById('rating');
  var stars=[].slice.call(wrap.querySelectorAll('i'));
  function paint(v){ stars.forEach(function(s,i){ s.className='bi bi-star'+(i<v?'-fill':''); }); }
  stars.forEach(function(s){
    s.addEventListener('click', function(){ var v=+s.dataset.v; input.value=v; paint(v); });
    s.addEventListener('mouseenter', function(){ paint(+s.dataset.v); });
  });
  wrap.addEventListener('mouseleave', function(){ paint(+input.value); });
  paint(5);
})();
</script>
