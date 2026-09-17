<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1, user-scalable=no, viewport-fit=cover">
  <title>Unauthorized — Amazing Feet</title>
  <link rel="stylesheet" href="<?= asset('assets/css/app.css') ?>">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css" crossorigin="anonymous" referrerpolicy="no-referrer">
</head>
<body>
<div class="auth-wrap">
  <div class="auth-box" style="text-align:center">
    <div class="err-page-ic"><i class="fa-solid fa-ban" aria-hidden="true"></i></div>
    <h2>Access Denied</h2>
    <p style="color:var(--muted)">You don't have permission to view this page.</p>
    <a href="<?= BASE_PATH ?>/logout" class="btn btn-ghost" style="margin-top:1rem"><i class="fa-solid fa-arrow-left" aria-hidden="true"></i> Back to Login</a>
  </div>
</div>
</body>
</html>
