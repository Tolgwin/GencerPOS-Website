<?php require_once 'db.php';
require_once 'auth.php'; ?>
<!DOCTYPE html>
<html lang="tr">
<head>
<meta charset="UTF-8">
<title>Tutanak Sistemi</title>
<style>
*{box-sizing:border-box;margin:0;padding:0}
body{font-family:'Segoe UI',sans-serif;background:#f4f6fb;color:#222}
.sayfa-wrap{max-width:1200px;margin:0 auto;padding:24px 16px}
h1{font-size:22px;font-weight:700;color:#1e3a8a;margin-bottom:20px}
.btn{padding:8px 18px;border:none;border-radius:7px;cursor:pointer;font-size:13px;font-weight:600;transition:.15s;display:inline-flex;align-items:center;gap:6px;text-decoration:none}
.btn-primary{background:#3b82f6;color:#fff}.btn-primary:hover{background:#2563eb}
.btn-success{background:#10b981;color:#fff}.btn-success:hover{background:#059669}
.btn-gray{background:#e5e7eb;color:#374151}.btn-gray:hover{background:#d1d5db}
.btn-sm{padding:5px 12px;font-size:12px}
.kart-grid{display:grid;grid-template-columns:1fr 1fr;gap:20px;margin-bottom:24px}
.kart{background:#fff;border-radius:12px;box-shadow:0 2px 8px rgba(0,0,0,.08);overflow:hidden}
.kart-header{padding:16px 20px;background:linear-gradient(135deg,#667eea,#764ba2);color:#fff}
.kart-header h2{font-size:16px;font-weight:700}
.kart-body{padding:20px}
.kart-body p{font-size:13px;color:#6b7280;margin-bottom:14px}
.kart-body .actions{display:flex;gap:10px;flex-wrap:wrap}
.stat-num{font-size:28px;font-weight:700;color:#fff;margin-bottom:2px}
.alert-success{background:#d1fae5;color:#065f46;border:1px solid #6ee7b7;padding:10px 16px;border-radius:7px;margin-bottom:16px;font-size:13px}
</style>
</head>
<body>
<?php include 'menu.php'; ?>
<div class="sayfa-wrap">
<div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:20px">
  <h1>📋 Tutanak Sistemi</h1>
</div>

<?php if(isset($_GET['success'])): ?>
<div class="alert-success">
  <?php echo $_GET['success']==='added'?'✅ Tutanak eklendi!':($_GET['success']==='deleted'?'✅ Silindi!':'✅ Güncellendi!'); ?>
</div>
<?php endif; ?>

<?php
require_once 'db.php';
require_once 'auth.php';
$hurdaSayisi = $pdo->query("SELECT COUNT(*) FROM tutanak_hurda")->fetchColumn();
$devirSayisi = $pdo->query("SELECT COUNT(*) FROM tutanak_devir")->fetchColumn();
?>

<div class="kart-grid">
  <div class="kart">
    <div class="kart-header" style="background:linear-gradient(135deg,#ef4444,#b91c1c)">
      <div class="stat-num"><?= $hurdaSayisi ?></div>
      <h2>📄 EK-1: Hurda / Geçici Kapanış / Yeniden Açılış</h2>
    </div>
    <div class="kart-body">
      <p>Hurdaya ayrılan veya geçici kullanım dışı bırakılan ödeme kaydedici cihazlar için tutanak oluşturun.</p>
      <div class="actions">
        <a href="tutanak_hurda_ekle.php" class="btn btn-primary">+ Yeni Tutanak</a>
        <a href="tutanak_hurda_liste.php" class="btn btn-gray">📋 Listele (<?= $hurdaSayisi ?>)</a>
      </div>
    </div>
  </div>
  <div class="kart">
    <div class="kart-header" style="background:linear-gradient(135deg,#3b82f6,#1d4ed8)">
      <div class="stat-num"><?= $devirSayisi ?></div>
      <h2>📄 EK-2: Devir Satış Tutanağı</h2>
    </div>
    <div class="kart-body">
      <p>Ödeme kaydedici cihazların devir ve satış işlemleri için tutanak oluşturun.</p>
      <div class="actions">
        <a href="tutanak_devir_ekle.php" class="btn btn-primary">+ Yeni Tutanak</a>
        <a href="tutanak_devir_liste.php" class="btn btn-gray">📋 Listele (<?= $devirSayisi ?>)</a>
      </div>
    </div>
  </div>
</div>

<div style="text-align:right;margin-top:8px">
  <a href="tutanak_firmalar.php" class="btn btn-gray btn-sm">🏢 Firma Yönetimi</a>
</div>
</div>
</body>
</html>
