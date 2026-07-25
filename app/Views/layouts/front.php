<?php
/** Customer-facing layout — modern, mobile-first, SEO-rich. Expects: $title, $content, optional $meta, $splash. */
use App\Core\Lang;
use App\Core\Session;
$primary   = setting('primary_color', '#0d6efd');
$secondary = setting('secondary_color', '#20c997');
$siteName  = setting('site_name', 'Dwarka Rental');
$refShop   = Session::get('ref_shop_name');
$desc      = 'Book a bike, scooty, Activa, self-drive car, taxi, cab or tempo traveller in Dwarka. Instant online booking, verified vehicles, lowest price, doorstep pickup near Dwarkadhish Temple, Gomti Ghat, Beyt Dwarka, Nageshwar & Okha. Dwarka darshan taxi, Dwarka to Somnath cab, one-way & round-trip.';
$keywords  = 'bike rent in dwarka, bike rental dwarka, rent bike near me, bike on rent, two wheeler rental dwarka, scooty rent dwarka, scooter on rent dwarka, activa on rent dwarka, car rental dwarka, car on rent dwarka, self drive car dwarka, self drive car rental near me, taxi in dwarka, taxi booking dwarka, dwarka taxi service, cab booking dwarka, online cab dwarka, car with driver dwarka, tempo traveller dwarka, tempo traveller on rent, tempo booking dwarka, bus rental dwarka, 12 seater tempo, 17 seater tempo traveller, dwarka darshan taxi, dwarka darshan by car, dwarka local sightseeing taxi, dwarka to somnath taxi, dwarka to somnath cab, dwarka to okha taxi, dwarka to beyt dwarka, dwarka to nageshwar, dwarka to rajkot taxi, dwarka to jamnagar cab, dwarka to porbandar taxi, dwarka to ahmedabad cab, dwarka airport taxi, jamnagar to dwarka taxi, dwarka railway station taxi, one way taxi dwarka, round trip taxi dwarka, outstation cab dwarka, cheap taxi dwarka, best car rental dwarka, cycle rental dwarka, bike hire dwarka, two wheeler hire, vehicle rental dwarka, devbhoomi dwarka vehicle rental, rent a car dwarka, rent a bike dwarka, book taxi online dwarka, dwarkadhish temple, gomti ghat, beyt dwarka, nageshwar jyotirlinga, okha, mithapur, rukmini temple';
$showSplash = $splash ?? false;
$baseUrl   = rtrim(setting('site_url', '') ?: guess_base_url(), '/');
?>
<!doctype html>
<html lang="<?= e(Lang::locale()) ?>">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=5">
<meta name="csrf-token" content="<?= e(csrf_token()) ?>">
<script>window.CSRF=<?= json_encode(csrf_token()) ?>;</script>
<meta name="theme-color" content="<?= e($primary) ?>">
<title><?= e($title ?? ($siteName . ' — Bike, Scooty & Car Rental in Dwarka')) ?></title>
<meta name="description" content="<?= e($desc) ?>">
<meta name="keywords" content="<?= e($keywords) ?>">
<meta name="robots" content="index, follow">
<link rel="canonical" href="<?= e($baseUrl . ($_SERVER['REQUEST_URI'] ?? '/')) ?>">
<!-- Open Graph / social -->
<meta property="og:type" content="website">
<meta property="og:site_name" content="<?= e($siteName) ?>">
<meta property="og:title" content="<?= e($title ?? $siteName) ?>">
<meta property="og:description" content="<?= e($desc) ?>">
<meta property="og:image" content="<?= e(setting('logo') ? upload_url(setting('logo')) : $baseUrl . '/assets/img/og.png') ?>">
<meta name="twitter:card" content="summary_large_image">
<?= $meta ?? '' ?>
<!-- LocalBusiness + FAQ structured data (helps local Google ranking & rich results) -->
<script type="application/ld+json">
{"@context":"https://schema.org","@type":["AutoRental","TaxiService","LocalBusiness"],"name":<?= json_encode($siteName) ?>,"image":<?= json_encode(setting('logo') ? upload_url(setting('logo')) : '') ?>,"url":<?= json_encode($baseUrl) ?>,"telephone":<?= json_encode(setting('contact_mobile', '')) ?>,"priceRange":"₹₹","areaServed":["Dwarka","Devbhoomi Dwarka","Beyt Dwarka","Okha","Nageshwar","Gujarat"],"makesOffer":["Bike rental","Scooty rental","Activa on rent","Self-drive car rental","Taxi service","Cab booking","Tempo traveller booking","Cycle rental","Dwarka darshan taxi"],"address":{"@type":"PostalAddress","addressLocality":"Dwarka","addressRegion":"Gujarat","postalCode":"361335","addressCountry":"IN"},"geo":{"@type":"GeoCoordinates","latitude":22.2394,"longitude":68.9678},"openingHours":"Mo-Su 06:00-23:00"}
</script>
<script type="application/ld+json">
{"@context":"https://schema.org","@type":"FAQPage","mainEntity":[
{"@type":"Question","name":"How can I rent a bike or scooty in Dwarka?","acceptedAnswer":{"@type":"Answer","text":"Choose a bike, scooty or Activa on <?= e($siteName) ?>, pick your dates, upload your driving licence and pay online. Pickup is available near Dwarkadhish Temple, Gomti Ghat and the bus stand."}},
{"@type":"Question","name":"Do you provide taxi and tempo traveller booking in Dwarka?","acceptedAnswer":{"@type":"Answer","text":"Yes. Book a taxi, cab, self-drive car or tempo traveller for Dwarka darshan, local sightseeing or outstation trips like Dwarka to Somnath, Okha, Nageshwar and Beyt Dwarka."}},
{"@type":"Question","name":"What are the rental charges?","acceptedAnswer":{"@type":"Answer","text":"Bikes and scooters start from an affordable hourly and daily rate with a refundable deposit. Cars, taxis and tempo travellers are priced per trip or per day. See live prices on the vehicles page."}},
{"@type":"Question","name":"Which documents are required?","acceptedAnswer":{"@type":"Answer","text":"A valid driving licence is required for self-drive bikes and cars. Carry a government ID (Aadhaar) for verification."}}
]}
</script>
<link rel="manifest" href="<?= e(base_url('/manifest.webmanifest')) ?>">
<!-- Self-hosted assets (fast, no CDN dependency, works offline via PWA) -->
<link href="<?= e(asset('css/bootstrap.min.css')) ?>" rel="stylesheet">
<link href="<?= e(asset('css/bootstrap-icons.css')) ?>" rel="stylesheet">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@500;600;700;800&family=Noto+Sans+Gujarati:wght@400;600;700&display=swap" rel="stylesheet">
<style>
 :root{
   --p:<?= e($primary) ?>; --s:<?= e($secondary) ?>;
   --saffron:#ff8f1f; --gold:#ffc44d; --ink:#141b2d; --muted:#6b7280;
   --bg:#f6f8fc; --card:#ffffff; --ring:0 8px 30px rgba(20,27,45,.08);
 }
 *{box-sizing:border-box}
 html,body{overflow-x:hidden;max-width:100%}
 body{font-family:'Poppins','Noto Sans Gujarati',system-ui,sans-serif;background:var(--bg);color:var(--ink);margin:0;padding-bottom:76px;-webkit-tap-highlight-color:transparent}
 .form-control,.form-select{min-width:0}
 input[type=datetime-local]{max-width:100%}
 h1,h2,h3,h4,h5{font-weight:700;letter-spacing:-.02em}
 a{text-decoration:none}
 .btn{border-radius:12px;font-weight:600}
 .btn-primary{background:var(--p);border-color:var(--p)}
 .btn-primary:hover{filter:brightness(.94)}
 .text-primary{color:var(--p)!important}
 .tap{min-height:46px;display:inline-flex;align-items:center;justify-content:center}
 .shadow-soft{box-shadow:var(--ring)}
 /* Splash */
 #jsplash{position:fixed;inset:0;z-index:3000;display:flex;flex-direction:column;align-items:center;justify-content:center;
   background:radial-gradient(circle at 50% 30%, #2a1a5e, #10122b 70%);color:#fff;text-align:center;animation:splashOut .6s ease 1.9s forwards}
 #jsplash .mandala{width:120px;height:120px;border-radius:50%;border:3px dashed var(--gold);display:flex;align-items:center;justify-content:center;font-size:52px;animation:spin 9s linear infinite,pop .6s ease}
 #jsplash h1{font-size:34px;margin:18px 0 4px;background:linear-gradient(90deg,var(--gold),#fff,var(--saffron));-webkit-background-clip:text;background-clip:text;-webkit-text-fill-color:transparent;animation:pop .7s ease .1s both}
 #jsplash p{opacity:.8;margin:0;font-size:14px}
 @keyframes spin{to{transform:rotate(360deg)}}
 @keyframes pop{from{transform:scale(.6);opacity:0}to{transform:scale(1);opacity:1}}
 @keyframes splashOut{to{opacity:0;visibility:hidden}}
 /* Nav */
 .topbar{background:rgba(255,255,255,.9);backdrop-filter:blur(10px);border-bottom:1px solid #eef1f6;position:sticky;top:0;z-index:1030}
 .brand{font-weight:800;font-size:20px;background:linear-gradient(90deg,var(--p),var(--s));-webkit-background-clip:text;background-clip:text;-webkit-text-fill-color:transparent}
 .jai{font-family:'Noto Sans Gujarati',sans-serif;font-weight:700;color:var(--saffron);font-size:13px}
 .ref-banner{background:linear-gradient(90deg,var(--s),var(--p));color:#fff;text-align:center;padding:7px;font-size:13px;font-weight:600}
 /* Cards */
 .veh-card{border:1px solid #eef1f6;border-radius:18px;overflow:hidden;background:var(--card);transition:.2s;box-shadow:var(--ring)}
 .veh-card:hover{transform:translateY(-4px)}
 .veh-card .imgwrap{position:relative;aspect-ratio:4/3;overflow:hidden;background:#eef2f8}
 .veh-card img{width:100%;height:100%;object-fit:cover;transition:.3s}
 .veh-card:hover img{transform:scale(1.06)}
 .pill{border-radius:999px;padding:3px 10px;font-size:11px;font-weight:600}
 .price-tag{color:var(--p);font-weight:800}
 .wa-float{position:fixed;right:16px;bottom:86px;background:#25d366;color:#fff;width:54px;height:54px;border-radius:50%;display:flex;align-items:center;justify-content:center;font-size:28px;box-shadow:0 8px 22px rgba(37,211,102,.45);z-index:1050}
 .bottombar{position:fixed;left:0;right:0;bottom:0;background:#fff;border-top:1px solid #eef1f6;display:flex;z-index:1040}
 .bottombar a{flex:1;text-align:center;padding:8px 2px;color:var(--muted);font-size:11px;font-weight:600}
 .bottombar a.active{color:var(--p)}
 .bottombar a i{display:block;font-size:20px;margin-bottom:2px}
 .section-title{font-size:20px;font-weight:800}
 .chip{background:#fff;border:1px solid #eef1f6;border-radius:14px;padding:12px 8px;text-align:center;box-shadow:var(--ring);transition:.15s;display:block;color:var(--ink)}
 .chip:hover{transform:translateY(-3px);border-color:var(--p)}
 .chip i{font-size:26px;color:var(--p)}
</style>
</head>
<body>
<?php if ($showSplash): ?>
<div id="jsplash">
  <div class="mandala">🛕</div>
  <h1>Jai Dwarkadhish</h1>
  <p>🙏 Welcome to <?= e($siteName) ?></p>
</div>
<?php endif; ?>

<?php if ($refShop): ?>
  <div class="ref-banner"><i class="bi bi-shop"></i> <?= e(__('booking_via', ['shop' => $refShop])) ?></div>
<?php endif; ?>

<nav class="topbar">
  <div class="container d-flex align-items-center justify-content-between py-2">
    <a href="<?= e(base_url('/')) ?>" class="d-flex flex-column">
      <span class="brand"><?php if (setting('logo')): ?><img src="<?= e(upload_url(setting('logo'))) ?>" style="height:30px;vertical-align:middle"> <?php else: ?>🛵 <?php endif; ?><?= e($siteName) ?></span>
      <span class="jai">🙏 Jai Dwarkadhish</span>
    </a>
    <div class="d-flex align-items-center gap-2">
      <?php if (setting('voice_greeting_enabled', '1') === '1'): ?>
        <button type="button" class="btn btn-sm btn-outline-secondary tap" id="voiceBtn" title="Jai Dwarkadhish voice greeting" aria-label="Toggle voice greeting"><i class="bi bi-volume-up" id="voiceIcon"></i></button>
      <?php endif; ?>
      <a href="<?= e(base_url('/my-bookings')) ?>" class="btn btn-sm btn-outline-primary tap" title="My Bookings"><i class="bi bi-bag-check"></i> My Bookings</a>
    </div>
  </div>
</nav>

<main>
  <?php if ($f = Session::flash('success')): ?><div class="container mt-3"><div class="alert alert-success shadow-soft"><?= e($f) ?></div></div><?php endif; ?>
  <?php if ($f = Session::flash('error')): ?><div class="container mt-3"><div class="alert alert-danger shadow-soft"><?= e($f) ?></div></div><?php endif; ?>
  <?= $content ?>
</main>

<?php if ($wa = setting('contact_whatsapp')): ?>
  <a class="wa-float" href="https://wa.me/91<?= e($wa) ?>" target="_blank" rel="noopener" aria-label="WhatsApp"><i class="bi bi-whatsapp"></i></a>
<?php endif; ?>

<nav class="bottombar">
  <a href="<?= e(base_url('/')) ?>"><i class="bi bi-house-door"></i>Home</a>
  <a href="<?= e(base_url('/vehicles')) ?>"><i class="bi bi-scooter"></i>Vehicles</a>
  <a href="<?= e(base_url('/packages')) ?>"><i class="bi bi-map"></i>Packages</a>
  <?php if ($wa = setting('contact_whatsapp')): ?><a href="https://wa.me/91<?= e($wa) ?>" target="_blank"><i class="bi bi-whatsapp"></i>Chat</a><?php endif; ?>
  <?php if ($m = setting('contact_mobile')): ?><a href="tel:<?= e($m) ?>"><i class="bi bi-telephone"></i>Call</a><?php endif; ?>
  <a href="<?= e(base_url('/my-bookings')) ?>"><i class="bi bi-bag-check"></i>Bookings</a>
</nav>

<footer class="text-center small text-muted py-4" style="margin-bottom:60px">
  <div class="jai mb-2" style="font-size:15px">🙏 Jai Dwarkadhish</div>
  <a href="<?= e(base_url('/page/terms')) ?>" class="text-muted">Terms</a> ·
  <a href="<?= e(base_url('/page/privacy')) ?>" class="text-muted">Privacy</a> ·
  <a href="<?= e(base_url('/page/cancellation')) ?>" class="text-muted">Cancellation</a> ·
  <a href="<?= e(base_url('/reviews')) ?>" class="text-muted">Reviews</a> ·
  <a href="<?= e(base_url('/page/about')) ?>" class="text-muted">About</a>
  <div class="mt-3 mb-2">
    <div class="fw-bold" style="color:var(--ink)">Partner with us</div>
    <div class="d-flex justify-content-center gap-2 flex-wrap mt-2">
      <a href="<?= e(base_url('/shop/register')) ?>" class="btn btn-success btn-sm"><i class="bi bi-shop"></i> Register Your Shop</a>
      <a href="<?= e(base_url('/agency/register')) ?>" class="btn btn-warning btn-sm"><i class="bi bi-people"></i> List Your Vehicles</a>
    </div>
  </div>
  <div class="mt-2 d-flex justify-content-center gap-2 flex-wrap">
    <a href="<?= e(base_url('/shop/login')) ?>" class="btn btn-outline-secondary btn-sm"><i class="bi bi-shop"></i> Shop Login</a>
    <a href="<?= e(base_url('/agency/login')) ?>" class="btn btn-outline-secondary btn-sm"><i class="bi bi-people"></i> Agency Login</a>
    <a href="<?= e(base_url('/admin/login')) ?>" class="btn btn-outline-secondary btn-sm"><i class="bi bi-person-badge"></i> Admin</a>
  </div>
  <div class="mt-2">© <?= date('Y') ?> <?= e($siteName) ?> · Bike, Car, Taxi &amp; Tempo rental in Dwarka</div>
</footer>

<script src="<?= e(asset('js/bootstrap.bundle.min.js')) ?>"></script>

<?php if (setting('voice_greeting_enabled', '1') === '1'): ?>
<script>
/* "Jai Dwarkadhish" spoken greeting.
   Browsers block audio before a user gesture, so we try immediately and, if the
   browser refuses, speak on the visitor's first tap/scroll instead. Plays once
   per visit and can be muted with the speaker button (remembered). */
(function(){
  var MUTE_KEY='dwk_voice_muted', SPOKEN_KEY='dwk_greeted';
  var btn=document.getElementById('voiceBtn'), icon=document.getElementById('voiceIcon');

  function muted(){ try{ return localStorage.getItem(MUTE_KEY)==='1'; }catch(e){ return false; } }
  function setMuted(v){ try{ localStorage.setItem(MUTE_KEY, v?'1':'0'); }catch(e){} paint(); }
  function paint(){ if(icon) icon.className = muted() ? 'bi bi-volume-mute' : 'bi bi-volume-up'; }

  function pickVoice(){
    var v = window.speechSynthesis.getVoices() || [];
    // Prefer an Indian-language voice so the pronunciation sounds natural.
    return v.find(function(x){ return /^gu/i.test(x.lang); })
        || v.find(function(x){ return /^hi/i.test(x.lang); })
        || v.find(function(x){ return /en[-_]IN/i.test(x.lang); })
        || null;
  }

  function speak(force){
    if(!('speechSynthesis' in window)) return false;
    if(muted() && !force) return false;
    try{
      var voice=pickVoice();
      // Devanagari/Gujarati text makes an Indic voice say it correctly;
      // otherwise fall back to a phonetic English spelling.
      var indic = voice && /^(gu|hi)/i.test(voice.lang);
      var u=new SpeechSynthesisUtterance(indic ? 'जय द्वारकाधीश' : 'Jai Dwarkadhish');
      u.lang = voice ? voice.lang : 'en-IN';
      if(voice) u.voice=voice;
      u.rate=0.85; u.pitch=1; u.volume=1;
      window.speechSynthesis.cancel();
      window.speechSynthesis.speak(u);
      return true;
    }catch(e){ return false; }
  }

  function greetOnce(){
    if(muted()) return;
    try{ if(sessionStorage.getItem(SPOKEN_KEY)==='1') return; }catch(e){}
    speak(false);
    try{ sessionStorage.setItem(SPOKEN_KEY,'1'); }catch(e){}
  }

  // Speak after voices are ready (they load asynchronously on many browsers).
  function ready(fn){
    if(!('speechSynthesis' in window)) return;
    if((window.speechSynthesis.getVoices()||[]).length){ fn(); return; }
    window.speechSynthesis.addEventListener('voiceschanged', function once(){
      window.speechSynthesis.removeEventListener('voiceschanged', once); fn();
    });
    setTimeout(fn, 1200); // safety net if the event never fires
  }

  // 1) Try right away (works where autoplay is permitted).
  ready(function(){ setTimeout(greetOnce, <?= $showSplash ? 900 : 300 ?>); });

  // 2) Autoplay blocked? Speak on the visitor's first interaction instead.
  ['pointerdown','touchstart','keydown','scroll'].forEach(function(ev){
    window.addEventListener(ev, function once(){
      ['pointerdown','touchstart','keydown','scroll'].forEach(function(e2){ window.removeEventListener(e2, once); });
      greetOnce();
    }, {once:true, passive:true});
  });

  // Speaker button: muted -> unmute & speak now; unmuted -> mute & stop.
  if(btn){
    paint();
    btn.addEventListener('click', function(){
      if(muted()){ setMuted(false); speak(true); }
      else { setMuted(true); try{ window.speechSynthesis.cancel(); }catch(e){} }
    });
  }
})();
</script>
<?php endif; ?>

<script>
window.CSRF=document.querySelector('meta[name=csrf-token]').content;
<?php if ($showSplash): ?>setTimeout(function(){var s=document.getElementById('jsplash');if(s)s.remove();},2600);<?php endif; ?>
if('serviceWorker' in navigator){navigator.serviceWorker.register('<?= e(base_url('/sw.js')) ?>').catch(function(){});}
</script>
</body>
</html>
