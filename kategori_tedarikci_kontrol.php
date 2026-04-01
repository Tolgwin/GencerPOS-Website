<?php
ini_set('display_errors', 0);
error_reporting(E_ALL);
ob_start();

require 'db.php';
header('Content-Type: application/json; charset=utf-8');

function cevap(bool $basari, string $mesaj, array $data = []): void
{
    ob_end_clean();
    echo json_encode(
        array_merge(['basari' => $basari, 'mesaj' => $mesaj], $data),
        JSON_UNESCAPED_UNICODE
    );
    exit;
}

$action = $_POST['action'] ?? $_GET['action'] ?? '';

try {

    switch ($action) {

        // ════════════════════════════════════════════════════
        // KATEGORİ LİSTE
        // ════════════════════════════════════════════════════
        case 'kategori_liste':
            $ara = '%' . ($_GET['ara'] ?? '') . '%';
            $stmt = $pdo->prepare("
                SELECT k.*,
                       COUNT(u.id) AS urun_sayisi
                FROM kategoriler k
                LEFT JOIN urunler u
                       ON u.kategori_id = k.id
                      AND u.durum = 'aktif'
                WHERE k.ad LIKE ?
                GROUP BY k.id
                ORDER BY k.ad
            ");
            $stmt->execute([$ara]);
            cevap(true, '', ['kategoriler' => $stmt->fetchAll()]);

        // ════════════════════════════════════════════════════
        // KATEGORİ KAYDET
        // ════════════════════════════════════════════════════
        case 'kategori_kaydet':
            $id = (int) ($_POST['id'] ?? 0);
            $ad = trim($_POST['ad'] ?? '');
            $aciklama = trim($_POST['aciklama'] ?? '');

            if (!$ad)
                cevap(false, 'Kategori adı zorunludur.');

            if ($id) {
                $pdo->prepare("
                    UPDATE kategoriler
                    SET ad = ?, aciklama = ?
                    WHERE id = ?
                ")->execute([$ad, $aciklama ?: null, $id]);
                cevap(true, 'Kategori güncellendi.', ['id' => $id]);
            } else {
                $var = $pdo->prepare("SELECT id FROM kategoriler WHERE ad = ?");
                $var->execute([$ad]);
                if ($var->fetch())
                    cevap(false, 'Bu isimde kategori zaten mevcut.');

                $pdo->prepare("
                    INSERT INTO kategoriler (ad, aciklama)
                    VALUES (?, ?)
                ")->execute([$ad, $aciklama ?: null]);
                cevap(true, 'Kategori eklendi.', ['id' => (int) $pdo->lastInsertId()]);
            }

        // ════════════════════════════════════════════════════
        // KATEGORİ SİL
        // ════════════════════════════════════════════════════
        case 'kategori_sil':
            $id = (int) ($_POST['id'] ?? 0);
            if (!$id)
                cevap(false, 'Geçersiz ID.');

            $stmt = $pdo->prepare("
                SELECT COUNT(*) FROM urunler
                WHERE kategori_id = ? AND durum = 'aktif'
            ");
            $stmt->execute([$id]);
            if ((int) $stmt->fetchColumn() > 0)
                cevap(false, 'Bu kategoriye bağlı aktif ürünler var. Önce ürünleri taşıyın.');

            $pdo->prepare("DELETE FROM kategoriler WHERE id = ?")->execute([$id]);
            cevap(true, 'Kategori silindi.');

        // ════════════════════════════════════════════════════
        // TEDARİKÇİ LİSTE
        // ════════════════════════════════════════════════════
        case 'tedarikci_liste':
            $ara = '%' . ($_GET['ara'] ?? '') . '%';
            $stmt = $pdo->prepare("
                SELECT t.*,
                       COUNT(DISTINCT u.id) AS urun_sayisi,
                       COALESCE(SUM(
                           CASE WHEN h.hareket_tipi = 'giris'
                                THEN h.toplam_tutar
                                ELSE 0 END
                       ), 0) AS toplam_alis
                FROM tedarikciler t
                LEFT JOIN urunler u
                       ON u.tedarikci_id = t.id
                      AND u.durum = 'aktif'
                LEFT JOIN urun_hareketleri h
                       ON h.kaynak_id  = t.id
                      AND h.kaynak_tip = 'tedarikci'
                WHERE t.ad LIKE ?
                GROUP BY t.id
                ORDER BY t.ad
            ");
            $stmt->execute([$ara]);
            cevap(true, '', ['tedarikciler' => $stmt->fetchAll()]);

        // ════════════════════════════════════════════════════
        // TEDARİKÇİ KAYDET
        // ════════════════════════════════════════════════════
        case 'tedarikci_kaydet':
            $id = (int) ($_POST['id'] ?? 0);
            $ad = trim($_POST['ad'] ?? '');
            $tel = trim($_POST['telefon'] ?? '');
            $email = trim($_POST['email'] ?? '');
            $adres = trim($_POST['adres'] ?? '');
            $vno = trim($_POST['vergi_no'] ?? '');
            $vd = trim($_POST['vergi_dairesi'] ?? '');
            $notlar = trim($_POST['notlar'] ?? '');

            if (!$ad)
                cevap(false, 'Tedarikçi adı zorunludur.');

            // Sütunlar var mı kontrol et — yoksa ekle
            $kolonlar = $pdo->query("DESCRIBE tedarikciler")->fetchAll(PDO::FETCH_COLUMN);
            if (!in_array('vergi_no', $kolonlar)) {
                $pdo->exec("ALTER TABLE tedarikciler
                    ADD COLUMN vergi_no       VARCHAR(20)  DEFAULT NULL,
                    ADD COLUMN vergi_dairesi  VARCHAR(100) DEFAULT NULL,
                    ADD COLUMN notlar         TEXT         DEFAULT NULL
                ");
            }

            if ($id) {
                $pdo->prepare("
                    UPDATE tedarikciler SET
                        ad            = ?,
                        telefon       = ?,
                        email         = ?,
                        adres         = ?,
                        vergi_no      = ?,
                        vergi_dairesi = ?,
                        notlar        = ?
                    WHERE id = ?
                ")->execute([
                            $ad,
                            $tel ?: null,
                            $email ?: null,
                            $adres ?: null,
                            $vno ?: null,
                            $vd ?: null,
                            $notlar ?: null,
                            $id
                        ]);
                cevap(true, 'Tedarikçi güncellendi.', ['id' => $id]);
            } else {
                $pdo->prepare("
                    INSERT INTO tedarikciler
                        (ad, telefon, email, adres,
                         vergi_no, vergi_dairesi, notlar)
                    VALUES (?, ?, ?, ?, ?, ?, ?)
                ")->execute([
                            $ad,
                            $tel ?: null,
                            $email ?: null,
                            $adres ?: null,
                            $vno ?: null,
                            $vd ?: null,
                            $notlar ?: null,
                        ]);
                cevap(true, 'Tedarikçi eklendi.', ['id' => (int) $pdo->lastInsertId()]);
            }

        // ════════════════════════════════════════════════════
        // TEDARİKÇİ SİL
        // ════════════════════════════════════════════════════
        case 'tedarikci_sil':
            $id = (int) ($_POST['id'] ?? 0);
            if (!$id)
                cevap(false, 'Geçersiz ID.');

            $stmt = $pdo->prepare("
                SELECT COUNT(*) FROM urunler
                WHERE tedarikci_id = ? AND durum = 'aktif'
            ");
            $stmt->execute([$id]);
            if ((int) $stmt->fetchColumn() > 0)
                cevap(false, 'Bu tedarikçiye bağlı aktif ürünler var.');

            $pdo->prepare("DELETE FROM tedarikciler WHERE id = ?")->execute([$id]);
            cevap(true, 'Tedarikçi silindi.');

        // ════════════════════════════════════════════════════
        // TEDARİKÇİ HAREKETLERİ
        // ════════════════════════════════════════════════════
        case 'tedarikci_hareketleri':
            $id = (int) ($_GET['id'] ?? 0);
            if (!$id)
                cevap(false, 'Geçersiz ID.');

            $stmt = $pdo->prepare("
                SELECT h.*,
                       u.ad        AS urun_adi,
                       u.urun_kodu AS urun_kodu
                FROM urun_hareketleri h
                JOIN urunler u ON h.urun_id = u.id
                WHERE h.kaynak_id  = ?
                  AND h.kaynak_tip = 'tedarikci'
                ORDER BY h.tarih DESC
                LIMIT 100
            ");
            $stmt->execute([$id]);
            cevap(true, '', ['hareketler' => $stmt->fetchAll()]);

        default:
            cevap(false, 'Geçersiz işlem: ' . htmlspecialchars($action));
    }

} catch (PDOException $e) {
    cevap(false, 'Veritabanı hatası: ' . $e->getMessage());
} catch (Throwable $e) {
    cevap(false, 'Sunucu hatası: ' . $e->getMessage());
}
