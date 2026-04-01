<?php
// fatura_ekle.php
require_once __DIR__ . '/EFaturaService.php';
require_once __DIR__ . '/UBLBuilder.php';

$pdo = new PDO('mysql:host=localhost;dbname=fatura_db;charset=utf8mb4', 'root', '');
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

// ── Servis Yapılandırması ────────────────────────────────────────
$config = [
    'username'        => '3930311899',
    'password'        => 'Tolga3583.',
    'user_wsdl'       => 'https://erpefaturatest1.qnbesolutions.com.tr/efatura/ws/userService?wsdl',
    'connector_url'   => 'https://erpefaturatest1.qnbesolutions.com.tr/efatura/ws/connectorService',
];

$errors  = [];
$success = null;

// ── POST İşlemi ──────────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    // Validasyon
    $required = ['belge_no','alici_vkn','alici_unvan','kalem_aciklama','matrah','kdv_oran'];
    foreach ($required as $field) {
        if (empty($_POST[$field])) {
            $errors[] = "$field alanı zorunludur.";
        }
    }

    if (empty($errors)) {
        $fatura = [
            'belge_no'        => trim($_POST['belge_no']),
            'gonderen_vkn'    => $config['username'],
            'gonderen_unvan'  => 'Gönderen Firma A.Ş.',   // ← config'e taşıyabilirsiniz
            'alici_vkn'       => trim($_POST['alici_vkn']),
            'alici_unvan'     => trim($_POST['alici_unvan']),
            'kalem_aciklama'  => trim($_POST['kalem_aciklama']),
            'matrah'          => (float) $_POST['matrah'],
            'kdv_oran'        => (int)   $_POST['kdv_oran'],
        ];

        try {
            // 1. UBL XML üret
            $xmlContent = UBLBuilder::build($fatura);

            // 2. E-Fatura gönder
            $service = new EFaturaService($config);
            $service->login();
            $ettn = $service->belgeGonder($xmlContent, $fatura['belge_no']);
            $service->logout();

            // 3. DB'ye kaydet
            $stmt = $pdo->prepare("
                INSERT INTO faturalar
                    (belge_no, alici_vkn, alici_unvan, matrah, kdv_oran, ettn, durum, olusturma_tarihi)
                VALUES
                    (:belge_no, :alici_vkn, :alici_unvan, :matrah, :kdv_oran, :ettn, 'GONDERILDI', NOW())
            ");
            $stmt->execute([
                ':belge_no'    => $fatura['belge_no'],
                ':alici_vkn'   => $fatura['alici_vkn'],
                ':alici_unvan' => $fatura['alici_unvan'],
                ':matrah'      => $fatura['matrah'],
                ':kdv_oran'    => $fatura['kdv_oran'],
                ':ettn'        => $ettn,
            ]);

            $success = "✅ Fatura gönderildi! ETTN: <strong>$ettn</strong>";

        } catch (RuntimeException $e) {
            $errors[] = "Gönderim hatası: " . $e->getMessage();
        } catch (SoapFault $e) {
            $errors[] = "SOAP hatası: " . $e->getMessage();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="tr">
<head>
  <meta charset="UTF-8">
  <title>E-Fatura Ekle</title>
  <style>
    body { font-family: sans-serif; max-width: 600px; margin: 40px auto; }
    label { display: block; margin-top: 12px; font-weight: bold; }
    input, select { width: 100%; padding: 8px; margin-top: 4px; box-sizing: border-box; }
    button { margin-top: 20px; padding: 10px 24px; background: #2563eb; color: #fff; border: none; border-radius: 6px; cursor: pointer; font-size: 1rem; }
    .success { background: #d1fae5; border: 1px solid #6ee7b7; padding: 12px; border-radius: 6px; margin-bottom: 16px; }
    .error   { background: #fee2e2; border: 1px solid #fca5a5; padding: 12px; border-radius: 6px; margin-bottom: 16px; }
  </style>
</head>
<body>

<h2>📄 E-Fatura Ekle</h2>

<?php if ($success): ?>
  <div class="success"><?= $success ?></div>
<?php endif; ?>

<?php if ($errors): ?>
  <div class="error">
    <?php foreach ($errors as $e): ?>
      <p>⚠️ <?= htmlspecialchars($e) ?></p>
    <?php endforeach; ?>
  </div>
<?php endif; ?>

<form method="POST">
  <label>Belge No</label>
  <input type="text" name="belge_no" value="<?= htmlspecialchars($_POST['belge_no'] ?? 'FAT2026000000001') ?>" required>

  <label>Alıcı VKN/TCKN</label>
  <input type="text" name="alici_vkn" value="<?= htmlspecialchars($_POST['alici_vkn'] ?? '') ?>" required>

  <label>Alıcı Ünvan</label>
  <input type="text" name="alici_unvan" value="<?= htmlspecialchars($_POST['alici_unvan'] ?? '') ?>" required>

  <label>Kalem Açıklama</label>
  <input type="text" name="kalem_aciklama" value="<?= htmlspecialchars($_POST['kalem_aciklama'] ?? '') ?>" required>

  <label>Matrah (TL)</label>
  <input type="number" step="0.01" name="matrah" value="<?= htmlspecialchars($_POST['matrah'] ?? '') ?>" required>

  <label>KDV Oranı (%)</label>
  <select name="kdv_oran">
    <option value="20" <?= ($_POST['kdv_oran'] ?? '') == 20 ? 'selected' : '' ?>>%20</option>
    <option value="10" <?= ($_POST['kdv_oran'] ?? '') == 10 ? 'selected' : '' ?>>%10</option>
    <option value="1"  <?= ($_POST['kdv_oran'] ?? '') ==  1 ? 'selected' : '' ?>>%1</option>
    <option value="0"  <?= ($_POST['kdv_oran'] ?? '') ==  0 ? 'selected' : '' ?>>%0</option>
  </select>

  <button type="submit">🚀 Faturayı Gönder</button>
</form>

</body>
</html>
