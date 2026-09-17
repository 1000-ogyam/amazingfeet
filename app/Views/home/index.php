<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1, user-scalable=no, viewport-fit=cover">
  <title>Amazing Feet — Sign In</title>
  <meta name="description" content="Amazing Feet is a modern retail POS and operations system for school shoes, ladies footwear, and shop teams across Ghana.">
  <link rel="stylesheet" href="<?= asset('assets/css/home.css') ?>">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css" crossorigin="anonymous" referrerpolicy="no-referrer">
  <link rel="icon" href="<?= e(af_favicon_data_uri()) ?>">
</head>
<body class="home">

  <div class="home-bg" aria-hidden="true">
    <div class="home-bg-wash"></div>
    <div class="home-bg-adinkra home-bg-adinkra--a"></div>
    <div class="home-bg-adinkra home-bg-adinkra--b"></div>
    <div class="home-bg-glow"></div>
  </div>

  <header class="home-nav">
    <a class="home-nav-brand" href="<?= BASE_PATH ?>/">
      <span class="home-nav-mark" aria-hidden="true"><i class="fa-solid fa-shoe-prints"></i></span>
      <span class="home-nav-name">Amazing Feet</span>
    </a>
  </header>

  <main class="home-hero home-hero--auth">
    <div class="home-hero-copy">
      <p class="home-brand anim anim-1">Amazing Feet</p>
      <h1 class="home-title anim anim-2">Sell smarter.<br>Stock clearer.<br>Grow with Ghana.</h1>
      <p class="home-desc anim anim-3">
        A lightweight retail POS built for Amazing Feet — track sales, stock, staff, and targets from the shop floor to the owner desk.
      </p>
      <div class="home-feat-row anim anim-4" aria-hidden="true">
        <svg class="home-adinkra-mini" viewBox="0 0 200 200">
          <g fill="none" stroke="currentColor" stroke-width="3.2" stroke-linecap="round" stroke-linejoin="round">
            <circle cx="100" cy="100" r="72"/>
            <path d="M100 40c18 18 18 42 0 60-18-18-18-42 0-60z"/>
            <path d="M100 100c18 18 18 42 0 60-18-18-18-42 0-60z"/>
            <path d="M40 100c18-18 42-18 60 0-18 18-42 18-60 0z"/>
            <path d="M100 100c18-18 42-18 60 0-18 18-42 18-60 0z"/>
            <circle cx="100" cy="100" r="10" fill="currentColor" stroke="none"/>
          </g>
        </svg>
        <span>Gye Nyame — except God</span>
      </div>
    </div>

    <aside class="home-auth anim anim-5">
      <div class="home-auth-card">
        <h2 class="home-auth-title">Sign in</h2>
        <p class="home-auth-sub">Owner &amp; staff access</p>

        <?php if (!empty($error)): ?>
        <div class="home-alert" role="alert">
          <i class="fa-solid fa-circle-xmark" aria-hidden="true"></i>
          <span><?= e($error) ?></span>
        </div>
        <?php endif; ?>

        <form method="POST" action="<?= BASE_PATH ?>/login" class="home-auth-form">
          <input type="hidden" name="csrf" value="<?= csrf() ?>">
          <div class="home-field">
            <label for="email">Email Address</label>
            <input id="email" type="email" name="email" required autofocus placeholder="you@amazingfeet.com" autocomplete="username">
          </div>
          <div class="home-field">
            <label for="password">Password</label>
            <input id="password" type="password" name="password" required placeholder="••••••••" autocomplete="current-password">
          </div>
          <button type="submit" class="home-btn home-btn-primary home-btn-block">
            Sign In <i class="fa-solid fa-arrow-right" aria-hidden="true"></i>
          </button>
        </form>

        <p class="home-demo">
          <strong>Demo:</strong> Owner: ama@amazingfeet.com / owner123<br>
          Staff: staff@amazingfeet.com / staff123
        </p>
      </div>
    </aside>
  </main>

  <footer class="home-foot anim anim-6">
    <span>Accra, Ghana</span>
    <span class="home-foot-dot" aria-hidden="true"></span>
    <span>Retail POS &amp; Operations</span>
    <span class="home-foot-dot" aria-hidden="true"></span>
    <span>Powered by <a class="home-foot-link" href="https://codecraftgh.com/" target="_blank" rel="noopener noreferrer">Codecraft Technologies</a></span>
  </footer>

</body>
</html>
