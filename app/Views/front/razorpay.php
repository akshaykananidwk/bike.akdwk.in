<?php /** @var array $booking, $order */ ?>
<div class="text-center py-4">
  <div class="spinner-border text-primary mb-3" role="status"></div>
  <h5>Launching secure payment…</h5>
  <p class="text-muted">If it doesn't open, <a href="#" id="retry">tap here</a>.</p>
</div>
<script src="https://checkout.razorpay.com/v1/checkout.js"></script>
<script>
var options = {
  key: <?= json_encode($order['key_id']) ?>,
  amount: <?= (int)$order['amount'] ?>,
  currency: <?= json_encode($order['currency']) ?>,
  name: <?= json_encode(setting('site_name','Dwarka Rental')) ?>,
  description: 'Booking <?= e($booking['code']) ?>',
  order_id: <?= json_encode($order['order_id']) ?>,
  prefill: { name: <?= json_encode($booking['customer_name']) ?>, contact: <?= json_encode($booking['customer_mobile']) ?> },
  theme: { color: <?= json_encode(setting('primary_color','#0d6efd')) ?> },
  handler: function (response) {
    var fd = new FormData();
    fd.append('_csrf', window.CSRF);
    fd.append('razorpay_order_id', response.razorpay_order_id);
    fd.append('razorpay_payment_id', response.razorpay_payment_id);
    fd.append('razorpay_signature', response.razorpay_signature);
    fetch('<?= e(base_url('/pay/'.$booking['code'].'/razorpay/callback')) ?>', {
      method:'POST', headers:{'X-CSRF-Token':window.CSRF,'X-Requested-With':'XMLHttpRequest'}, body:fd
    }).then(function(r){return r.json();}).then(function(d){
      window.location = d.ok ? d.redirect : '<?= e(base_url('/checkout/'.$booking['code'])) ?>';
    });
  },
  modal: { ondismiss: function(){ window.location='<?= e(base_url('/checkout/'.$booking['code'])) ?>'; } }
};
var rzp = new Razorpay(options);
document.getElementById('retry').onclick = function(e){ e.preventDefault(); rzp.open(); };
rzp.open();
</script>
