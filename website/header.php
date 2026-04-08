<?php $current = basename($_SERVER['PHP_SELF']); ?>
<!DOCTYPE html>
<html lang="tr">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title><?= $pageTitle ?? 'GencerPOS - POS & Yazılım Çözümleri' ?></title>
  <meta name="description" content="<?= $pageDesc ?? 'GencerPOS - Profesyonel POS cihazları, yazarkasa yazılımları ve iş yönetim çözümleri.' ?>">
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>

<nav class="navbar" id="navbar">
  <div class="container">
    <a href="index.php" class="logo">
      <svg viewBox="0 0 36 36" fill="none" xmlns="http://www.w3.org/2000/svg">
        <rect width="36" height="36" rx="8" fill="currentColor"/>
        <path d="M8 12h20v2H8zM8 17h14v2H8zM8 22h18v2H8z" fill="#fff"/>
        <circle cx="28" cy="23" r="4" fill="#fff" opacity="0.8"/>
      </svg>
      Gencer<span>POS</span>
    </a>

    <div class="nav-links" id="navLinks">
      <a href="index.php" class="<?= $current === 'index.php' ? 'active' : '' ?>">Anasayfa</a>
      <a href="urunler.php" class="<?= $current === 'urunler.php' ? 'active' : '' ?>">Ürünler</a>
      <a href="hakkimizda.php" class="<?= $current === 'hakkimizda.php' ? 'active' : '' ?>">Hakkımızda</a>
      <a href="iletisim.php" class="<?= $current === 'iletisim.php' ? 'active' : '' ?>">İletişim</a>
      <a href="#" class="nav-cta">Giriş Yap</a>
    </div>

    <div class="nav-toggle" id="navToggle" onclick="document.getElementById('navLinks').classList.toggle('open')">
      <span></span><span></span><span></span>
    </div>
  </div>
</nav>
