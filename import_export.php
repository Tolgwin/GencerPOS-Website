<?php
require_once 'db.php';
require_once 'auth.php';
require_once __DIR__ . '/vendor/autoload.php';

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Reader\Xlsx as XlsxReader;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\NumberFormat;

// ── YARDIMCI: JSON cevap ──────────────────────────────────────
function jsonCevap(bool $ok, string $msg, array $data = []): void {
    header('Content-Type: application/json; charset=UTF-8');
    echo json_encode(['basari' => $ok, 'mesaj' => $msg] + $data, JSON_UNESCAPED_UNICODE);
    exit;
}

// ── YARDIMCI: Stil uygula ──────────────────────────────────────
function headerStil(Spreadsheet $sp, string $sheet, array $cols, int $row = 1): void {
    $ws = $sp->getSheetByName($sheet) ?? $sp->getActiveSheet();
    foreach ($cols as $col) {
        $cell = $col . $row;
        $ws->getStyle($cell)->applyFromArray([
            'font'      => ['bold' => true, 'color' => ['argb' => 'FFFFFFFF'], 'size' => 11],
            'fill'      => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['argb' => 'FF1E3A8A']],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER],
            'borders'   => ['allBorders' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['argb' => 'FFCBD5E1']]],
        ]);
    }
}

function satirStil(Spreadsheet $sp, string $sheet, int $row, int $colCount, bool $alt = false): void {
    $ws = $sp->getSheetByName($sheet) ?? $sp->getActiveSheet();
    $range = 'A' . $row . ':' . \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($colCount) . $row;
    $ws->getStyle($range)->applyFromArray([
        'fill'      => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['argb' => $alt ? 'FFF8FAFF' : 'FFFFFFFF']],
        'borders'   => ['allBorders' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['argb' => 'FFE2E8F0']]],
        'alignment' => ['vertical' => Alignment::VERTICAL_CENTER],
    ]);
}

function xlsxIndir(Spreadsheet $sp, string $dosyaAdi): void {
    $writer = new Xlsx($sp);
    header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
    header('Content-Disposition: attachment; filename="' . $dosyaAdi . '"');
    header('Cache-Control: max-age=0');
    $writer->save('php://output');
    exit;
}

$action = $_GET['action'] ?? $_POST['action'] ?? '';

// ═══════════════════════════════════════════════════════════════
// EXPORT
// ═══════════════════════════════════════════════════════════════

// ── Müşteri Export ────────────────────────────────────────────
if ($action === 'export_musteriler') {
    $rows = $pdo->query("
        SELECT m.id, m.ad_soyad, m.musteri_tipi, m.email,
               m.telefon,
               m.vergi_dairesi, m.vergi_no,
               m.adres,
               m.baslangic_bakiye, m.notlar,
               DATE_FORMAT(m.olusturma_tarihi,'%d.%m.%Y') AS kayit_tarihi
        FROM musteriler m ORDER BY m.ad_soyad
    ")->fetchAll(PDO::FETCH_ASSOC);

    $sp = new Spreadsheet();
    $ws = $sp->getActiveSheet()->setTitle('Müşteriler');
    $basliklar = ['ID','Ad Soyad/Ünvan','Tip','E-posta','Telefon','Vergi Dairesi','Vergi No','Adres','Başlangıç Bakiye','Notlar','Kayıt Tarihi'];
    $ws->fromArray($basliklar, null, 'A1');
    headerStil($sp, 'Müşteriler', range('A','K'));
    $ws->getRowDimension(1)->setRowHeight(24);

    foreach ($rows as $i => $r) {
        $rn = $i + 2;
        $ws->fromArray(array_values($r), null, 'A' . $rn);
        satirStil($sp, 'Müşteriler', $rn, 11, $i % 2 === 1);
    }
    foreach (['A'=>8,'B'=>32,'C'=>13,'D'=>26,'E'=>16,'F'=>20,'G'=>14,'H'=>30,'I'=>16,'J'=>30,'K'=>14] as $col=>$w)
        $ws->getColumnDimension($col)->setWidth($w);

    xlsxIndir($sp, 'musteriler_' . date('Ymd') . '.xlsx');
}

// ── Ürün Export ────────────────────────────────────────────────
if ($action === 'export_urunler') {
    $rows = $pdo->query("
        SELECT u.id, u.urun_kodu, k.ad AS kategori, u.ad,
               u.aciklama, u.alis_fiyati, u.satis_fiyati, u.bayi_fiyati,
               u.kdv_orani, u.stok_adeti, u.birim,
               IF(u.seri_no_takip,1,0) AS seri_no_takip, u.durum
        FROM urunler u LEFT JOIN kategoriler k ON k.id=u.kategori_id ORDER BY u.ad
    ")->fetchAll(PDO::FETCH_ASSOC);

    $sp = new Spreadsheet();
    $ws = $sp->getActiveSheet()->setTitle('Ürünler');
    $basliklar = ['ID','Ürün Kodu','Kategori','Ürün Adı','Açıklama','Alış Fiyatı','Satış Fiyatı','Bayi Fiyatı','KDV %','Stok','Birim','Seri No Takip','Durum'];
    $ws->fromArray($basliklar, null, 'A1');
    headerStil($sp, 'Ürünler', range('A','M'));
    $ws->getRowDimension(1)->setRowHeight(24);

    foreach ($rows as $i => $r) {
        $rn = $i + 2;
        $ws->fromArray(array_values($r), null, 'A' . $rn);
        foreach (['F','G','H'] as $c)
            $ws->getStyle($c.$rn)->getNumberFormat()->setFormatCode('#,##0.00 ₺');
        satirStil($sp, 'Ürünler', $rn, 13, $i % 2 === 1);
    }
    foreach (['A'=>7,'B'=>14,'C'=>16,'D'=>28,'E'=>32,'F'=>14,'G'=>14,'H'=>14,'I'=>8,'J'=>10,'K'=>10,'L'=>12,'M'=>10] as $col=>$w)
        $ws->getColumnDimension($col)->setWidth($w);

    xlsxIndir($sp, 'urunler_' . date('Ymd') . '.xlsx');
}

// ── Fatura Export ──────────────────────────────────────────────
if ($action === 'export_faturalar') {
    $rows = $pdo->query("
        SELECT f.id, f.fatura_no,
               m.ad_soyad AS musteri,
               f.alici_vkn, DATE_FORMAT(f.tarih,'%d.%m.%Y') AS tarih,
               DATE_FORMAT(f.vade_tarihi,'%d.%m.%Y') AS vade,
               f.matrah, f.kdv_tutari, f.toplam,
               f.durum, f.odeme_durumu,
               p.ad_soyad AS personel,
               f.notlar
        FROM faturalar f
        LEFT JOIN musteriler m ON m.id=f.musteri_id
        LEFT JOIN personeller p ON p.id=f.personel_id
        ORDER BY f.tarih DESC
    ")->fetchAll(PDO::FETCH_ASSOC);

    $sp = new Spreadsheet();
    $ws = $sp->getActiveSheet()->setTitle('Faturalar');
    $basliklar = ['ID','Fatura No','Müşteri','Alıcı VKN','Tarih','Vade','Matrah','KDV','Toplam','Durum','Ödeme Durumu','Personel','Notlar'];
    $ws->fromArray($basliklar, null, 'A1');
    headerStil($sp, 'Faturalar', range('A','M'));
    $ws->getRowDimension(1)->setRowHeight(24);

    foreach ($rows as $i => $r) {
        $rn = $i + 2;
        $ws->fromArray(array_values($r), null, 'A' . $rn);
        foreach (['G','H','I'] as $c)
            $ws->getStyle($c.$rn)->getNumberFormat()->setFormatCode('#,##0.00 ₺');
        satirStil($sp, 'Faturalar', $rn, 13, $i % 2 === 1);
    }
    foreach (['A'=>7,'B'=>18,'C'=>28,'D'=>14,'E'=>12,'F'=>12,'G'=>14,'H'=>12,'I'=>14,'J'=>12,'K'=>14,'L'=>20,'M'=>30] as $col=>$w)
        $ws->getColumnDimension($col)->setWidth($w);

    xlsxIndir($sp, 'faturalar_' . date('Ymd') . '.xlsx');
}

// ── Fatura Kalemleri Export ────────────────────────────────────
if ($action === 'export_fatura_kalemleri') {
    $rows = $pdo->query("
        SELECT f.fatura_no, fk.urun_adi, fk.urun_kodu, fk.seri_no,
               fk.miktar, fk.birim_fiyat, fk.kdv_orani,
               fk.kdv_tutar, fk.satir_toplam,
               DATE_FORMAT(f.tarih,'%d.%m.%Y') AS fatura_tarihi
        FROM fatura_kalemleri fk
        JOIN faturalar f ON f.id=fk.fatura_id
        ORDER BY f.tarih DESC, f.fatura_no
    ")->fetchAll(PDO::FETCH_ASSOC);

    $sp = new Spreadsheet();
    $ws = $sp->getActiveSheet()->setTitle('Fatura Kalemleri');
    $basliklar = ['Fatura No','Ürün Adı','Ürün Kodu','Seri No','Miktar','Birim Fiyat','KDV %','KDV Tutarı','Satır Toplam','Fatura Tarihi'];
    $ws->fromArray($basliklar, null, 'A1');
    headerStil($sp, 'Fatura Kalemleri', range('A','J'));
    $ws->getRowDimension(1)->setRowHeight(24);

    foreach ($rows as $i => $r) {
        $rn = $i + 2;
        $ws->fromArray(array_values($r), null, 'A' . $rn);
        foreach (['F','H','I'] as $c)
            $ws->getStyle($c.$rn)->getNumberFormat()->setFormatCode('#,##0.00 ₺');
        satirStil($sp, 'Fatura Kalemleri', $rn, 10, $i % 2 === 1);
    }
    foreach (['A'=>18,'B'=>28,'C'=>14,'D'=>16,'E'=>8,'F'=>14,'G'=>8,'H'=>14,'I'=>14,'J'=>14] as $col=>$w)
        $ws->getColumnDimension($col)->setWidth($w);

    xlsxIndir($sp, 'fatura_kalemleri_' . date('Ymd') . '.xlsx');
}

// ── Cari Export ────────────────────────────────────────────────
if ($action === 'export_cariler') {
    $rows = $pdo->query("
        SELECT m.id, m.ad_soyad, m.vergi_no,
               COALESCE(m.baslangic_bakiye,0) AS baslangic_bakiye,
               COALESCE((SELECT SUM(f.toplam) FROM faturalar f WHERE f.musteri_id=m.id),0) AS toplam_fatura,
               COALESCE((SELECT SUM(t.tutar)  FROM tahsilatlar t WHERE t.musteri_id=m.id),0) AS toplam_tahsilat,
               COALESCE(m.baslangic_bakiye,0)
                 + COALESCE((SELECT SUM(f.toplam) FROM faturalar f WHERE f.musteri_id=m.id),0)
                 - COALESCE((SELECT SUM(t.tutar)  FROM tahsilatlar t WHERE t.musteri_id=m.id),0)
               AS kalan_bakiye
        FROM musteriler m ORDER BY m.ad_soyad
    ")->fetchAll(PDO::FETCH_ASSOC);

    $sp = new Spreadsheet();
    $ws = $sp->getActiveSheet()->setTitle('Cari Hesaplar');
    $basliklar = ['ID','Ad Soyad/Ünvan','Vergi No','Başlangıç Bakiye','Toplam Fatura','Toplam Tahsilat','Kalan Bakiye'];
    $ws->fromArray($basliklar, null, 'A1');
    headerStil($sp, 'Cari Hesaplar', range('A','G'));
    $ws->getRowDimension(1)->setRowHeight(24);

    foreach ($rows as $i => $r) {
        $rn = $i + 2;
        $ws->fromArray(array_values($r), null, 'A' . $rn);
        foreach (['D','E','F','G'] as $c) {
            $ws->getStyle($c.$rn)->getNumberFormat()->setFormatCode('#,##0.00 ₺');
            $val = (float)$r[array_keys($r)[array_search($c, ['D','E','F','G']) + 3]] ?? 0;
        }
        // Kalan bakiye negatifse kırmızı
        $kalan = (float)$r['kalan_bakiye'];
        if ($kalan > 0) {
            $ws->getStyle('G'.$rn)->getFont()->getColor()->setARGB('FFDC2626');
        }
        satirStil($sp, 'Cari Hesaplar', $rn, 7, $i % 2 === 1);
    }
    foreach (['A'=>7,'B'=>30,'C'=>14,'D'=>18,'E'=>18,'F'=>18,'G'=>16] as $col=>$w)
        $ws->getColumnDimension($col)->setWidth($w);

    // Toplam satırı
    $son = count($rows) + 2;
    $ws->setCellValue('B'.$son, 'TOPLAM');
    $ws->getStyle('B'.$son)->getFont()->setBold(true);
    foreach (['D','E','F','G'] as $idx => $c) {
        $ws->setCellValue($c.$son, '=SUM('.$c.'2:'.$c.($son-1).')');
        $ws->getStyle($c.$son)->getNumberFormat()->setFormatCode('#,##0.00 ₺');
        $ws->getStyle($c.$son)->getFont()->setBold(true);
        $ws->getStyle($c.$son)->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setARGB('FFDBEAFE');
    }

    xlsxIndir($sp, 'cariler_' . date('Ymd') . '.xlsx');
}

// ── Şablon İndir ───────────────────────────────────────────────
if ($action === 'sablon_musteri') {
    $sp = new Spreadsheet();
    $ws = $sp->getActiveSheet()->setTitle('Müşteriler');
    $basliklar = ['Ad Soyad/Ünvan *','Tip (bireysel/kurumsal)','E-posta','Telefon','Vergi Dairesi','Vergi No','Adres','Notlar'];
    $ws->fromArray($basliklar, null, 'A1');
    headerStil($sp, 'Müşteriler', range('A','H'));
    $ws->fromArray(['Ahmet Yılmaz','bireysel','ahmet@mail.com','05321234567','Ankara VD','12345678901','Atatürk Cad. No:1',''], null, 'A2');
    foreach (['A'=>30,'B'=>22,'C'=>26,'D'=>16,'E'=>20,'F'=>14,'G'=>30,'H'=>20] as $col=>$w)
        $ws->getColumnDimension($col)->setWidth($w);
    // Yorum ekle
    $ws->getComment('A1')->getText()->createTextRun('Zorunlu alan');
    xlsxIndir($sp, 'musteri_sablon.xlsx');
}

if ($action === 'sablon_urun') {
    $sp = new Spreadsheet();
    $ws = $sp->getActiveSheet()->setTitle('Ürünler');
    $basliklar = ['Ürün Kodu *','Ürün Adı *','Kategori','Açıklama','Alış Fiyatı','Satış Fiyatı','Bayi Fiyatı','KDV %','Stok','Birim','Seri No Takip (0/1)'];
    $ws->fromArray($basliklar, null, 'A1');
    headerStil($sp, 'Ürünler', range('A','K'));
    $ws->fromArray(['URN001','Örnek Ürün','Elektronik','Ürün açıklaması','100.00','150.00','130.00','20','10','Adet','0'], null, 'A2');
    foreach (['A'=>14,'B'=>28,'C'=>16,'D'=>32,'E'=>14,'F'=>14,'G'=>14,'H'=>8,'I'=>8,'J'=>10,'K'=>20] as $col=>$w)
        $ws->getColumnDimension($col)->setWidth($w);
    xlsxIndir($sp, 'urun_sablon.xlsx');
}

// ═══════════════════════════════════════════════════════════════
// IMPORT
// ═══════════════════════════════════════════════════════════════

if ($action === 'import_musteriler' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    if (empty($_FILES['dosya']['tmp_name'])) jsonCevap(false, 'Dosya yüklenmedi.');
    try {
        $reader = new XlsxReader();
        $reader->setReadDataOnly(true);
        $sp = $reader->load($_FILES['dosya']['tmp_name']);
        $ws = $sp->getActiveSheet();
        $data = $ws->toArray(null, true, true, false);
    } catch (Exception $e) { jsonCevap(false, 'Excel okunamadı: ' . $e->getMessage()); }

    if (count($data) < 2) jsonCevap(false, 'Dosya boş veya başlık satırı eksik.');
    $header = array_map('trim', $data[0]);
    array_shift($data);

    $eklenen = $guncellenen = $atlanan = 0; $hatalar = [];
    $stmtChk = $pdo->prepare("SELECT id FROM musteriler WHERE ad_soyad=? LIMIT 1");
    $stmtIns = $pdo->prepare("INSERT INTO musteriler (ad_soyad,musteri_tipi,email,telefon,vergi_dairesi,vergi_no,adres,baslangic_bakiye,notlar) VALUES (?,?,?,?,?,?,?,?,?)");
    $stmtUpd = $pdo->prepare("UPDATE musteriler SET musteri_tipi=?,email=?,telefon=?,vergi_dairesi=?,vergi_no=?,adres=?,baslangic_bakiye=?,notlar=? WHERE ad_soyad=?");

    foreach ($data as $i => $row) {
        $r = array_combine($header, $row);
        $ad = mb_strtoupper(trim($r['Ad Soyad/Ünvan *'] ?? $r['Ad Soyad/Ünvan'] ?? ''), 'UTF-8');
        if (!$ad) { $atlanan++; continue; }
        $tip    = in_array($r['Tip (bireysel/kurumsal)'] ?? '', ['bireysel','kurumsal']) ? $r['Tip (bireysel/kurumsal)'] : 'bireysel';
        $email  = strtolower(trim($r['E-posta'] ?? ''));
        $tel    = trim($r['Telefon'] ?? '');
        $vd     = mb_strtoupper(trim($r['Vergi Dairesi'] ?? ''), 'UTF-8');
        $vn     = trim($r['Vergi No'] ?? '');
        $adres  = trim($r['Adres'] ?? '');
        $bakiye = floatval(str_replace(',', '.', $r['Başlangıç Bakiye'] ?? '0'));
        $not    = trim($r['Notlar'] ?? '');
        try {
            $stmtChk->execute([$ad]);
            if ($stmtChk->fetchColumn()) {
                $stmtUpd->execute([$tip,$email,$tel,$vd,$vn,$adres,$bakiye,$not,$ad]);
                $guncellenen++;
            } else {
                $stmtIns->execute([$ad,$tip,$email,$tel,$vd,$vn,$adres,$bakiye,$not]);
                $eklenen++;
            }
        } catch (Exception $e) { $hatalar[] = 'Satır '.($i+2).': '.$e->getMessage(); }
    }
    jsonCevap(true, "$eklenen yeni eklendi, $guncellenen güncellendi, $atlanan atlandı.", ['hatalar' => $hatalar]);
}

if ($action === 'import_urunler' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    if (empty($_FILES['dosya']['tmp_name'])) jsonCevap(false, 'Dosya yüklenmedi.');
    try {
        $reader = new XlsxReader();
        $reader->setReadDataOnly(true);
        $sp = $reader->load($_FILES['dosya']['tmp_name']);
        $data = $sp->getActiveSheet()->toArray(null, true, true, false);
    } catch (Exception $e) { jsonCevap(false, 'Excel okunamadı: ' . $e->getMessage()); }

    if (count($data) < 2) jsonCevap(false, 'Dosya boş.');
    $header = array_map('trim', $data[0]);
    array_shift($data);

    $eklenen = $guncellenen = $atlanan = 0; $hatalar = [];
    $katCache = [];
    foreach ($pdo->query("SELECT id,ad FROM kategoriler")->fetchAll(PDO::FETCH_ASSOC) as $k)
        $katCache[mb_strtolower($k['ad'], 'UTF-8')] = $k['id'];

    $stmtChk = $pdo->prepare("SELECT id FROM urunler WHERE urun_kodu=? LIMIT 1");
    $stmtIns = $pdo->prepare("INSERT INTO urunler (urun_kodu,kategori_id,ad,aciklama,alis_fiyati,satis_fiyati,bayi_fiyati,kdv_orani,stok_adeti,birim,seri_no_takip,durum) VALUES (?,?,?,?,?,?,?,?,?,?,?,'aktif')");
    $stmtUpd = $pdo->prepare("UPDATE urunler SET kategori_id=?,aciklama=?,alis_fiyati=?,satis_fiyati=?,bayi_fiyati=?,kdv_orani=?,stok_adeti=?,birim=?,seri_no_takip=? WHERE urun_kodu=?");

    foreach ($data as $i => $row) {
        $r = array_combine($header, $row);
        $kod = trim($r['Ürün Kodu *'] ?? $r['Ürün Kodu'] ?? '');
        $ad  = trim($r['Ürün Adı *']  ?? $r['Ürün Adı']  ?? '');
        if (!$kod || !$ad) { $atlanan++; continue; }

        $katAd = mb_strtolower(trim($r['Kategori'] ?? ''), 'UTF-8');
        $katId = $katCache[$katAd] ?? null;
        if (!$katId && $katAd) {
            $pdo->prepare("INSERT INTO kategoriler (ad) VALUES (?)")->execute([ucwords($katAd)]);
            $katId = $pdo->lastInsertId(); $katCache[$katAd] = $katId;
        }
        $acik  = trim($r['Açıklama'] ?? '');
        $alis  = floatval(str_replace(',', '.', $r['Alış Fiyatı']  ?? '0'));
        $satis = floatval(str_replace(',', '.', $r['Satış Fiyatı'] ?? '0'));
        $bayi  = floatval(str_replace(',', '.', $r['Bayi Fiyatı']  ?? '0'));
        $kdv   = floatval($r['KDV %'] ?? '20');
        $stok  = floatval(str_replace(',', '.', $r['Stok'] ?? '0'));
        $birim = trim($r['Birim'] ?? 'Adet');
        $seri  = intval($r['Seri No Takip (0/1)'] ?? '0');
        try {
            $stmtChk->execute([$kod]);
            if ($stmtChk->fetchColumn()) {
                $stmtUpd->execute([$katId,$acik,$alis,$satis,$bayi,$kdv,$stok,$birim,$seri,$kod]);
                $guncellenen++;
            } else {
                $stmtIns->execute([$kod,$katId,$ad,$acik,$alis,$satis,$bayi,$kdv,$stok,$birim,$seri]);
                $eklenen++;
            }
        } catch (Exception $e) { $hatalar[] = 'Satır '.($i+2).': '.$e->getMessage(); }
    }
    jsonCevap(true, "$eklenen yeni eklendi, $guncellenen güncellendi, $atlanan atlandı.", ['hatalar' => $hatalar]);
}

if ($action === 'import_cariler' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    if (empty($_FILES['dosya']['tmp_name'])) jsonCevap(false, 'Dosya yüklenmedi.');
    try {
        $reader = new XlsxReader();
        $reader->setReadDataOnly(true);
        $sp = $reader->load($_FILES['dosya']['tmp_name']);
        $data = $sp->getActiveSheet()->toArray(null, true, true, false);
    } catch (Exception $e) { jsonCevap(false, 'Excel okunamadı: ' . $e->getMessage()); }

    if (count($data) < 2) jsonCevap(false, 'Dosya boş.');
    $header = array_map('trim', $data[0]);
    array_shift($data);
    $guncellenen = $atlanan = 0; $hatalar = [];
    $stmtChk = $pdo->prepare("SELECT id FROM musteriler WHERE ad_soyad=? OR (vergi_no=? AND vergi_no!='') LIMIT 1");
    $stmtUpd = $pdo->prepare("UPDATE musteriler SET baslangic_bakiye=? WHERE id=?");
    foreach ($data as $i => $row) {
        $r = array_combine($header, $row);
        $ad     = mb_strtoupper(trim($r['Ad Soyad/Ünvan'] ?? ''), 'UTF-8');
        $vn     = trim($r['Vergi No'] ?? '');
        $bakiye = floatval(str_replace(',', '.', $r['Başlangıç Bakiye'] ?? '0'));
        if (!$ad) { $atlanan++; continue; }
        try {
            $stmtChk->execute([$ad, $vn]);
            $mid = $stmtChk->fetchColumn();
            if ($mid) { $stmtUpd->execute([$bakiye, $mid]); $guncellenen++; }
            else $atlanan++;
        } catch (Exception $e) { $hatalar[] = 'Satır '.($i+2).': '.$e->getMessage(); }
    }
    jsonCevap(true, "$guncellenen cari güncellendi, $atlanan eşleşme bulunamadı.", ['hatalar' => $hatalar]);
}

// ── İstatistik ────────────────────────────────────────────────
$istat = [
    'musteri_sayisi'  => $pdo->query("SELECT COUNT(*) FROM musteriler")->fetchColumn(),
    'urun_sayisi'     => $pdo->query("SELECT COUNT(*) FROM urunler")->fetchColumn(),
    'fatura_sayisi'   => $pdo->query("SELECT COUNT(*) FROM faturalar")->fetchColumn(),
    'tahsilat_toplam' => $pdo->query("SELECT COALESCE(SUM(tutar),0) FROM tahsilatlar")->fetchColumn(),
];
?>
<!DOCTYPE html>
<html lang="tr">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1.0">
<title>📤 Import / Export</title>
<link rel="stylesheet" href="style.css">
<style>
:root{--blue:#1e3a8a;--blue2:#3b82f6;--green:#10b981;--orange:#f59e0b;--red:#ef4444;--gray:#6b7280;}
body{background:#f0f4ff;font-family:'Segoe UI',sans-serif;}
.sayfa{max-width:1100px;margin:0 auto;padding:24px 16px;}
h1{font-size:22px;font-weight:800;color:var(--blue);margin-bottom:4px;}
.sub{color:var(--gray);font-size:13px;margin-bottom:24px;}
.ozet{display:grid;grid-template-columns:repeat(auto-fill,minmax(160px,1fr));gap:12px;margin-bottom:26px;}
.oz-kart{background:#fff;border-radius:12px;padding:16px 14px;text-align:center;box-shadow:0 1px 4px rgba(0,0,0,.07);}
.oz-kart .ikon{font-size:24px;margin-bottom:6px;}
.oz-kart .sayi{font-size:22px;font-weight:800;color:var(--blue);}
.oz-kart .lbl{font-size:11px;color:var(--gray);font-weight:700;text-transform:uppercase;}
.bkart{background:#fff;border-radius:14px;box-shadow:0 1px 4px rgba(0,0,0,.07);margin-bottom:22px;overflow:hidden;}
.bkart-baslik{padding:14px 20px;border-bottom:2px solid #f1f5f9;font-weight:800;font-size:15px;color:var(--blue);display:flex;align-items:center;gap:8px;}
.bkart-icerik{padding:20px;}
.tab-bar{display:flex;border-bottom:2px solid #f1f5f9;padding:0 20px;gap:2px;}
.tab{padding:10px 18px;border:none;background:none;cursor:pointer;font-size:13px;font-weight:600;color:#9ca3af;border-bottom:3px solid transparent;margin-bottom:-2px;transition:.15s;}
.tab.aktif{color:var(--blue);border-bottom-color:var(--blue);}
.tab-panel{display:none;padding:20px;}.tab-panel.aktif{display:block;}
.ie-grid{display:grid;grid-template-columns:repeat(auto-fill,minmax(230px,1fr));gap:14px;}
.ie-kart{border:1.5px solid #e5e7eb;border-radius:12px;padding:18px;transition:.15s;}
.ie-kart:hover{border-color:var(--blue2);box-shadow:0 2px 12px rgba(59,130,246,.1);}
.ie-kart h4{font-size:14px;font-weight:700;color:#1f2937;margin:0 0 6px;display:flex;align-items:center;gap:6px;}
.ie-kart p{font-size:12px;color:var(--gray);margin:0 0 14px;line-height:1.5;}
.xlsx-badge{display:inline-flex;align-items:center;gap:4px;background:#d1fae5;color:#065f46;font-size:11px;font-weight:700;padding:3px 8px;border-radius:6px;margin-bottom:10px;}
.btn{padding:9px 18px;border-radius:8px;border:none;cursor:pointer;font-size:13px;font-weight:700;transition:.15s;display:inline-flex;align-items:center;gap:6px;text-decoration:none;}
.btn:disabled{opacity:.5;cursor:not-allowed;}
.btn-blue{background:var(--blue);color:#fff;}.btn-blue:not(:disabled):hover{background:#2563eb;}
.btn-green{background:var(--green);color:#fff;}.btn-green:not(:disabled):hover{background:#059669;}
.btn-gray{background:#f3f4f6;color:#374151;border:1px solid #e2e8f0;}.btn-gray:hover{background:#e5e7eb;}
.btn-sm{padding:6px 12px;font-size:12px;}
.drop-zone{border:2.5px dashed #c7d2fe;border-radius:12px;padding:28px;text-align:center;cursor:pointer;transition:.15s;background:#fafbff;}
.drop-zone:hover,.drop-zone.over{border-color:var(--blue2);background:#eff6ff;}
.drop-zone .dz-ikon{font-size:36px;margin-bottom:8px;}
.drop-zone .dz-txt{font-size:13px;color:#374151;font-weight:600;}
.drop-zone .dz-sub{font-size:12px;color:var(--gray);margin-top:4px;}
.drop-zone input[type=file]{display:none;}
.dosya-secili{background:#d1fae5;border-color:#6ee7b7;border-style:solid;}
.sonuc-kutu{border-radius:10px;padding:14px 16px;font-size:13px;margin-top:14px;display:none;}
.sonuc-ok{background:#d1fae5;color:#065f46;border:1px solid #6ee7b7;}
.sonuc-err{background:#fee2e2;color:#991b1b;border:1px solid #fca5a5;}
.sonuc-kutu ul{margin:6px 0 0;padding-left:18px;}
.alert{padding:12px 16px;border-radius:8px;font-size:13px;margin-bottom:14px;}
.alert-info{background:#eff6ff;color:#1d4ed8;border:1px solid #bfdbfe;}
.alert-warn{background:#fffbeb;color:#92400e;border:1px solid #fde68a;}
.col-tags{display:flex;flex-wrap:wrap;gap:5px;margin:8px 0 14px;}
.col-tag{background:#eff6ff;color:#1d4ed8;border-radius:5px;padding:3px 8px;font-size:11px;font-weight:600;font-family:monospace;}
.col-tag.zorunlu{background:#fee2e2;color:#991b1b;}
.spin{display:inline-block;width:13px;height:13px;border:2px solid rgba(255,255,255,.3);border-top-color:#fff;border-radius:50%;animation:spin .6s linear infinite;}
@keyframes spin{to{transform:rotate(360deg)}}
</style>
</head>
<body>
<?php require_once 'menu.php'; ?>
<div class="sayfa">
  <h1>📤 Import / Export</h1>
  <div class="sub">Müşteri, Ürün, Fatura ve Cari verilerini <strong>Excel (.xlsx)</strong> formatında içe/dışa aktarın</div>

  <div class="ozet">
    <div class="oz-kart"><div class="ikon">👥</div><div class="sayi"><?= $istat['musteri_sayisi'] ?></div><div class="lbl">Müşteri</div></div>
    <div class="oz-kart"><div class="ikon">📦</div><div class="sayi"><?= $istat['urun_sayisi'] ?></div><div class="lbl">Ürün</div></div>
    <div class="oz-kart"><div class="ikon">📄</div><div class="sayi"><?= $istat['fatura_sayisi'] ?></div><div class="lbl">Fatura</div></div>
    <div class="oz-kart"><div class="ikon">💰</div><div class="sayi"><?= number_format($istat['tahsilat_toplam'],2,',','.') ?> ₺</div><div class="lbl">Toplam Tahsilat</div></div>
  </div>

  <div class="bkart">
    <div class="tab-bar">
      <button class="tab aktif" onclick="tabGec('Export',this)">⬇️ Dışa Aktar</button>
      <button class="tab" onclick="tabGec('Import',this)">⬆️ İçe Aktar</button>
    </div>

    <!-- EXPORT -->
    <div class="tab-panel aktif" id="tpExport">
      <div class="alert alert-info">ℹ️ Tüm dosyalar <strong>Excel (.xlsx)</strong> formatında indirilir. Microsoft Excel, LibreOffice ve Google Sheets ile açılabilir. Türkçe karakterler tam desteklenir.</div>
      <div class="ie-grid">
        <div class="ie-kart">
          <div class="xlsx-badge">🟢 .xlsx</div>
          <h4>👥 Müşteriler</h4>
          <p>Ad, iletişim, vergi bilgileri ve bakiye dahil tüm kayıtlar.</p>
          <a href="?action=export_musteriler" class="btn btn-blue btn-sm">⬇️ Excel İndir</a>
        </div>
        <div class="ie-kart">
          <div class="xlsx-badge">🟢 .xlsx</div>
          <h4>📦 Ürünler</h4>
          <p>Ürün kodu, fiyatlar, stok, kategori ve KDV dahil.</p>
          <a href="?action=export_urunler" class="btn btn-blue btn-sm">⬇️ Excel İndir</a>
        </div>
        <div class="ie-kart">
          <div class="xlsx-badge">🟢 .xlsx</div>
          <h4>📄 Faturalar</h4>
          <p>Tüm fatura başlıkları; müşteri, tutar, durum, personel.</p>
          <a href="?action=export_faturalar" class="btn btn-blue btn-sm">⬇️ Excel İndir</a>
        </div>
        <div class="ie-kart">
          <div class="xlsx-badge">🟢 .xlsx</div>
          <h4>📋 Fatura Kalemleri</h4>
          <p>Her faturanın satır detayları; ürün, miktar, fiyat, KDV.</p>
          <a href="?action=export_fatura_kalemleri" class="btn btn-blue btn-sm">⬇️ Excel İndir</a>
        </div>
        <div class="ie-kart">
          <div class="xlsx-badge">🟢 .xlsx</div>
          <h4>🏦 Cari Hesaplar</h4>
          <p>Borç/alacak özeti; toplam fatura, tahsilat ve kalan bakiye.</p>
          <a href="?action=export_cariler" class="btn btn-blue btn-sm">⬇️ Excel İndir</a>
        </div>
      </div>
    </div>

    <!-- IMPORT -->
    <div class="tab-panel" id="tpImport">
      <div class="alert alert-warn">⚠️ İçe aktarmadan önce mutlaka <strong>yedek alın</strong>! Aynı isme/koda sahip mevcut kayıtlar güncellenecektir.</div>

      <!-- Müşteri -->
      <div class="bkart" style="box-shadow:none;border:1.5px solid #e5e7eb;margin-bottom:16px;">
        <div class="bkart-baslik" style="border-bottom:1px solid #f1f5f9;font-size:14px;">👥 Müşteri İçe Aktar</div>
        <div class="bkart-icerik">
          <div style="display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:8px;margin-bottom:10px;">
            <div>
              <strong style="font-size:13px;">Sütunlar:</strong>
              <div class="col-tags">
                <span class="col-tag zorunlu">Ad Soyad/Ünvan *</span>
                <span class="col-tag">Tip (bireysel/kurumsal)</span>
                <span class="col-tag">E-posta</span><span class="col-tag">Telefon</span>
                <span class="col-tag">Vergi Dairesi</span><span class="col-tag">Vergi No</span>
                <span class="col-tag">Adres</span><span class="col-tag">Notlar</span>
              </div>
            </div>
            <a href="?action=sablon_musteri" class="btn btn-gray btn-sm">📄 Şablon İndir</a>
          </div>
          <div class="drop-zone" id="dzMusteri" onclick="document.getElementById('fileMusteri').click()"
               ondragover="dzOver(event,'dzMusteri')" ondragleave="dzLeave('dzMusteri')" ondrop="dzDrop(event,'dzMusteri','fileMusteri')">
            <div class="dz-ikon">📊</div>
            <div class="dz-txt">Excel dosyasını buraya sürükleyin veya tıklayın</div>
            <div class="dz-sub">Yalnızca .xlsx formatı</div>
            <input type="file" id="fileMusteri" accept=".xlsx" onchange="dosyaSec(this,'dzMusteri','musteriDosyaAd','btnImportMusteri')">
          </div>
          <div id="musteriDosyaAd" style="font-size:12px;color:#374151;margin-top:6px;display:none;"></div>
          <div style="margin-top:12px;">
            <button class="btn btn-green" id="btnImportMusteri" onclick="importEt('musteri')" disabled>⬆️ İçe Aktar</button>
          </div>
          <div class="sonuc-kutu" id="sonucMusteri"></div>
        </div>
      </div>

      <!-- Ürün -->
      <div class="bkart" style="box-shadow:none;border:1.5px solid #e5e7eb;margin-bottom:16px;">
        <div class="bkart-baslik" style="border-bottom:1px solid #f1f5f9;font-size:14px;">📦 Ürün İçe Aktar</div>
        <div class="bkart-icerik">
          <div style="display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:8px;margin-bottom:10px;">
            <div>
              <strong style="font-size:13px;">Sütunlar:</strong>
              <div class="col-tags">
                <span class="col-tag zorunlu">Ürün Kodu *</span><span class="col-tag zorunlu">Ürün Adı *</span>
                <span class="col-tag">Kategori</span><span class="col-tag">Açıklama</span>
                <span class="col-tag">Alış Fiyatı</span><span class="col-tag">Satış Fiyatı</span>
                <span class="col-tag">Bayi Fiyatı</span><span class="col-tag">KDV %</span>
                <span class="col-tag">Stok</span><span class="col-tag">Birim</span>
                <span class="col-tag">Seri No Takip (0/1)</span>
              </div>
            </div>
            <a href="?action=sablon_urun" class="btn btn-gray btn-sm">📄 Şablon İndir</a>
          </div>
          <div class="drop-zone" id="dzUrun" onclick="document.getElementById('fileUrun').click()"
               ondragover="dzOver(event,'dzUrun')" ondragleave="dzLeave('dzUrun')" ondrop="dzDrop(event,'dzUrun','fileUrun')">
            <div class="dz-ikon">📊</div>
            <div class="dz-txt">Excel dosyasını buraya sürükleyin veya tıklayın</div>
            <div class="dz-sub">Yalnızca .xlsx formatı</div>
            <input type="file" id="fileUrun" accept=".xlsx" onchange="dosyaSec(this,'dzUrun','urunDosyaAd','btnImportUrun')">
          </div>
          <div id="urunDosyaAd" style="font-size:12px;color:#374151;margin-top:6px;display:none;"></div>
          <div style="margin-top:12px;">
            <button class="btn btn-green" id="btnImportUrun" onclick="importEt('urun')" disabled>⬆️ İçe Aktar</button>
          </div>
          <div class="sonuc-kutu" id="sonucUrun"></div>
        </div>
      </div>

      <!-- Cari -->
      <div class="bkart" style="box-shadow:none;border:1.5px solid #e5e7eb;">
        <div class="bkart-baslik" style="border-bottom:1px solid #f1f5f9;font-size:14px;">🏦 Cari Başlangıç Bakiye Güncelle</div>
        <div class="bkart-icerik">
          <div class="alert alert-info" style="margin-bottom:12px;">ℹ️ Önce Cari Hesapları dışa aktarın, Başlangıç Bakiye sütununu düzenleyin ve tekrar yükleyin.</div>
          <div class="col-tags">
            <span class="col-tag zorunlu">Ad Soyad/Ünvan *</span>
            <span class="col-tag">Vergi No</span>
            <span class="col-tag zorunlu">Başlangıç Bakiye *</span>
          </div>
          <div class="drop-zone" id="dzCari" onclick="document.getElementById('fileCari').click()"
               ondragover="dzOver(event,'dzCari')" ondragleave="dzLeave('dzCari')" ondrop="dzDrop(event,'dzCari','fileCari')">
            <div class="dz-ikon">📊</div>
            <div class="dz-txt">Excel dosyasını buraya sürükleyin veya tıklayın</div>
            <input type="file" id="fileCari" accept=".xlsx" onchange="dosyaSec(this,'dzCari','cariDosyaAd','btnImportCari')">
          </div>
          <div id="cariDosyaAd" style="font-size:12px;color:#374151;margin-top:6px;display:none;"></div>
          <div style="margin-top:12px;">
            <button class="btn btn-green" id="btnImportCari" onclick="importEt('cari')" disabled>⬆️ Güncelle</button>
          </div>
          <div class="sonuc-kutu" id="sonucCari"></div>
        </div>
      </div>
    </div>
  </div>

  <div style="text-align:center;margin-top:8px;">
    <a href="index.php" class="btn btn-gray btn-sm">← Ana Sayfa</a>
    <a href="db_yonetim.php" class="btn btn-gray btn-sm" style="margin-left:8px;">🗄️ DB Yönetim</a>
  </div>
</div>

<script>
'use strict';
const fileMap  = {musteri:'fileMusteri', urun:'fileUrun', cari:'fileCari'};
const sonucMap = {musteri:'sonucMusteri', urun:'sonucUrun', cari:'sonucCari'};
const btnMap   = {musteri:'btnImportMusteri', urun:'btnImportUrun', cari:'btnImportCari'};
const actionMap= {musteri:'import_musteriler', urun:'import_urunler', cari:'import_cariler'};

function tabGec(id, btn) {
    document.querySelectorAll('.tab-panel').forEach(p=>p.classList.remove('aktif'));
    document.querySelectorAll('.tab').forEach(b=>b.classList.remove('aktif'));
    document.getElementById('tp'+id).classList.add('aktif');
    btn.classList.add('aktif');
}
function dosyaSec(inp, dzId, adId, btnId) {
    if (!inp.files.length) return;
    document.getElementById(dzId).classList.add('dosya-secili');
    const ad = document.getElementById(adId);
    ad.style.display='block';
    ad.textContent='📊 '+inp.files[0].name+' ('+(inp.files[0].size/1024).toFixed(1)+' KB)';
    document.getElementById(btnId).disabled=false;
}
function dzOver(e,id){e.preventDefault();document.getElementById(id).classList.add('over');}
function dzLeave(id){document.getElementById(id).classList.remove('over');}
function dzDrop(e,dzId,fileId){
    e.preventDefault();dzLeave(dzId);
    const files=e.dataTransfer.files;if(!files.length)return;
    const inp=document.getElementById(fileId);
    const dt=new DataTransfer();dt.items.add(files[0]);inp.files=dt.files;
    inp.dispatchEvent(new Event('change'));
}
async function importEt(tip){
    const inp=document.getElementById(fileMap[tip]);
    if(!inp.files.length)return;
    const btn=document.getElementById(btnMap[tip]);
    const sonucEl=document.getElementById(sonucMap[tip]);
    btn.disabled=true;btn.innerHTML='<span class="spin"></span> İşleniyor...';
    sonucEl.style.display='none';
    const fd=new FormData();
    fd.append('action',actionMap[tip]);
    fd.append('dosya',inp.files[0]);
    try{
        const r=await fetch('import_export.php',{method:'POST',body:fd});
        const v=await r.json();
        sonucEl.className='sonuc-kutu '+(v.basari?'sonuc-ok':'sonuc-err');
        sonucEl.style.display='block';
        let html=(v.basari?'✅ ':'❌ ')+v.mesaj;
        if(v.hatalar&&v.hatalar.length){
            html+='<ul>'+v.hatalar.slice(0,10).map(h=>'<li>'+h+'</li>').join('')+'</ul>';
            if(v.hatalar.length>10)html+='<small>...ve '+(v.hatalar.length-10)+' hata daha</small>';
        }
        sonucEl.innerHTML=html;
    }catch(e){
        sonucEl.className='sonuc-kutu sonuc-err';
        sonucEl.style.display='block';
        sonucEl.textContent='❌ Sunucu hatası: '+e.message;
    }
    btn.disabled=false;
    btn.innerHTML='⬆️ '+(tip==='cari'?'Güncelle':'İçe Aktar');
}
</script>
</body>
</html>
