<?php
require_once 'db.php';
require_once 'auth.php';
require_once 'EFaturaService.php';
require_once 'UBLBuilder.php';

$errors  = [];
$success = null;

function uretFaturaNo(PDO $pdo): string {
    $yil = date('Y');
    $row = $pdo->query("SELECT MAX(CAST(SUBSTRING(fatura_no, 8) AS UNSIGNED)) AS son FROM faturalar WHERE fatura_no LIKE 'FAT{$yil}%'")->fetch();
    $sira = ($row['son'] ?? 0) + 1;
    return 'FAT' . $yil . str_pad($sira, 9, '0', STR_PAD_LEFT);
}
$oneriliFaturaNo = uretFaturaNo($pdo);

// ── POST İşlemi ──────────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['kaydet'],$_POST['fatura_no'])) {
    $faturaNo   = trim($_POST['fatura_no'] ?? '');
    $musteriId  = (int)($_POST['musteri_id'] ?? 0);
    $tarih      = $_POST['tarih'] ?? date('Y-m-d');
    $vadeTarihi = $_POST['vade_tarihi'] ?? '';
    $notlar     = trim($_POST['notlar'] ?? '');
    $gonder     = isset($_POST['gonder_efatura']);
    $personelId = (int)($_POST['personel_id'] ?? 0) ?: null;

    $kalemUrunId   = $_POST['kalem_urun_id']   ?? [];
    $kalemAciklama = $_POST['kalem_aciklama']   ?? [];
    $kalemKod      = $_POST['kalem_kod']        ?? [];
    $kalemMiktar   = $_POST['kalem_miktar']     ?? [];
    $kalemFiyat    = $_POST['kalem_fiyat']      ?? [];
    $kalemKdv      = $_POST['kalem_kdv']        ?? [];
    $kalemSeriNo   = $_POST['kalem_seri_no']    ?? [];

    if (!$faturaNo)   $errors[] = 'Fatura numarası zorunludur.';
    if (!$musteriId)  $errors[] = 'Müşteri seçimi zorunludur.';
    if (!$personelId) $errors[] = 'Personel seçimi zorunludur.';
    if (empty(array_filter(array_map('trim', $kalemAciklama))))
        $errors[] = 'En az bir fatura kalemi ekleyin.';

    if (empty($errors)) {
        $stmtM = $pdo->prepare("SELECT * FROM musteriler WHERE id = ?");
        $stmtM->execute([$musteriId]);
        $musteri = $stmtM->fetch();
        if (!$musteri) $errors[] = 'Seçilen müşteri bulunamadı.';
    }

    if (empty($errors)) {
        $satirlar  = [];
        $matrahTop = 0;
        $kdvTop    = 0;

        foreach ($kalemAciklama as $i => $aciklama) {
            if (empty(trim($aciklama))) continue;
            $miktar      = (float)($kalemMiktar[$i] ?? 1);
            $birimFiyat  = (float)($kalemFiyat[$i] ?? 0);
            $kdvOran     = (float)($kalemKdv[$i] ?? 20);
            $satirMatrah = $miktar * $birimFiyat;
            $satirKdv    = $satirMatrah * $kdvOran / 100;
            $satirlar[] = [
                'urun_id'     => (int)($kalemUrunId[$i] ?? 0),
                'aciklama'    => trim($aciklama),
                'urun_kodu'   => trim($kalemKod[$i] ?? ''),
                'miktar'      => $miktar,
                'birim_fiyat' => $birimFiyat,
                'kdv_oran'    => $kdvOran,
                'kdv_tutar'   => $satirKdv,
                'matrah'      => $satirMatrah,
                'seri_no'     => trim($kalemSeriNo[$i] ?? ''),
            ];
            $matrahTop += $satirMatrah;
            $kdvTop    += $satirKdv;
        }
        $toplamTutar = $matrahTop + $kdvTop;

        try {
            $pdo->beginTransaction();

            $stmtF = $pdo->prepare("
                INSERT INTO faturalar
                    (fatura_no, musteri_id, alici_vkn, alici_unvan, tarih, vade_tarihi,
                     matrah, kdv_tutari, toplam, durum, odeme_durumu, efatura_durum, notlar, personel_id)
                VALUES (:fn,:mid,:vkn,:unvan,:tarih,:vade,:matrah,:kdv,:toplam,'beklemede','odenmedi','TASLAK',:notlar,:pid)
            ");
            $stmtF->execute([
                ':fn'     => $faturaNo,
                ':mid'    => $musteriId,
                ':vkn'    => $musteri['vergi_no'] ?? '',
                ':unvan'  => $musteri['ad_soyad'],
                ':tarih'  => $tarih,
                ':vade'   => $vadeTarihi ?: null,
                ':matrah' => $matrahTop,
                ':kdv'    => $kdvTop,
                ':toplam' => $toplamTutar,
                ':notlar' => $notlar,
                ':pid'    => $personelId,
            ]);
            $faturaId = $pdo->lastInsertId();

            // ── Otomatik Prim Kaydı (ürün bazlı) ─────────────────
            if ($personelId) {
                $upStmt = $pdo->prepare("SELECT urun_id, prim_orani, prim_sabit_tutar FROM personel_urun_prim WHERE personel_id=?");
                $upStmt->execute([$personelId]);
                $upMap = [];
                foreach ($upStmt->fetchAll(PDO::FETCH_ASSOC) as $row) { $upMap[(int)$row['urun_id']] = $row; }
                $pRow2 = $pdo->prepare("SELECT prim_orani FROM personeller WHERE id=? AND aktif=1");
                $pRow2->execute([$personelId]);
                $genelOran = floatval(($pRow2->fetch() ?: [])['prim_orani'] ?? 0);
                $primInsert = $pdo->prepare("INSERT INTO personel_prim (personel_id,fatura_id,urun_id,urun_adi,fatura_tutari,kalem_tutari,prim_orani,prim_tutari,odeme_durumu,aciklama) VALUES (?,?,?,?,?,?,?,?,'beklemede',?)");
                $primEklendi = false;
                foreach ($satirlar as $s) {
                    $kalemToplam = round(floatval($s['matrah']) + floatval($s['kdv_tutar']), 2);
                    $uid = (int)($s['urun_id'] ?? 0);
                    if ($uid && isset($upMap[$uid])) {
                        $up = $upMap[$uid];
                        if ($up['prim_sabit_tutar'] !== null && $up['prim_sabit_tutar'] !== '') {
                            $primT = round(floatval($up['prim_sabit_tutar']) * floatval($s['miktar']), 2);
                            $oranK = 0;
                        } else {
                            $oranK = floatval($up['prim_orani']);
                            $primT = round($kalemToplam * $oranK / 100, 2);
                        }
                        $primInsert->execute([$personelId, $faturaId, $uid, $s['aciklama'], $toplamTutar, $kalemToplam, $oranK, $primT, 'Otomatik: ' . $s['aciklama']]);
                        $primEklendi = true;
                    }
                }
                if (!$primEklendi && $genelOran > 0) {
                    $primT = round($toplamTutar * $genelOran / 100, 2);
                    $primInsert->execute([$personelId, $faturaId, null, null, $toplamTutar, $toplamTutar, $genelOran, $primT, 'Otomatik: Fatura ' . $faturaNo]);
                }
            }

            $stmtK = $pdo->prepare("
                INSERT INTO fatura_kalemleri
                    (fatura_id, urun_id, urun_adi, urun_kodu, miktar, birim_fiyat, kdv_orani, kdv_tutar, satir_toplam)
                VALUES (:fid,:uid,:ad,:kod,:miktar,:fiyat,:kdv,:kdvt,:toplam)
            ");
            foreach ($satirlar as $s) {
                $stmtK->execute([
                    ':fid'    => $faturaId,
                    ':uid'    => ($s['urun_id'] > 0 ? $s['urun_id'] : null),
                    ':ad'     => $s['aciklama'],
                    ':kod'    => $s['urun_kodu'],
                    ':miktar' => $s['miktar'],
                    ':fiyat'  => $s['birim_fiyat'],
                    ':kdv'    => $s['kdv_oran'],
                    ':kdvt'   => $s['kdv_tutar'],
                    ':toplam' => $s['matrah'] + $s['kdv_tutar'],
                ]);
                // Seri no takip: birden fazla seri no destekle (JSON array)
                $seriNoRaw = trim($s['seri_no']);
                $seriNolar = [];
                if ($seriNoRaw) {
                    $decoded = json_decode($seriNoRaw, true);
                    if (is_array($decoded)) {
                        $seriNolar = array_values(array_filter(array_map('trim', $decoded)));
                    } elseif ($seriNoRaw !== '[]') {
                        $seriNolar = [$seriNoRaw];
                    }
                }
                if (!empty($seriNolar) && $s['urun_id']) {
                    $kalemSeriStr = implode(', ', $seriNolar);
                    $pdo->prepare("UPDATE fatura_kalemleri SET seri_no=? WHERE fatura_id=? AND urun_id=? ORDER BY id DESC LIMIT 1")
                        ->execute([$kalemSeriStr, $faturaId, $s['urun_id']]);
                    foreach ($seriNolar as $sn) {
                        if ($sn) {
                            $pdo->prepare("UPDATE seri_numaralari SET durum='satildi', fatura_id=? WHERE urun_id=? AND seri_no=? AND durum='stokta'")
                                ->execute([$faturaId, $s['urun_id'], $sn]);
                        }
                    }
                }
                // Stok düş
                if ($s['urun_id']) {
                    $pdo->prepare("UPDATE urunler SET stok_adeti = stok_adeti - ? WHERE id = ?")
                        ->execute([$s['miktar'], $s['urun_id']]);
                }
            }

            if ($gonder) {
                $cfg = require __DIR__ . '/config.php';
                $faturaData = [
                    'fatura_no'      => $faturaNo,
                    'gonderen_vkn'   => $cfg['username'],
                    'gonderen_unvan' => $cfg['firma_unvan'] ?? 'Gönderen Firma',
                    'alici_vkn'      => $musteri['vergi_no'] ?? $cfg['username'],
                    'alici_unvan'    => $musteri['ad_soyad'],
                    'tarih'          => $tarih,
                    'matrah'         => $matrahTop,
                    'kdv'            => $kdvTop,
                    'toplam'         => $toplamTutar,
                    'satirlar'       => array_map(fn($s) => [
                        'aciklama'    => $s['aciklama'],
                        'birim'       => 'C62',
                        'miktar'      => $s['miktar'],
                        'birim_fiyat' => $s['birim_fiyat'],
                        'kdv_oran'    => $s['kdv_oran'],
                        'kdv_tutar'   => $s['kdv_tutar'],
                        'matrah'      => $s['matrah'],
                    ], $satirlar),
                ];
                $xmlContent = UBLBuilder::build($faturaData);
                $service = new EFaturaService([
                    'username'      => $cfg['username'],
                    'password'      => $cfg['password'],
                    'user_wsdl'     => $cfg['wsdl_user'],
                    'connector_url' => str_replace('?wsdl', '', $cfg['wsdl_connector']),
                ]);
                $service->login();
                $ettn = $service->belgeGonder($xmlContent, $faturaNo);
                $service->logout();
                $pdo->prepare("UPDATE faturalar SET ettn=:ettn, efatura_durum='GONDERILDI' WHERE id=:id")
                    ->execute([':ettn' => $ettn, ':id' => $faturaId]);
            }

            $pdo->commit();

            // Tutanaktan gelindiyse tutanağa fatura_id yaz
            $fromTutanakPHP = $_POST['from_tutanak'] ?? ($_GET['from_tutanak'] ?? '');
            $tutanakIdPHP   = (int)($_POST['tutanak_id_ref'] ?? ($_GET['tutanak_id'] ?? 0));
            if ($fromTutanakPHP && $tutanakIdPHP) {
                $tbl = $fromTutanakPHP === 'devir' ? 'tutanak_devir' : 'tutanak_hurda';
                try {
                    $pdo->prepare("UPDATE $tbl SET fatura_id=? WHERE id=?")->execute([$faturaId, $tutanakIdPHP]);
                } catch (Exception $te) {}
            }

            header("Location: fatura_liste.php?mesaj=fatura_eklendi&id=" . $faturaId);
            exit;

        } catch (Exception $e) {
            $pdo->rollBack();
            $errors[] = 'Kayıt hatası: ' . $e->getMessage();
        }
    }
}

// Modal için vergi dairesi ve etiket verileri
$iller        = $pdo->query("SELECT DISTINCT il FROM vergi_daireleri ORDER BY il")->fetchAll(PDO::FETCH_COLUMN);
$vdTum        = $pdo->query("SELECT * FROM vergi_daireleri ORDER BY il, ad")->fetchAll();
$etiketlerTum = $pdo->query("SELECT * FROM etiketler ORDER BY ad")->fetchAll();
?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <title>Fatura Ekle</title>
    <link rel="stylesheet" href="style.css">
    <style>
        .sayfa-wrap { max-width: 1200px; margin: 0 auto; padding: 24px 16px; }
        h1 { font-size: 22px; font-weight: 700; margin-bottom: 24px; color: #1e3a8a; }
        .kart { background: #fff; border-radius: 12px; padding: 24px; box-shadow: 0 1px 4px rgba(0,0,0,.08); margin-bottom: 20px; }
        .kart h3 { font-size: 15px; font-weight: 700; margin-bottom: 16px; color: #1e3a8a; padding-bottom: 8px; border-bottom: 2px solid #e5e7eb; display:flex; align-items:center; justify-content:space-between; }
        .form-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 14px; }
        .form-grup label { display: block; font-size: 12px; font-weight: 600; color: #374151; margin-bottom: 5px; }
        .form-grup input, .form-grup select, .form-grup textarea {
            width: 100%; padding: 9px 12px; border: 1px solid #dde3f0;
            border-radius: 8px; font-size: 13px; outline: none; box-sizing: border-box; background:#fff;
        }
        .form-grup input:focus, .form-grup select:focus { border-color: #3b82f6; box-shadow: 0 0 0 3px rgba(59,130,246,.1); }
        .btn { padding: 10px 22px; border: none; border-radius: 8px; cursor: pointer; font-size: 14px; font-weight: 600; transition:.15s; }
        .btn-primary { background: #3b82f6; color: #fff; } .btn-primary:hover { background: #2563eb; }
        .btn-success { background: #10b981; color: #fff; } .btn-success:hover { background: #059669; }
        .btn-link { background:none; border:1px solid #3b82f6; color:#3b82f6; padding:8px 14px; font-size:13px; border-radius:8px; cursor:pointer; font-weight:600; }
        .btn-link:hover { background:#eff6ff; }
        .alert { padding: 12px 16px; border-radius: 8px; margin-bottom: 16px; font-size: 13px; }
        .alert-error { background: #fee2e2; color: #991b1b; border: 1px solid #fca5a5; }
        /* Kalem tablosu */
        .kalem-tablo { width: 100%; border-collapse: collapse; font-size: 13px; }
        .kalem-tablo th { background: #f8faff; padding: 9px 8px; text-align: left; font-weight: 600; color: #374151; border-bottom: 2px solid #e5e7eb; white-space:nowrap; }
        .kalem-tablo td { padding: 5px 6px; border-bottom: 1px solid #f3f4f6; vertical-align: middle; }
        .kalem-tablo input, .kalem-tablo select { padding: 7px 8px; border: 1px solid #dde3f0; border-radius: 6px; font-size: 13px; width: 100%; box-sizing: border-box; background:#fff; }
        .kalem-tablo tfoot td { padding: 10px 8px; font-weight: 700; }
        .text-right { text-align: right; }
        .btn-del { background:#fee2e2; color:#991b1b; border:none; border-radius:6px; padding:5px 10px; cursor:pointer; font-size:13px; font-weight:600; }
        .btn-del:hover { background:#fca5a5; }
        /* Autocomplete */
        .ac-wrap { position: relative; }
        .ac-dropdown { position:absolute; top:100%; left:0; right:0; z-index:999; background:#fff; border:1px solid #dde3f0; border-radius:8px; box-shadow:0 4px 16px rgba(0,0,0,.12); max-height:200px; overflow-y:auto; display:none; }
        .ac-item { padding:9px 14px; cursor:pointer; font-size:13px; border-bottom:1px solid #f3f4f6; }
        .ac-item:hover { background:#f0f4ff; }
        .ac-item small { color:#6b7280; display:block; }
        .musteri-info { font-size:12px; color:#374151; margin-top:6px; padding:8px 12px; background:#f0f9ff; border-radius:6px; border:1px solid #bae6fd; display:none; }
        /* Seri no badge */
        .seri-badge { display:inline-flex; align-items:center; gap:4px; background:#fef3c7; color:#92400e; border-radius:5px; padding:2px 7px; font-size:11px; font-weight:600; cursor:pointer; }
        .stok-kirmizi { color:#ef4444; font-weight:700; }
        .stok-yesil { color:#10b981; font-weight:700; }
        /* Kalem satır ürün autocomplete */
        .kalem-drop-wrap { position:relative; }
        .kalem-drop { position:absolute; top:100%; left:0; right:0; z-index:800; background:#fff; border:1px solid #dde3f0; border-radius:8px; box-shadow:0 4px 16px rgba(0,0,0,.14); max-height:220px; overflow-y:auto; display:none; }
        .kalem-drop-item { padding:8px 12px; cursor:pointer; font-size:12px; border-bottom:1px solid #f3f4f6; display:flex; gap:8px; align-items:center; }
        .kalem-drop-item:hover { background:#f0f4ff; }
        .kalem-drop-item .kd-ad { font-weight:600; color:#1e3a8a; flex:1; }
        .kalem-drop-item .kd-detay { color:#6b7280; font-size:11px; white-space:nowrap; }
        /* Modal */
        .modal-overlay { display:none; position:fixed; inset:0; background:rgba(0,0,0,.45); z-index:1000; align-items:center; justify-content:center; }
        .modal-overlay.aktif { display:flex; }
        .modal-kart { background:#fff; border-radius:14px; width:100%; max-width:560px; padding:28px; box-shadow:0 8px 40px rgba(0,0,0,.18); max-height:90vh; overflow-y:auto; }
        .modal-kart h3 { font-size:17px; font-weight:700; margin-bottom:18px; color:#1e3a8a; }
        .modal-form-grid { display:grid; grid-template-columns:1fr 1fr; gap:12px; }
        .modal-form-grid .tam { grid-column:1/-1; }
        .modal-footer { display:flex; gap:10px; justify-content:flex-end; margin-top:18px; }
        /* Ürün seçici modal */
        .urun-grid { display:grid; grid-template-columns:repeat(auto-fill,minmax(220px,1fr)); gap:10px; max-height:400px; overflow-y:auto; padding:4px; }
        .urun-kart { border:2px solid #e5e7eb; border-radius:10px; padding:12px; cursor:pointer; transition:.15s; }
        .urun-kart:hover { border-color:#3b82f6; background:#f0f4ff; }
        .urun-kart .urun-ad { font-weight:700; font-size:13px; color:#1e3a8a; margin-bottom:4px; }
        .urun-kart .urun-kod { font-size:11px; color:#6b7280; font-family:monospace; }
        .urun-kart .urun-fiyat { font-size:14px; font-weight:700; color:#10b981; margin-top:6px; }
        .urun-kart .urun-stok { font-size:11px; margin-top:3px; }
        /* ── YENİ MÜŞTERİ MODAL — Modern Tasarım ── */
        #musteriModal { backdrop-filter:blur(4px); }
        .mm-kart { background:#fff; border-radius:20px; width:100%; max-width:700px; box-shadow:0 20px 60px rgba(0,0,0,.22); overflow:hidden; margin:auto; }
        .mm-header { background:linear-gradient(135deg,#1e3a8a 0%,#3b82f6 100%); padding:22px 28px 18px; position:relative; }
        .mm-header h3 { color:#fff; font-size:18px; font-weight:800; margin:0; letter-spacing:.2px; }
        .mm-header p { color:#bfdbfe; font-size:12px; margin:4px 0 0; }
        .mm-kapat { position:absolute; top:16px; right:18px; background:rgba(255,255,255,.15); border:none; border-radius:50%; width:32px; height:32px; color:#fff; font-size:18px; cursor:pointer; display:flex; align-items:center; justify-content:center; transition:.15s; }
        .mm-kapat:hover { background:rgba(255,255,255,.3); }
        .mm-tab-bar { display:flex; border-bottom:1px solid #f1f5f9; padding:0 20px; background:#fafbff; overflow-x:auto; }
        .mm-tab { padding:11px 16px; border:none; background:none; cursor:pointer; font-size:12.5px; font-weight:600; color:#9ca3af; border-bottom:3px solid transparent; margin-bottom:-1px; white-space:nowrap; transition:.15s; display:flex; align-items:center; gap:5px; }
        .mm-tab:hover { color:#374151; }
        .mm-tab.aktif { color:#3b82f6; border-bottom-color:#3b82f6; background:none; }
        .mm-body { padding:22px 28px; max-height:58vh; overflow-y:auto; }
        .m-panel { display:none; } .m-panel.aktif { display:block; }
        .mm-footer { padding:14px 28px 20px; border-top:1px solid #f1f5f9; display:flex; justify-content:flex-end; gap:10px; background:#fafbff; }
        /* Form elements */
        .mfg { margin-bottom:14px; }
        .mfg label { display:flex; align-items:center; gap:5px; font-size:11.5px; font-weight:700; color:#374151; margin-bottom:5px; text-transform:uppercase; letter-spacing:.4px; }
        .mfg label span.req { color:#ef4444; }
        .mfg input, .mfg select, .mfg textarea { width:100%; padding:10px 13px; border:1.5px solid #e2e8f0; border-radius:10px; font-size:13px; outline:none; box-sizing:border-box; background:#fff; transition:all .15s; }
        .mfg input:focus, .mfg select:focus, .mfg textarea:focus { border-color:#3b82f6; box-shadow:0 0 0 3px rgba(59,130,246,.1); }
        .mfg input.ust { text-transform:uppercase; }
        .mfg2 { display:grid; grid-template-columns:1fr 1fr; gap:14px; }
        .mfg3 { display:grid; grid-template-columns:1fr 1fr 1fr; gap:12px; }
        .mfg-full { grid-column:1/-1; }
        /* Tip butonları */
        .tip-grup { display:flex; gap:8px; }
        .tip-btn { flex:1; padding:9px 12px; border:2px solid #e2e8f0; border-radius:10px; background:#fff; cursor:pointer; font-size:13px; font-weight:600; color:#6b7280; transition:.15s; text-align:center; }
        .tip-btn.aktif { border-color:#3b82f6; background:#eff6ff; color:#1d4ed8; }
        /* Çoklu satır */
        .cs { display:flex; align-items:center; gap:8px; margin-bottom:8px; background:#f8faff; border-radius:10px; padding:8px 10px; border:1px solid #e8edf8; }
        .cs input, .cs select { flex:1; padding:7px 10px; border:1.5px solid #e2e8f0; border-radius:8px; font-size:13px; background:#fff; outline:none; min-width:0; }
        .cs input:focus, .cs select:focus { border-color:#3b82f6; box-shadow:0 0 0 2px rgba(59,130,246,.1); }
        .cs .cs-del { background:#fee2e2; color:#dc2626; border:none; border-radius:8px; padding:7px 11px; cursor:pointer; font-size:12px; font-weight:700; flex-shrink:0; }
        .btn-satir-ekle { display:flex; align-items:center; justify-content:center; gap:6px; width:100%; padding:9px; background:#f0f9ff; color:#0369a1; border:1.5px dashed #7dd3fc; border-radius:10px; cursor:pointer; font-size:13px; font-weight:600; transition:.15s; margin-top:4px; }
        .btn-satir-ekle:hover { background:#e0f2fe; }
        /* Etiket seçici */
        .etk-wrap { display:flex; flex-wrap:wrap; gap:8px; padding:14px; background:#f8faff; border-radius:12px; border:1.5px solid #e2e8f0; min-height:52px; }
        .etk-chip { padding:5px 13px; border-radius:99px; font-size:12px; font-weight:700; cursor:pointer; border:2px solid transparent; transition:all .15s; opacity:.55; transform:scale(.97); }
        .etk-chip:hover { opacity:.85; transform:scale(1); }
        .etk-chip.secili { opacity:1; border-color:currentColor !important; box-shadow:0 2px 8px rgba(0,0,0,.12); transform:scale(1); }
        /* Adres kartı */
        .adr-kart { background:#f8faff; border:1.5px solid #e0e7ff; border-radius:12px; padding:14px; margin-bottom:12px; }
        .adr-kart-header { display:flex; align-items:center; justify-content:space-between; margin-bottom:12px; }
        /* Bilgi kutusu */
        .info-box { background:#f0f9ff; border:1px solid #bae6fd; border-radius:10px; padding:12px 15px; font-size:12px; color:#0369a1; margin-bottom:14px; line-height:1.5; }
    </style>
</head>
<body>
<?php require_once 'menu.php'; ?>

<div class="sayfa-wrap">
    <h1>📄 Yeni Fatura Oluştur</h1>

    <?php if ($errors): ?>
        <div class="alert alert-error">
            <?php foreach ($errors as $e): ?><div>⚠️ <?= htmlspecialchars($e) ?></div><?php endforeach; ?>
        </div>
    <?php endif; ?>

    <form method="POST" id="faturaForm">
        <input type="hidden" name="kaydet" value="1">
        <input type="hidden" name="from_tutanak" value="<?= htmlspecialchars($_GET['from_tutanak'] ?? '') ?>">
        <input type="hidden" name="tutanak_id_ref" value="<?= (int)($_GET['tutanak_id'] ?? 0) ?>">

        <!-- FATURA BİLGİLERİ -->
        <div class="kart">
            <h3>📋 Fatura Bilgileri</h3>
            <div class="form-grid">
                <div class="form-grup">
                    <label>Fatura No *</label>
                    <input type="text" name="fatura_no" value="<?= htmlspecialchars($_POST['fatura_no'] ?? $oneriliFaturaNo) ?>" required style="font-family:monospace;font-weight:700;">
                </div>
                <div class="form-grup">
                    <label>Fatura Tarihi *</label>
                    <input type="date" name="tarih" value="<?= htmlspecialchars($_POST['tarih'] ?? date('Y-m-d')) ?>" required>
                </div>
                <div class="form-grup">
                    <label>Vade Tarihi</label>
                    <input type="date" name="vade_tarihi" value="<?= htmlspecialchars($_POST['vade_tarihi'] ?? date('Y-m-d', strtotime('+30 days'))) ?>">
                </div>
                <div class="form-grup">
                    <label>👤 Personel (Satış Yetkilisi)</label>
                    <select name="personel_id" id="personelSecFatura" style="width:100%;padding:8px 12px;border:1px solid #dde3f0;border-radius:8px;font-size:13px;">
                        <option value="">— Personel Seçin —</option>
                    </select>
                </div>
            </div>
        </div>

        <!-- MÜŞTERİ -->
        <div class="kart">
            <h3>
                <span>👤 Müşteri Seçimi</span>
                <button type="button" class="btn-link" onclick="musteriModalAc()">➕ Yeni Müşteri Ekle</button>
            </h3>
            <input type="hidden" name="musteri_id" id="musteriId" value="<?= (int)($_POST['musteri_id'] ?? 0) ?>">
            <div class="form-grup ac-wrap">
                <label>Müşteri Adı / Ünvan *</label>
                <input type="text" id="musteriAraInput" placeholder="Müşteri adı, vergi no veya telefon ile ara..." autocomplete="off">
                <div class="ac-dropdown" id="musteriDropdown"></div>
            </div>
            <div class="musteri-info" id="musteriInfo"></div>
        </div>

        <!-- KALEMLER -->
        <div class="kart">
            <h3>
                <span>📦 Fatura Kalemleri</span>
                <button type="button" class="btn-link" onclick="urunSeciciAc()">🔍 Üründen Seç</button>
            </h3>
            <div style="overflow-x:auto;">
                <table class="kalem-tablo">
                    <thead>
                        <tr>
                            <th style="width:25%">Ürün / Hizmet Adı *</th>
                            <th style="width:10%">Ürün Kodu</th>
                            <th style="width:12%">Seri No</th>
                            <th style="width:7%">Miktar</th>
                            <th style="width:12%">Birim Fiyat (₺)</th>
                            <th style="width:7%">KDV %</th>
                            <th class="text-right" style="width:10%">KDV Tutarı</th>
                            <th class="text-right" style="width:11%">Satır Toplamı</th>
                            <th style="width:4%"></th>
                        </tr>
                    </thead>
                    <tbody id="kalemBody"></tbody>
                    <tfoot>
                        <tr>
                            <td colspan="6" class="text-right" style="font-size:13px;padding-right:8px;">Ara Toplam (Matrah):</td>
                            <td class="text-right" style="color:#f59e0b;font-weight:700;" id="totKdv">0,00 ₺</td>
                            <td class="text-right" style="color:#10b981;font-weight:700;" id="totMatrah">0,00 ₺</td>
                            <td></td>
                        </tr>
                        <tr style="background:#f8faff;">
                            <td colspan="7" class="text-right" style="font-weight:800;font-size:15px;padding-right:8px;">GENEL TOPLAM (KDV Dahil):</td>
                            <td class="text-right" id="totGenel" style="font-weight:800;font-size:16px;color:#1e40af;">0,00 ₺</td>
                            <td></td>
                        </tr>
                    </tfoot>
                </table>
            </div>
            <button type="button" onclick="kalemEkle()" style="margin-top:14px;padding:8px 18px;border-radius:8px;font-size:13px;cursor:pointer;border:1px solid #dde3f0;background:#f9fafb;font-weight:600;">+ Boş Satır Ekle</button>
        </div>

        <!-- NOTLAR / AÇIKLAMA -->
        <div class="kart">
            <h3>📝 Fatura Açıklaması</h3>
            <div class="form-grup">
                <textarea name="notlar" rows="3" placeholder="Fatura üzerinde görünecek açıklama veya notlar..."><?= htmlspecialchars($_POST['notlar'] ?? '') ?></textarea>
            </div>
        </div>

        <div style="display:flex;gap:12px;flex-wrap:wrap;align-items:center;">
            <button type="submit" name="kaydet" class="btn btn-primary">💾 Fatura Kaydet</button>
            <button type="submit" name="gonder_efatura" class="btn btn-success">📤 Kaydet &amp; e-Fatura Gönder</button>
            <a href="fatura_liste.php" style="padding:10px 20px;border-radius:8px;font-size:14px;font-weight:600;color:#374151;border:1px solid #dde3f0;background:#fff;text-decoration:none;">← Geri</a>
        </div>
    </form>
</div>

<!-- ── YENİ MÜŞTERİ MODAL — Modern ──────────────────────────── -->
<div class="modal-overlay" id="musteriModal" style="align-items:center;padding:16px;overflow-y:auto;">
  <div class="mm-kart">
    <!-- Header -->
    <div class="mm-header">
      <h3>➕ Yeni Müşteri</h3>
      <p>Müşteri bilgilerini doldurun, kaydedin ve otomatik seçilsin</p>
      <button class="mm-kapat" onclick="document.getElementById('musteriModal').classList.remove('aktif')">×</button>
    </div>
    <!-- Tabs -->
    <div class="mm-tab-bar">
      <button class="mm-tab aktif" onclick="mTabGec('genel',this)">📋 Genel Bilgiler</button>
      <button class="mm-tab" onclick="mTabGec('telefonlar',this)">📞 Telefonlar</button>
      <button class="mm-tab" onclick="mTabGec('adresler',this)">📍 Adresler</button>
      <button class="mm-tab" onclick="mTabGec('etiketler',this)">🏷️ Etiketler</button>
      <button class="mm-tab" onclick="mTabGec('bakiye',this)">💰 Bakiye</button>
    </div>
    <!-- Body -->
    <div class="mm-body">

      <!-- TAB: GENEL -->
      <div class="m-panel aktif" id="mpGenel">
        <div class="mfg mfg-full">
          <label>Ad Soyad / Ünvan <span class="req">*</span></label>
          <input type="text" id="mAd" class="ust" placeholder="AHMET YILMAZ veya ABC TİC. A.Ş.">
        </div>
        <div style="margin-bottom:14px;">
          <label style="display:block;font-size:11.5px;font-weight:700;color:#374151;margin-bottom:6px;text-transform:uppercase;letter-spacing:.4px;">Müşteri Tipi</label>
          <div class="tip-grup">
            <button type="button" class="tip-btn aktif" id="tipBireysel" onclick="mTipSec('bireysel')">👤 Bireysel</button>
            <button type="button" class="tip-btn" id="tipKurumsal" onclick="mTipSec('kurumsal')">🏢 Kurumsal</button>
          </div>
          <input type="hidden" id="mTip" value="bireysel">
        </div>
        <div class="mfg2">
          <div class="mfg">
            <label>E-posta</label>
            <input type="email" id="mEmail" placeholder="ornek@email.com">
          </div>
          <div class="mfg">
            <label>Vergi No / TC Kimlik</label>
            <input type="text" id="mVergiNo" placeholder="1234567890" maxlength="11">
          </div>
          <div class="mfg">
            <label>İl (Fatura İli)</label>
            <select id="mIl" onchange="mIlDegisti()">
              <option value="">-- İl Seçin --</option>
              <?php foreach ($iller as $il): ?>
              <option value="<?= htmlspecialchars($il) ?>"><?= htmlspecialchars($il) ?></option>
              <?php endforeach; ?>
            </select>
          </div>
          <div class="mfg">
            <label>Vergi Dairesi</label>
            <select id="mVergiDairesi"><option value="">-- Önce İl Seçin --</option></select>
          </div>
        </div>
        <div class="mfg">
          <label>Notlar</label>
          <textarea id="mNotlar" rows="2" placeholder="Müşteri hakkında notlar..."></textarea>
        </div>
      </div>

      <!-- TAB: TELEFONLAR -->
      <div class="m-panel" id="mpTelefonlar">
        <div class="info-box">📞 Birden fazla telefon numarası ekleyebilirsiniz. İlk numara ana numara olarak atanır.</div>
        <div id="mTelefonListesi"></div>
        <button type="button" class="btn-satir-ekle" onclick="mTelefonEkle()">+ Telefon Ekle</button>
      </div>

      <!-- TAB: ADRESLER -->
      <div class="m-panel" id="mpAdresler">
        <div class="info-box">📍 Şehir ve ilçe bilgisini açılır listeden seçin.</div>
        <div id="mAdresListesi"></div>
        <button type="button" class="btn-satir-ekle" onclick="mAdresEkle()">+ Adres Ekle</button>
      </div>

      <!-- TAB: ETİKETLER -->
      <div class="m-panel" id="mpEtiketler">
        <div class="info-box">🏷️ Müşteriyi sınıflandırmak için etiketlere tıklayın.</div>
        <div class="etk-wrap" id="mEtiketSecici">
          <?php foreach ($etiketlerTum as $et): ?>
          <span class="etk-chip" data-eid="<?= $et['id'] ?>"
            style="background:<?= htmlspecialchars($et['renk']) ?>22;color:<?= htmlspecialchars($et['renk']) ?>;"
            onclick="mEtiketToggle(<?= $et['id'] ?>)"><?= htmlspecialchars($et['ad']) ?></span>
          <?php endforeach; ?>
          <?php if (empty($etiketlerTum)): ?>
          <span style="color:#9ca3af;font-size:13px;padding:6px;">Henüz etiket yok — Müşteri Listesi bölümünden ekleyin.</span>
          <?php endif; ?>
        </div>
      </div>

      <!-- TAB: BAKİYE -->
      <div class="m-panel" id="mpBakiye">
        <div class="info-box"><strong>ℹ️ Başlangıç Bakiyesi:</strong> Sisteme eklendiği andaki borç/alacak. Pozitif = müşteri borçlu, Negatif = müşteri alacaklı.</div>
        <div class="mfg2">
          <div class="mfg">
            <label>Başlangıç Bakiyesi (₺)</label>
            <input type="number" id="mBakiye" placeholder="0.00" step="0.01" value="0">
          </div>
          <div class="mfg">
            <label>Bakiye Tarihi</label>
            <input type="date" id="mBakiyeTarih" value="<?= date('Y-m-d') ?>">
          </div>
        </div>
      </div>
    </div>

    <!-- Footer -->
    <div class="mm-footer">
      <button type="button" style="padding:10px 22px;border-radius:10px;border:1.5px solid #e2e8f0;background:#f8fafc;cursor:pointer;font-weight:600;font-size:13px;color:#374151;" onclick="document.getElementById('musteriModal').classList.remove('aktif')">İptal</button>
      <button type="button" onclick="musteriKaydet()" id="mKaydetBtn" style="padding:10px 26px;border-radius:10px;border:none;background:linear-gradient(135deg,#1e3a8a,#3b82f6);color:#fff;cursor:pointer;font-weight:700;font-size:13px;box-shadow:0 3px 12px rgba(59,130,246,.35);">💾 Kaydet ve Seç</button>
    </div>
  </div>
</div>

<!-- VD + İl/İlçe verisi JS için -->
<script>
const mVdData = <?= json_encode($vdTum, JSON_UNESCAPED_UNICODE) ?>;
const TR_ILCELER = {"Adana":["Aladağ","Ceyhan","Çukurova","Feke","İmamoğlu","Karaisalı","Karataş","Kozan","Pozantı","Saimbeyli","Sarıçam","Seyhan","Tufanbeyli","Yumurtalık","Yüreğir"],"Adıyaman":["Adıyaman Merkez","Besni","Çelikhan","Gerger","Gölbaşı","Kahta","Samsat","Sincik","Tut"],"Afyonkarahisar":["Afyon Merkez","Başmakçı","Bayat","Bolvadin","Çay","Çobanlar","Dazkırı","Dinar","Emirdağ","Evciler","Hocalar","İhsaniye","İscehisar","Kızılören","Sandıklı","Sinanpaşa","Sultandağı","Şuhut"],"Ağrı":["Ağrı Merkez","Diyadin","Doğubayazıt","Eleşkirt","Hamur","Patnos","Taşlıçay","Tutak"],"Aksaray":["Ağaçören","Aksaray Merkez","Eskil","Gülağaç","Güzelyurt","Ortaköy","Sarıyahşi"],"Amasya":["Amasya Merkez","Göynücek","Gümüşhacıköy","Hamamözü","Merzifon","Suluova","Taşova"],"Ankara":["Altındağ","Ayaş","Bala","Beypazarı","Çamlıdere","Çankaya","Çubuk","Elmadağ","Etimesgut","Evren","Gölbaşı","Güdül","Haymana","Kalecik","Kahramankazan","Keçiören","Kızılcahamam","Mamak","Nallıhan","Polatlı","Pursaklar","Sincan","Şereflikoçhisar","Yenimahalle"],"Antalya":["Akseki","Aksu","Alanya","Demre","Döşemealtı","Elmalı","Finike","Gazipaşa","Gündoğmuş","İbradı","Kaş","Kemer","Kepez","Konyaaltı","Korkuteli","Kumluca","Manavgat","Muratpaşa","Serik"],"Ardahan":["Ardahan Merkez","Çıldır","Damal","Göle","Hanak","Posof"],"Artvin":["Ardanuç","Arhavi","Artvin Merkez","Borçka","Hopa","Kemalpaşa","Murgul","Şavşat","Yusufeli"],"Aydın":["Bozdoğan","Buharkent","Çine","Didim","Efeler","Germencik","İncirliova","Karacasu","Karpuzlu","Koçarlı","Köşk","Kuşadası","Kuyucak","Nazilli","Söke","Sultanhisar","Yenipazar"],"Balıkesir":["Altıeylül","Ayvalık","Balya","Bandırma","Bigadiç","Burhaniye","Dursunbey","Edremit","Erdek","Gömeç","Gönen","Havran","İvrindi","Karesi","Kepsut","Manyas","Marmara","Savaştepe","Sındırgı","Susurluk"],"Bartın":["Arit","Bartın Merkez","Kurucaşile","Ulus"],"Batman":["Batman Merkez","Beşiri","Gercüş","Hasankeyf","Kozluk","Sason"],"Bayburt":["Aydıntepe","Bayburt Merkez","Demirözü"],"Bilecik":["Bozüyük","Gölpazarı","İnhisar","Merkez","Osmaneli","Pazaryeri","Söğüt","Yenipazar"],"Bingöl":["Adaklı","Bingöl Merkez","Genç","Karlıova","Kiğı","Solhan","Yayladere","Yedisu"],"Bitlis":["Adilcevaz","Ahlat","Bitlis Merkez","Güroymak","Hizan","Mutki","Tatvan"],"Bolu":["Bolu Merkez","Dörtdivan","Gerede","Göynük","Kıbrıscık","Mengen","Mudurnu","Seben","Yeniçağa"],"Burdur":["Ağlasun","Altınyayla","Burdur Merkez","Bucak","Çavdır","Çeltikçi","Gölhisar","Karamanlı","Kemer","Tefenni","Yeşilova"],"Bursa":["Büyükorhan","Gemlik","Gürsu","Harmancık","İnegöl","İznik","Karacabey","Keles","Kestel","Mudanya","Mustafakemalpaşa","Nilüfer","Orhaneli","Orhangazi","Osmangazi","Yenişehir","Yıldırım"],"Çanakkale":["Ayvacık","Bayramiç","Biga","Bozcaada","Çan","Çanakkale Merkez","Eceabat","Ezine","Gelibolu","Gökçeada","Lapseki","Yenice"],"Çankırı":["Atkaracalar","Bayramören","Çankırı Merkez","Çerkeş","Eldivan","Ilgaz","Kızılırmak","Korgun","Kurşunlu","Orta","Şabanözü","Yapraklı"],"Çorum":["Alaca","Bayat","Boğazkale","Dodurga","İskilip","Kargı","Laçin","Mecitözü","Merkez","Oğuzlar","Ortaköy","Osmancık","Sungurlu","Uğurludağ"],"Denizli":["Acıpayam","Babadağ","Baklan","Bekilli","Beyağaç","Bozkurt","Buldan","Çal","Çameli","Çardak","Çivril","Güney","Honaz","Kale","Merkezefendi","Pamukkale","Sarayköy","Serinhisar","Tavas"],"Diyarbakır":["Bağlar","Bismil","Çermik","Çınar","Çüngüş","Dicle","Eğil","Ergani","Hani","Hazro","Kayapınar","Kocaköy","Kulp","Lice","Silvan","Sur","Yenişehir"],"Düzce":["Akçakoca","Cumayeri","Çilimli","Düzce Merkez","Gölyaka","Gümüşova","Kaynaşlı","Yığılca"],"Edirne":["Edirne Merkez","Enez","Havsa","İpsala","Keşan","Lalapaşa","Meriç","Süloğlu","Uzunköprü"],"Elazığ":["Ağın","Alacakaya","Arıcak","Baskil","Elazığ Merkez","Karakoçan","Keban","Kovancılar","Maden","Palu","Sivrice"],"Erzincan":["Çayırlı","Erzincan Merkez","İliç","Kemah","Kemaliye","Otlukbeli","Refahiye","Tercan","Üzümlü"],"Erzurum":["Aşkale","Aziziye","Çat","Hınıs","Horasan","İspir","Karaçoban","Karayazı","Köprüköy","Narman","Oltu","Olur","Palandöken","Pasinler","Pazaryolu","Şenkaya","Tekman","Tortum","Uzundere","Yakutiye"],"Eskişehir":["Alpu","Beylikova","Çifteler","Günyüzü","Han","İnönü","Mahmudiye","Mihalgazi","Mihalıççık","Odunpazarı","Sarıcakaya","Seyitgazi","Sivrihisar","Tepebaşı"],"Gaziantep":["Araban","İslahiye","Karkamış","Nurdağı","Nizip","Oğuzeli","Şahinbey","Şehitkamil","Yavuzeli"],"Giresun":["Alucra","Bulancak","Çamoluk","Çanakçı","Dereli","Doğankent","Espiye","Eynesil","Giresun Merkez","Görele","Güce","Keşap","Piraziz","Şebinkarahisar","Tirebolu","Yağlıdere"],"Gümüşhane":["Gümüşhane Merkez","Kelkit","Köse","Kürtün","Şiran","Torul"],"Hakkari":["Çukurca","Derecik","Hakkari Merkez","Şemdinli","Yüksekova"],"Hatay":["Altınözü","Antakya","Arsuz","Belen","Defne","Dörtyol","Erzin","Hassa","İskenderun","Kırıkhan","Kumlu","Payas","Reyhanlı","Samandağ","Serinyol","Yayladağı"],"Iğdır":["Aralık","Iğdır Merkez","Karakoyunlu","Tuzluca"],"Isparta":["Aksu","Atabey","Eğirdir","Gelendost","Gönen","Isparta Merkez","Keçiborlu","Senirkent","Sütçüler","Şarkikaraağaç","Uluborlu","Yalvaç","Yenişarbademli"],"İstanbul":["Adalar","Arnavutköy","Ataşehir","Avcılar","Bağcılar","Bahçelievler","Bakırköy","Başakşehir","Bayrampaşa","Beşiktaş","Beykoz","Beylikdüzü","Beyoğlu","Büyükçekmece","Çatalca","Çekmeköy","Esenler","Esenyurt","Eyüpsultan","Fatih","Gaziosmanpaşa","Güngören","Kadıköy","Kağıthane","Kartal","Küçükçekmece","Maltepe","Pendik","Sancaktepe","Sarıyer","Silivri","Sultanbeyli","Sultangazi","Şile","Şişli","Tuzla","Ümraniye","Üsküdar","Zeytinburnu"],"İzmir":["Aliağa","Balçova","Bayındır","Bayraklı","Bergama","Beydağ","Bornova","Buca","Çeşme","Çiğli","Dikili","Foça","Gaziemir","Güzelbahçe","Karabağlar","Karaburun","Karşıyaka","Kemalpaşa","Kınık","Kiraz","Konak","Menderes","Menemen","Narlıdere","Ödemiş","Seferihisar","Selçuk","Tire","Torbalı","Urla"],"Kahramanmaraş":["Afşin","Andırın","Çağlayancerit","Dulkadiroğlu","Ekinözü","Elbistan","Göksun","Nurhak","Onikişubat","Pazarcık","Türkoğlu"],"Karabük":["Eflani","Eskipazar","Karabük Merkez","Ovacık","Safranbolu","Yenice"],"Karaman":["Ayrancı","Başyayla","Ermenek","Karaman Merkez","Kazımkarabekir","Sarıveliler"],"Kars":["Akyaka","Arpaçay","Digor","Kars Merkez","Kağızman","Sarıkamış","Selim","Susuz"],"Kastamonu":["Abana","Ağlı","Araç","Azdavay","Bozkurt","Cide","Çatalzeytin","Daday","Devrekani","Doğanyurt","Hanönü","İhsangazi","İnebolu","Kastamonu Merkez","Küre","Pınarbaşı","Seydiler","Şenpazar","Taşköprü","Tosya"],"Kayseri":["Akkışla","Bünyan","Develi","Felahiye","Hacılar","İncesu","Kocasinan","Melikgazi","Özvatan","Pınarbaşı","Sarıoğlan","Sarız","Talas","Tomarza","Yahyalı","Yeşilhisar"],"Kırıkkale":["Bahşılı","Balışeyh","Çelebi","Delice","Karakeçili","Keskin","Kırıkkale Merkez","Sulakyurt","Yahşihan"],"Kırklareli":["Babaeski","Demirköy","Kırklareli Merkez","Kofçaz","Lüleburgaz","Pehlivanköy","Pınarhisar","Vize"],"Kırşehir":["Akçakent","Akpınar","Boztepe","Çiçekdağı","Kaman","Kırşehir Merkez","Mucur"],"Kilis":["Elbeyli","Kilis Merkez","Musabeyli","Polateli"],"Kocaeli":["Başiskele","Çayırova","Darıca","Derince","Dilovası","Gebze","Gölcük","İzmit","Kandıra","Karamürsel","Kartepe","Körfez"],"Konya":["Ahırlı","Akören","Akşehir","Altınekin","Beyşehir","Bozkır","Cihanbeyli","Çeltik","Çumra","Derbent","Derebucak","Doğanhisar","Emirgazi","Ereğli","Güneysınır","Hadim","Halkapınar","Hüyük","Ilgın","Kadınhanı","Karapınar","Karatay","Kulu","Meram","Sarayönü","Selçuklu","Seydişehir","Taşkent","Tuzlukçu","Yalıhüyük","Yunak"],"Kütahya":["Altıntaş","Aslanapa","Çavdarhisar","Domaniç","Dumlupınar","Emet","Gediz","Hisarcık","Kütahya Merkez","Pazarlar","Simav","Şaphane","Tavşanlı"],"Malatya":["Akçadağ","Arapgir","Arguvan","Battalgazi","Darende","Doğanşehir","Doğanyol","Hekimhan","Kale","Kuluncak","Pütürge","Yazıhan","Yeşilyurt"],"Manisa":["Ahmetli","Akhisar","Alaşehir","Demirci","Gölmarmara","Gördes","Kırkağaç","Köprübaşı","Kula","Merkez","Salihli","Sarıgöl","Saruhanlı","Selendi","Soma","Turgutlu","Yunusemre"],"Mardin":["Artuklu","Dargeçit","Derik","Kızıltepe","Mazıdağı","Midyat","Nusaybin","Ömerli","Savur","Yeşilli"],"Mersin":["Akdeniz","Anamur","Aydıncık","Bozyazı","Çamlıyayla","Erdemli","Gülnar","Mezitli","Mut","Silifke","Tarsus","Toroslar","Yenişehir"],"Muğla":["Bodrum","Dalaman","Datça","Fethiye","Kavaklıdere","Köyceğiz","Marmaris","Menteşe","Milas","Ortaca","Seydikemer","Ula","Yatağan"],"Muş":["Bulanık","Hasköy","Korkut","Malazgirt","Muş Merkez","Varto"],"Nevşehir":["Acıgöl","Avanos","Derinkuyu","Gülşehir","Hacıbektaş","Kozaklı","Nevşehir Merkez","Ürgüp"],"Niğde":["Altunhisar","Bor","Çamardı","Çiftlik","Niğde Merkez","Ulukışla"],"Ordu":["Akkuş","Altınordu","Aybastı","Çamaş","Çatalpınar","Çaybaşı","Fatsa","Gölköy","Gülyalı","Gürgentepe","İkizce","Kabadüz","Kabataş","Korgan","Kumru","Mesudiye","Perşembe","Ulubey","Ünye"],"Osmaniye":["Bahçe","Düziçi","Hasanbeyli","Kadirli","Osmaniye Merkez","Sumbas","Toprakkale"],"Rize":["Ardeşen","Çamlıhemşin","Çayeli","Derepazarı","Fındıklı","Güneysu","Hemşin","İkizdere","İyidere","Kalkandere","Pazar","Rize Merkez"],"Sakarya":["Adapazarı","Akyazı","Arifiye","Erenler","Ferizli","Geyve","Hendek","Karapürçek","Karasu","Kaynarca","Kocaali","Mithatpaşa","Pamukova","Sapanca","Serdivan","Söğütlü","Taraklı"],"Samsun":["19 Mayıs","Alaçam","Asarcık","Atakum","Ayvacık","Bafra","Canik","Çarşamba","İlkadım","Kavak","Ladik","Salıpazarı","Tekkeköy","Terme","Vezirköprü","Yakakent"],"Siirt":["Baykan","Eruh","Kurtalan","Pervari","Siirt Merkez","Şirvan","Tillo"],"Sinop":["Ayancık","Boyabat","Dikmen","Durağan","Erfelek","Gerze","Saraydüzü","Sinop Merkez","Türkeli"],"Sivas":["Akıncılar","Altınyayla","Divriği","Doğanşar","Gemerek","Gölova","Gürun","Hafik","İmranlı","Kangal","Koyulhisar","Merkez","Suşehri","Şarkışla","Ulaş","Yıldızeli","Zara"],"Şanlıurfa":["Akçakale","Birecik","Bozova","Ceylanpınar","Eyyübiye","Halfeti","Haliliye","Harran","Hilvan","Karaköprü","Siverek","Suruç","Viranşehir"],"Şırnak":["Beytüşşebap","Cizre","Güçlükonak","İdil","Silopi","Şırnak Merkez","Uludere"],"Tekirdağ":["Çerkezköy","Çorlu","Ergene","Hayrabolu","Kapaklı","Malkara","Marmara Ereğlisi","Muratlı","Saray","Süleymanpaşa","Şarköy"],"Tokat":["Almus","Artova","Başçiftlik","Erbaa","Niksar","Pazar","Reşadiye","Sulusaray","Tokat Merkez","Turhal","Yeşilyurt","Zile"],"Trabzon":["Akçaabat","Araklı","Arsin","Beşikdüzü","Çarşıbaşı","Çaykara","Dernekpazarı","Düzköy","Hayrat","Köprübaşı","Maçka","Of","Ortahisar","Sürmene","Şalpazarı","Tonya","Vakfıkebir","Yomra"],"Tunceli":["Çemişgezek","Hozat","Mazgirt","Merkez","Nazımiye","Ovacık","Pertek","Pülümür"],"Uşak":["Banaz","Eşme","Karahallı","Merkez","Sivaslı","Ulubey"],"Van":["Bahçesaray","Başkale","Çaldıran","Çatak","Edremit","Erciş","Gevaş","Gürpınar","İpekyolu","Muradiye","Özalp","Saray","Tuşba"],"Yalova":["Altınova","Armutlu","Çınarcık","Çiftlikköy","Merkez","Termal"],"Yozgat":["Akdağmadeni","Aydıncık","Boğazlıyan","Çandır","Çayıralan","Çekerek","Kadışehri","Merkez","Saraykent","Sarıkaya","Şefaatli","Sorgun","Yenifakılı","Yerköy"],"Zonguldak":["Alaplı","Çaycuma","Devrek","Ereğli","Gökçebey","Kilimli","Kozlu","Merkez"]};
</script>

<!-- ── ÜRÜN SEÇİCİ MODAL ──────────────────────────────────────── -->
<div class="modal-overlay" id="urunModal">
    <div class="modal-kart" style="max-width:860px;">
        <h3>🔍 Ürün Seç</h3>
        <div style="margin-bottom:14px;">
            <input type="text" id="urunAraInput" placeholder="Ürün adı veya kodu ile ara..." style="width:100%;padding:9px 12px;border:1px solid #dde3f0;border-radius:8px;font-size:13px;box-sizing:border-box;" oninput="urunleriFiltrele()">
        </div>
        <div class="urun-grid" id="urunGrid">
            <div style="grid-column:1/-1;text-align:center;padding:30px;color:#9ca3af;">⏳ Yükleniyor...</div>
        </div>
        <div class="modal-footer">
            <button type="button" style="padding:9px 20px;border-radius:8px;border:1px solid #dde3f0;background:#f3f4f6;cursor:pointer;font-weight:600;" onclick="document.getElementById('urunModal').classList.remove('aktif')">Kapat</button>
        </div>
    </div>
</div>

<!-- ── SERİ NO SEÇİCİ MODAL ──────────────────────────────────── -->
<div class="modal-overlay" id="seriModal">
    <div class="modal-kart" style="max-width:500px;">
        <h3>🔢 Seri No Seç</h3>
        <p id="seriModalUrunAd" style="font-size:13px;color:#374151;margin-bottom:12px;"></p>
        <div id="seriListesi" style="max-height:320px;overflow-y:auto;border:1px solid #e5e7eb;border-radius:8px;"></div>
        <div class="modal-footer">
            <button type="button" style="padding:9px 20px;border-radius:8px;border:1px solid #dde3f0;background:#f3f4f6;cursor:pointer;font-weight:600;" onclick="document.getElementById('seriModal').classList.remove('aktif')">Kapat</button>
        </div>
    </div>
</div>

<script>
'use strict';

const para = v => parseFloat(v||0).toLocaleString('tr-TR',{minimumFractionDigits:2,maximumFractionDigits:2}) + ' ₺';
function esc(s) { return String(s??'').replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;').replace(/'/g,'&#39;'); }

// ── Ürün verisi (PHP'den JSON olarak) ─────────────────────────
let tumUrunler = [];
fetch('urun_kontrol.php', {method:'POST', headers:{'Content-Type':'application/x-www-form-urlencoded'}, body:'action=liste_json'})
.then(r=>r.json())
.then(v => { tumUrunler = v.urunler || []; })
.catch(()=>{});

// ── KALEM SATIRI ──────────────────────────────────────────────
let kalemSayac = 0;

function kalemEkle(urun) {
    kalemSayac++;
    const i = kalemSayac;
    const ad       = urun ? (urun.ad||'') : '';
    const kod      = urun ? (urun.urun_kodu||'') : '';
    const fiyat    = urun ? parseFloat(urun.satis_fiyati||0) : 0;
    const kdv      = urun ? parseFloat(urun.kdv_orani||20) : 20;
    const urunId   = urun ? (urun.id||0) : 0;
    const seriTakip = urun ? (parseInt(urun.seri_no_takip||0)===1) : false;
    const stok     = urun ? parseFloat(urun.stok_adeti||0) : null;

    const stokBadge = stok !== null
        ? `<span class="${stok>0?'stok-yesil':'stok-kirmizi'}" style="font-size:11px;">${stok>0?'Stok: '+stok:'Stok yok'}</span>`
        : '';

    const seriBtn = seriTakip
        ? `<span class="seri-badge" onclick="seriSeciciAc(${i},${urunId},'${esc(ad)}')" title="Seri no seç">🔢 Seç</span>`
        : '';

    const tr = document.createElement('tr');
    tr.id = 'kalem-' + i;
    tr.dataset.urunId = urunId;
    tr.dataset.seriTakip = seriTakip ? '1' : '0';
    tr.innerHTML = `
        <td>
            <div class="kalem-drop-wrap">
                <input type="text" name="kalem_aciklama[]" value="${esc(ad)}" placeholder="Ürün/Hizmet adı"
                    oninput="urunAraKalemde(this,${i});hesapla()" autocomplete="off"
                    onblur="setTimeout(()=>kalemDropKapat(${i}),200)">
                <div class="kalem-drop" id="kalemDrop${i}"></div>
            </div>
            <input type="hidden" name="kalem_urun_id[]" id="kalemUrunId${i}" value="${urunId}">
            ${stokBadge}
        </td>
        <td><input type="text" name="kalem_kod[]" value="${esc(kod)}" placeholder="KOD" style="font-family:monospace;font-size:12px;"></td>
        <td>
            <input type="hidden" name="kalem_seri_no[]" id="seriNoJson${i}" value="">
            <div id="seriInputContainer${i}"></div>
        </td>
        <td><input type="number" name="kalem_miktar[]" value="1" min="0.001" step="0.001" style="width:70px;" oninput="hesapla();updateSeriInputs(${i},this.value,${seriTakip?'true':'false'},${urunId})"></td>
        <td><input type="number" name="kalem_fiyat[]" value="${fiyat.toFixed(2)}" min="0" step="0.01" oninput="hesapla()"></td>
        <td>
            <select name="kalem_kdv[]" onchange="hesapla()" style="width:68px;">
                <option value="0" ${kdv==0?'selected':''}>%0</option>
                <option value="1" ${kdv==1?'selected':''}>%1</option>
                <option value="10" ${kdv==10?'selected':''}>%10</option>
                <option value="20" ${kdv==20?'selected':''}>%20</option>
                ${(kdv>0&&kdv!=1&&kdv!=10&&kdv!=20)?`<option value="${kdv}" selected>%${kdv}</option>`:''}
            </select>
        </td>
        <td class="text-right kdv-col">0,00 ₺</td>
        <td class="text-right top-col">0,00 ₺</td>
        <td><button type="button" class="btn-del" onclick="document.getElementById('kalem-${i}').remove();hesapla();">✕</button></td>
    `;
    document.getElementById('kalemBody').appendChild(tr);
    updateSeriInputs(i, 1, seriTakip, urunId);
    hesapla();
}

function hesapla() {
    let matrahTop=0, kdvTop=0;
    document.querySelectorAll('#kalemBody tr').forEach(tr => {
        const mik = parseFloat(tr.querySelector('[name="kalem_miktar[]"]')?.value||0);
        const fiy = parseFloat(tr.querySelector('[name="kalem_fiyat[]"]')?.value||0);
        const kdv = parseFloat(tr.querySelector('[name="kalem_kdv[]"]')?.value||0);
        const mat = mik * fiy;
        const kv  = mat * kdv / 100;
        matrahTop += mat; kdvTop += kv;
        tr.querySelector('.kdv-col').textContent = para(kv);
        tr.querySelector('.top-col').textContent = para(mat+kv);
    });
    document.getElementById('totKdv').textContent    = para(kdvTop);
    document.getElementById('totMatrah').textContent = para(matrahTop);
    document.getElementById('totGenel').textContent  = para(matrahTop+kdvTop);
}

// ── KALEM SATIRI ÜRÜN AUTOCOMPLETE ────────────────────────────
function urunAraKalemde(inp, i) {
    const q = inp.value.trim().toLowerCase();
    const drop = document.getElementById('kalemDrop' + i);
    if (!q || q.length < 1) { drop.style.display = 'none'; return; }
    const filtre = tumUrunler.filter(u =>
        (u.ad||'').toLowerCase().includes(q) ||
        (u.urun_kodu||'').toLowerCase().includes(q)
    ).slice(0, 10);
    if (!filtre.length) { drop.style.display = 'none'; return; }
    drop.innerHTML = filtre.map(u => {
        const stok = parseFloat(u.stok_adeti||0);
        const stokRenk = stok > 0 ? '#10b981' : '#ef4444';
        return `<div class="kalem-drop-item" onmousedown="kalemDropSec(${i},${JSON.stringify(u).replace(/"/g,'&quot;')})">
            <span class="kd-ad">${esc(u.ad)}</span>
            <span class="kd-detay">${esc(u.urun_kodu)} · ${para(u.satis_fiyati)} · <span style="color:${stokRenk}">Stok:${stok}</span></span>
        </div>`;
    }).join('');
    drop.style.display = 'block';
}

function kalemDropSec(i, urun) {
    kalemDropKapat(i);
    const tr = document.getElementById('kalem-' + i);
    if (!tr) return;
    tr.querySelector('[name="kalem_aciklama[]"]').value = urun.ad || '';
    tr.querySelector('[name="kalem_kod[]"]').value = urun.urun_kodu || '';
    tr.querySelector('[name="kalem_fiyat[]"]').value = parseFloat(urun.satis_fiyati||0).toFixed(2);
    const kdvSel = tr.querySelector('[name="kalem_kdv[]"]');
    const kdvVal = parseFloat(urun.kdv_orani||20);
    let found = false;
    for (let opt of kdvSel.options) { if (parseFloat(opt.value) === kdvVal) { kdvSel.value = opt.value; found = true; break; } }
    if (!found) { const o = new Option('%' + kdvVal, kdvVal, true, true); kdvSel.add(o); }
    document.getElementById('kalemUrunId' + i).value = urun.id || 0;
    tr.dataset.urunId = urun.id || 0;
    tr.dataset.seriTakip = parseInt(urun.seri_no_takip||0) === 1 ? '1' : '0';
    const stok = parseFloat(urun.stok_adeti||0);
    const existingBadge = tr.querySelector('.stok-yesil, .stok-kirmizi');
    if (existingBadge) existingBadge.remove();
    const badge = document.createElement('span');
    badge.className = stok > 0 ? 'stok-yesil' : 'stok-kirmizi';
    badge.style.fontSize = '11px';
    badge.textContent = stok > 0 ? 'Stok: ' + stok : 'Stok yok';
    tr.querySelector('.kalem-drop-wrap').after(badge);
    if (parseInt(urun.seri_no_takip||0) === 1) {
        const seriContainer = tr.querySelector('[name="kalem_seri_no[]"]').parentElement;
        if (!seriContainer.querySelector('.seri-badge')) {
            const btn = document.createElement('div');
            btn.style.marginTop = '3px';
            btn.innerHTML = `<span class="seri-badge" onclick="seriSeciciAc(${i},${urun.id},'${esc(urun.ad)}')">🔢 Seç</span>`;
            seriContainer.appendChild(btn);
        }
    }
    hesapla();
}

function kalemDropKapat(i) {
    const drop = document.getElementById('kalemDrop' + i);
    if (drop) drop.style.display = 'none';
}


function urunSeciciAc() {
    document.getElementById('urunAraInput').value = '';
    renderUrunGrid(tumUrunler);
    document.getElementById('urunModal').classList.add('aktif');
    setTimeout(()=>document.getElementById('urunAraInput').focus(), 100);
}

function urunleriFiltrele() {
    const q = document.getElementById('urunAraInput').value.toLowerCase();
    const filtre = q ? tumUrunler.filter(u =>
        (u.ad||'').toLowerCase().includes(q) ||
        (u.urun_kodu||'').toLowerCase().includes(q)
    ) : tumUrunler;
    renderUrunGrid(filtre);
}

function renderUrunGrid(liste) {
    const grid = document.getElementById('urunGrid');
    if (!liste.length) {
        grid.innerHTML = '<div style="grid-column:1/-1;text-align:center;padding:30px;color:#9ca3af;">📭 Ürün bulunamadı.</div>';
        return;
    }
    grid.innerHTML = liste.map(u => {
        const stok = parseFloat(u.stok_adeti||0);
        const stokRenk = stok > 0 ? '#10b981' : '#ef4444';
        const stokYazi = stok > 0 ? `Stok: ${stok}` : 'Stok yok';
        return `<div class="urun-kart" onclick="urunEkle(${JSON.stringify(u).replace(/"/g,'&quot;')})">
            <div class="urun-ad">${esc(u.ad)}</div>
            <div class="urun-kod">${esc(u.urun_kodu)}</div>
            <div class="urun-fiyat">${para(u.satis_fiyati)}</div>
            <div class="urun-stok" style="color:${stokRenk};">${stokYazi} ${parseInt(u.seri_no_takip)?'· 🔢 Seri No':''}  &nbsp; KDV: %${u.kdv_orani}</div>
        </div>`;
    }).join('');
}

function urunEkle(urun) {
    document.getElementById('urunModal').classList.remove('aktif');
    kalemEkle(urun);
}

// ── SERİ NO SEÇİCİ ────────────────────────────────────────────
let aktifKalemSatir = null;
let aktifKalemSub   = 0;

function updateSeriInputs(rowIdx, miktarVal, seriTakip, urunId) {
    const n = Math.max(1, Math.round(parseFloat(miktarVal) || 1));
    const container = document.getElementById('seriInputContainer' + rowIdx);
    if (!container) return;
    // Mevcut değerleri koru
    const existing = [];
    container.querySelectorAll('.seri-sub-input').forEach(inp => existing.push(inp.value));
    let html = '';
    for (let j = 0; j < n; j++) {
        const val = (existing[j] || '').replace(/"/g, '&quot;');
        const lbl = n > 1 ? `<span style="font-size:10px;color:#6b7280;display:block;margin-bottom:1px;">${j+1}. Seri No</span>` : '';
        const btn = seriTakip ? `<button type="button" onclick="seriSeciciAc(${rowIdx},${j},${urunId},'Seri No Seç')" style="padding:2px 6px;border:1px solid #dde3f0;border-radius:5px;background:#f0f4ff;cursor:pointer;font-size:11px;white-space:nowrap;" title="Listeden seç">🔢 Seç</button>` : '';
        html += `<div style="margin-bottom:${j < n-1 ? '5px' : '0'};">
            ${lbl}
            <div style="display:flex;gap:4px;align-items:center;">
                <input type="text" class="seri-sub-input" value="${val}" placeholder="Seri no..."
                    style="flex:1;font-size:12px;font-family:monospace;padding:5px 8px;border:1px solid #dde3f0;border-radius:6px;outline:none;"
                    autocomplete="off"
                    data-row="${rowIdx}" data-sub="${j}"
                    oninput="clearTimeout(seriTimerMap['${rowIdx}_${j}']); seriTimerMap['${rowIdx}_${j}']=setTimeout(()=>seriSubAra(${rowIdx},${j},this.value),400); syncSeriJson(${rowIdx})"
                    onkeydown="if(event.key==='Enter'){event.preventDefault();seriSubAra(${rowIdx},${j},this.value);}">
                ${btn}
            </div>
            <div id="seriDurum${rowIdx}_${j}" style="font-size:11px;margin-top:2px;"></div>
        </div>`;
    }
    container.innerHTML = html;
    syncSeriJson(rowIdx);
}

function syncSeriJson(rowIdx) {
    const container = document.getElementById('seriInputContainer' + rowIdx);
    const hidden    = document.getElementById('seriNoJson' + rowIdx);
    if (!container || !hidden) return;
    const vals = [];
    container.querySelectorAll('.seri-sub-input').forEach(inp => vals.push(inp.value.trim()));
    hidden.value = JSON.stringify(vals);
}

function seriSeciciAc(rowIdx, subIdx, urunId, urunAd) {
    aktifKalemSatir = rowIdx;
    aktifKalemSub   = subIdx;
    document.getElementById('seriModalUrunAd').textContent = urunAd;
    document.getElementById('seriListesi').innerHTML = '<div style="padding:20px;text-align:center;color:#9ca3af;">⏳ Yükleniyor...</div>';
    document.getElementById('seriModal').classList.add('aktif');

    fetch('urun_kontrol.php', {
        method:'POST', headers:{'Content-Type':'application/x-www-form-urlencoded'},
        body:'action=seri_listele&urun_id=' + urunId + '&durum=stokta'
    })
    .then(r=>r.json())
    .then(v => {
        const liste = v.seriler || [];
        if (!liste.length) {
            document.getElementById('seriListesi').innerHTML = '<div style="padding:20px;text-align:center;color:#9ca3af;">📭 Stokta seri numarası yok.</div>';
            return;
        }
        document.getElementById('seriListesi').innerHTML = liste.map(s => `
            <div onclick="seriSec('${esc(s.seri_no)}')" style="padding:10px 14px;cursor:pointer;border-bottom:1px solid #f3f4f6;font-size:13px;font-family:monospace;display:flex;align-items:center;gap:8px;">
                <span style="color:#3b82f6;font-size:16px;">○</span>
                <span>${esc(s.seri_no)}</span>
            </div>`).join('');
    });
}

function seriSec(seriNo) {
    if (aktifKalemSatir !== null) {
        const container = document.getElementById('seriInputContainer' + aktifKalemSatir);
        if (container) {
            const inputs = container.querySelectorAll('.seri-sub-input');
            if (inputs[aktifKalemSub]) inputs[aktifKalemSub].value = seriNo;
            syncSeriJson(aktifKalemSatir);
        }
    }
    document.getElementById('seriModal').classList.remove('aktif');
}

const seriTimerMap = {};
const rezerveSeriSet = new Set(); // Bu oturumda rezerve edilen seri nolar

// Sayfa kapanınca rezervasyonları iptal et
window.addEventListener('beforeunload', () => {
    if (rezerveSeriSet.size === 0) return;
    const data = new URLSearchParams({action:'seri_rezerve_iptal', seri_liste: JSON.stringify([...rezerveSeriSet])});
    navigator.sendBeacon('urun_kontrol.php', data);
});

function isDuplicateSeri(seriNo, skipRowIdx, skipSubIdx) {
    let found = false;
    document.querySelectorAll('.seri-sub-input').forEach(inp => {
        if (parseInt(inp.dataset.row) === skipRowIdx && parseInt(inp.dataset.sub) === skipSubIdx) return;
        if (inp.value.trim().toLowerCase() === seriNo.toLowerCase()) found = true;
    });
    return found;
}

async function seriSubAra(rowIdx, subIdx, seriNo) {
    const durumId = `seriDurum${rowIdx}_${subIdx}`;
    const durum = document.getElementById(durumId);
    seriNo = (seriNo || '').trim();
    if (!seriNo || seriNo.length < 3) { if (durum) durum.textContent = ''; return; }

    // Aynı faturada duplicate kontrolü
    if (isDuplicateSeri(seriNo, rowIdx, subIdx)) {
        if (durum) durum.innerHTML = '<span style="color:#dc2626;">⛔ Bu seri no bu faturada zaten girilmiş!</span>';
        return;
    }

    if (durum) durum.innerHTML = '<span style="color:#6b7280;">⏳ Aranıyor...</span>';
    const res = await fetch('urun_kontrol.php', {
        method: 'POST',
        headers: {'Content-Type': 'application/x-www-form-urlencoded'},
        body: new URLSearchParams({action: 'seri_ara', seri_no: seriNo}).toString()
    }).then(r => r.json()).catch(() => null);
    if (!res || !res.basari) {
        if (durum) durum.innerHTML = '<span style="color:#ef4444;">❌ ' + (res?.mesaj || 'Bulunamadı') + '</span>';
        return;
    }
    const s = res.seri;
    if (s.durum === 'satildi') {
        if (durum) durum.innerHTML = '<span style="color:#f59e0b;">⚠️ Bu seri no zaten satılmış! Fatura ID: ' + (s.fatura_id || '?') + '</span>';
        return;
    }
    // Sadece ilk sub-input (subIdx=0) ürün bilgilerini doldursun
    if (subIdx === 0) {
        const tr = document.getElementById('kalem-' + rowIdx);
        if (tr) {
            tr.querySelector('input[name="kalem_aciklama[]"]').value = s.urun_adi || '';
            tr.querySelector('input[name="kalem_kod[]"]').value = s.urun_kodu || '';
            tr.querySelector('input[name="kalem_fiyat[]"]').value = parseFloat(s.satis_fiyati || 0).toFixed(2);
            document.getElementById('kalemUrunId' + rowIdx).value = s.urun_id || 0;
            tr.dataset.urunId = s.urun_id || 0;
            const kdvSel = tr.querySelector('select[name="kalem_kdv[]"]');
            const kdvVal = parseFloat(s.kdv_orani || 20);
            let found = false;
            for (let opt of kdvSel.options) { if (parseFloat(opt.value) === kdvVal) { kdvSel.value = opt.value; found = true; break; } }
            if (!found) { const o = new Option('%' + kdvVal, kdvVal, true, true); kdvSel.add(o); }
            hesapla();
        }
    }
    if (durum) durum.innerHTML = '<span style="color:#10b981;">✅ ' + esc(s.urun_adi) + ' <span style="color:#f59e0b;font-size:11px;">⏳ Rezerve edildi (15 dk)</span></span>';
    syncSeriJson(rowIdx);
    // Seri noyu rezerve et
    rezerveSeriSet.add(seriNo);
    fetch('urun_kontrol.php', {
        method: 'POST',
        headers: {'Content-Type': 'application/x-www-form-urlencoded'},
        body: new URLSearchParams({action: 'seri_rezerve', urun_id: s.urun_id, seri_no: seriNo}).toString()
    }).then(r => r.json()).then(v => {
        if (!v.basari && durum) {
            durum.innerHTML = '<span style="color:#dc2626;">⛔ ' + esc(v.mesaj) + '</span>';
            // Rezerve başarısız: alanı temizle
            const container = document.getElementById('seriInputContainer' + rowIdx);
            if (container) { const inputs = container.querySelectorAll('.seri-sub-input'); if(inputs[subIdx]) inputs[subIdx].value = ''; }
            syncSeriJson(rowIdx);
            rezerveSeriSet.delete(seriNo);
        }
    });
}

// ── MÜŞTERİ AUTOCOMPLETE ─────────────────────────────────────
let acTimer = null;

document.getElementById('musteriAraInput').addEventListener('input', function() {
    clearTimeout(acTimer);
    const q = this.value.trim();
    const dd = document.getElementById('musteriDropdown');
    if (q.length < 2) { dd.style.display='none'; return; }
    acTimer = setTimeout(() => {
        fetch('musteri_kontrol.php', {
            method:'POST', headers:{'Content-Type':'application/x-www-form-urlencoded'},
            body:'action=liste&ad_soyad=' + encodeURIComponent(q) + '&limit=8'
        })
        .then(r=>r.json())
        .then(v => {
            const musteriler = v.musteriler || [];
            if (!musteriler.length) { dd.style.display='none'; return; }
            dd.innerHTML = musteriler.map(m =>
                `<div class="ac-item" onclick="musteriSec(${m.id},'${esc(m.ad_soyad)}','${esc(m.vergi_no||'')}','${esc(m.telefon||'')}','${esc(m.email||'')}')">
                    <strong>${esc(m.ad_soyad)}</strong>
                    <small>${m.vergi_no?'VKN: '+esc(m.vergi_no):''}${m.telefon?' · '+esc(m.telefon):''}</small>
                </div>`
            ).join('');
            dd.style.display = 'block';
        });
    }, 300);
});

function musteriSec(id, ad, vkn, tel, email) {
    document.getElementById('musteriId').value = id;
    document.getElementById('musteriAraInput').value = ad;
    document.getElementById('musteriDropdown').style.display = 'none';
    const info = document.getElementById('musteriInfo');
    info.style.display = 'block';
    info.innerHTML = '<strong>' + esc(ad) + '</strong>'
        + (vkn ? ' &nbsp;·&nbsp; VKN: ' + esc(vkn) : '')
        + (tel  ? ' &nbsp;·&nbsp; ' + esc(tel)  : '')
        + (email? ' &nbsp;·&nbsp; ' + esc(email) : '');
}

document.addEventListener('click', e => {
    if (!e.target.closest('.ac-wrap'))
        document.getElementById('musteriDropdown').style.display = 'none';
});

// ── YENİ MÜŞTERİ MODAL ───────────────────────────────────────
let mTelSayac = 0, mAdrSayac = 0;
const mSeciliEtiketler = new Set();

function musteriModalAc() {
    // Form sıfırla
    document.getElementById('mAd').value = '';
    document.getElementById('mEmail').value = '';
    document.getElementById('mVergiNo').value = '';
    document.getElementById('mTip').value = 'bireysel';
    document.getElementById('tipBireysel').classList.add('aktif');
    document.getElementById('tipKurumsal').classList.remove('aktif');
    document.getElementById('mIl').value = '';
    document.getElementById('mVergiDairesi').innerHTML = '<option value="">-- Önce İl Seçin --</option>';
    document.getElementById('mNotlar').value = '';
    document.getElementById('mBakiye').value = '0';
    document.getElementById('mBakiyeTarih').value = new Date().toISOString().split('T')[0];
    document.getElementById('mTelefonListesi').innerHTML = '';
    mTelSayac = 0;
    mTelefonEkle();
    document.getElementById('mAdresListesi').innerHTML = '';
    mAdrSayac = 0;
    mAdresEkle();
    mSeciliEtiketler.clear();
    document.querySelectorAll('#mEtiketSecici .etk-chip').forEach(c => c.classList.remove('secili'));
    mTabGec('genel', document.querySelector('.m-tab.aktif') || document.querySelector('.m-tab'));
    document.getElementById('musteriModal').classList.add('aktif');
    setTimeout(() => document.getElementById('mAd').focus(), 100);
}

function mTabGec(id, btn) {
    document.querySelectorAll('.m-panel').forEach(p => p.classList.remove('aktif'));
    document.querySelectorAll('.m-tab').forEach(b => b.classList.remove('aktif'));
    document.getElementById('mp' + id.charAt(0).toUpperCase() + id.slice(1)).classList.add('aktif');
    if (btn) btn.classList.add('aktif');
}

function mTipSec(tip) {
    document.getElementById('mTip').value = tip;
    document.getElementById('tipBireysel').classList.toggle('aktif', tip === 'bireysel');
    document.getElementById('tipKurumsal').classList.toggle('aktif', tip === 'kurumsal');
}

function mIlDegisti() {
    const il = document.getElementById('mIl').value;
    const sel = document.getElementById('mVergiDairesi');
    sel.innerHTML = '<option value="">-- Seçin --</option>';
    if (!il) { sel.innerHTML = '<option value="">-- Önce İl Seçin --</option>'; return; }
    mVdData.filter(d => d.il === il).forEach(d => {
        const opt = document.createElement('option');
        opt.value = d.ad; opt.textContent = d.ad;
        sel.appendChild(opt);
    });
}

function mTelefonEkle(tel, etiket) {
    mTelSayac++;
    const i = mTelSayac;
    const div = document.createElement('div');
    div.className = 'coklu-satir';
    div.id = 'mTel' + i;
    div.innerHTML = `
        <select id="mTelEtk${i}" style="width:90px;flex:0 0 90px;">
            <option ${etiket==='Cep'||!etiket?'selected':''}>📱 Cep</option>
            <option ${etiket==='İş'?'selected':''}>💼 İş</option>
            <option ${etiket==='Ev'?'selected':''}>🏠 Ev</option>
            <option ${etiket==='Faks'?'selected':''}>📠 Faks</option>
        </select>
        <input type="tel" id="mTelNo${i}" placeholder="0532 000 00 00" value="${esc(tel||'')}">
        <button type="button" class="btn-sil" onclick="document.getElementById('mTel${i}').remove()">🗑</button>
    `;
    document.getElementById('mTelefonListesi').appendChild(div);
}

function mTelefonlariTopla() {
    const sonuc = [];
    document.querySelectorAll('#mTelefonListesi .coklu-satir').forEach(row => {
        const id = row.id.replace('mTel', '');
        const no = document.getElementById('mTelNo' + id)?.value.trim();
        const et = document.getElementById('mTelEtk' + id)?.value.replace(/[^\w\sİışğüöçĞÜÖÇİŞ]/gu, '').trim();
        if (no) sonuc.push({ telefon: no, etiket: et || 'Cep', varsayilan: sonuc.length === 0 ? 1 : 0 });
    });
    return sonuc;
}

function mAdresEkle(adresVal, sehirVal, ilceVal) {
    mAdrSayac++;
    const i = mAdrSayac;
    const ilOptions = Object.keys(TR_ILCELER).map(il =>
        `<option value="${il}" ${sehirVal===il?'selected':''}>${il}</option>`
    ).join('');
    const ilceOptions = sehirVal && TR_ILCELER[sehirVal]
        ? TR_ILCELER[sehirVal].map(ilce => `<option value="${ilce}" ${ilceVal===ilce?'selected':''}>${ilce}</option>`).join('')
        : '';
    const div = document.createElement('div');
    div.id = 'mAdr' + i;
    div.className = 'adr-kart';
    div.innerHTML = `
        <div class="adr-kart-header">
            <input type="text" id="mAdrBaslik${i}" placeholder="📍 Adres başlığı (Merkez, Şube...)"
                style="flex:1;padding:8px 11px;border:1.5px solid #e2e8f0;border-radius:8px;font-size:13px;font-weight:600;outline:none;">
            <button type="button" onclick="document.getElementById('mAdr${i}').remove()"
                style="margin-left:10px;background:#fee2e2;color:#dc2626;border:none;border-radius:8px;padding:7px 12px;cursor:pointer;font-size:12px;font-weight:700;">🗑 Kaldır</button>
        </div>
        <textarea id="mAdrAdres${i}" rows="2" placeholder="Açık adres (Sokak, Bina No...)"
            style="width:100%;padding:9px 11px;border:1.5px solid #e2e8f0;border-radius:8px;font-size:13px;box-sizing:border-box;margin-bottom:10px;outline:none;">${esc(adresVal||'')}</textarea>
        <div style="display:grid;grid-template-columns:1fr 1fr;gap:10px;">
            <div>
                <label style="font-size:11px;font-weight:700;color:#374151;display:block;margin-bottom:4px;">İL</label>
                <select id="mAdrIl${i}" onchange="mAdrIlDegisti(${i})"
                    style="width:100%;padding:8px 10px;border:1.5px solid #e2e8f0;border-radius:8px;font-size:13px;background:#fff;outline:none;">
                    <option value="">-- İl Seçin --</option>
                    ${ilOptions}
                </select>
            </div>
            <div>
                <label style="font-size:11px;font-weight:700;color:#374151;display:block;margin-bottom:4px;">İLÇE</label>
                <select id="mAdrIlce${i}"
                    style="width:100%;padding:8px 10px;border:1.5px solid #e2e8f0;border-radius:8px;font-size:13px;background:#fff;outline:none;">
                    ${ilceOptions ? '<option value="">-- İlçe Seçin --</option>' + ilceOptions : '<option value="">-- Önce İl Seçin --</option>'}
                </select>
            </div>
        </div>
    `;
    document.getElementById('mAdresListesi').appendChild(div);
}

function mAdrIlDegisti(i) {
    const il = document.getElementById('mAdrIl' + i).value;
    const sel = document.getElementById('mAdrIlce' + i);
    sel.innerHTML = '<option value="">-- İlçe Seçin --</option>';
    if (il && TR_ILCELER[il]) {
        TR_ILCELER[il].forEach(ilce => {
            const opt = document.createElement('option');
            opt.value = ilce; opt.textContent = ilce;
            sel.appendChild(opt);
        });
    } else {
        sel.innerHTML = '<option value="">-- Önce İl Seçin --</option>';
    }
}

function mAdresleriTopla() {
    const sonuc = [];
    document.querySelectorAll('#mAdresListesi .adr-kart').forEach(row => {
        const id = row.id.replace('mAdr', '');
        const adres = document.getElementById('mAdrAdres' + id)?.value.trim();
        if (!adres) return;
        sonuc.push({
            baslik: document.getElementById('mAdrBaslik' + id)?.value.trim() || 'Merkez',
            adres,
            sehir: document.getElementById('mAdrIl' + id)?.value || '',
            ilce: document.getElementById('mAdrIlce' + id)?.value || '',
            posta_kodu: '', varsayilan: sonuc.length === 0 ? 1 : 0
        });
    });
    return sonuc;
}

function mEtiketToggle(eid) {
    const chip = document.querySelector('#mEtiketSecici [data-eid="' + eid + '"]');
    if (mSeciliEtiketler.has(eid)) {
        mSeciliEtiketler.delete(eid);
        if (chip) chip.classList.remove('secili');
    } else {
        mSeciliEtiketler.add(eid);
        if (chip) chip.classList.add('secili');
    }
}

function musteriKaydet() {
    const adRaw = document.getElementById('mAd').value.trim();
    if (!adRaw) { alert('Ad Soyad / Ünvan zorunludur.'); mTabGec('genel', document.querySelectorAll('.m-tab')[0]); return; }
    const btn = document.getElementById('mKaydetBtn');
    btn.disabled = true; btn.textContent = '⏳ Kaydediliyor...';

    const params = new URLSearchParams({
        action: 'yeni_kaydet',
        ad_soyad:         adRaw,
        musteri_tipi:     document.getElementById('mTip').value,
        email:            document.getElementById('mEmail').value.trim(),
        vergi_no:         document.getElementById('mVergiNo').value.trim(),
        vergi_dairesi:    document.getElementById('mVergiDairesi').value,
        notlar:           document.getElementById('mNotlar').value.trim(),
        baslangic_bakiye: document.getElementById('mBakiye').value,
        telefonlar:       JSON.stringify(mTelefonlariTopla()),
        adresler:         JSON.stringify(mAdresleriTopla()),
        etiket_idler:     JSON.stringify([...mSeciliEtiketler]),
    });

    fetch('musteri_kontrol.php', { method:'POST', headers:{'Content-Type':'application/x-www-form-urlencoded'}, body: params.toString() })
    .then(r => r.json())
    .then(v => {
        const yeniId = v.musteri?.id || v.id || v.musteri_id;
        const kayitliAd = v.musteri?.ad_soyad || adRaw.toUpperCase();
        if (v.basari || v.ayni) {
            const teller = mTelefonlariTopla();
            musteriSec(yeniId, kayitliAd,
                document.getElementById('mVergiNo').value.trim(),
                teller.length ? teller[0].telefon : '',
                document.getElementById('mEmail').value.trim());
            document.getElementById('musteriModal').classList.remove('aktif');
        } else {
            alert('Hata: ' + (v.mesaj || 'Kayıt başarısız.'));
        }
    })
    .catch(() => alert('Sunucu hatası.'))
    .finally(() => { btn.disabled = false; btn.textContent = '💾 Kaydet ve Seç'; });
}

// Modal dışı tıklama ile kapat
['musteriModal','urunModal','seriModal'].forEach(id => {
    document.getElementById(id).addEventListener('click', function(e) {
        if (e.target === this) this.classList.remove('aktif');
    });
});

// Sayfa yüklenince bir boş satır
document.addEventListener('DOMContentLoaded', () => {
    kalemEkle();

    // Personel listesini yükle
    fetch('personel_kontrol.php', {method:'POST', headers:{'Content-Type':'application/x-www-form-urlencoded'}, body:'action=listele'})
        .then(r=>r.json()).then(v=>{
            const sel = document.getElementById('personelSecFatura');
            if (!sel) return;
            (v.personeller||[]).filter(p=>p.aktif==1).forEach(p=>{
                const o = document.createElement('option');
                o.value = p.id;
                o.textContent = p.ad_soyad + (p.gorev ? ' ('+p.gorev+')' : '');
                if (p.id == <?= (int)($_POST['personel_id'] ?? 0) ?>) o.selected = true;
                sel.appendChild(o);
            });
        });
    // Tutanaktan ön doldurma
    const urlP = new URLSearchParams(window.location.search);
    const fromTutanak = urlP.get('from_tutanak');
    const tutanakId = urlP.get('tutanak_id');
    const isTahsilat = urlP.get('tahsilat') === '1';
    if(fromTutanak && tutanakId){
        const action = fromTutanak === 'devir' ? 'get_devir' : 'get_hurda';
        fetch('tutanak_kontrol.php?action='+action+'&id='+tutanakId)
            .then(r=>r.json())
            .then(r=>{
                if(!r.success||!r.data) return;
                const d=r.data;
                const marka=d.cihaz_marka||d.cihaz_marka_adi||'';
                const model=d.cihaz_model||d.cihaz_model_adi||'';
                const sicil=d.cihaz_sicil_no||'';
                const tutanakTip=fromTutanak==='devir'?'EK-2 Devir Satış':'EK-1 Hurda/Geçici';
                const aciklama=`${tutanakTip} - ${marka} ${model}${sicil?' - Sicil: '+sicil:''}`;

                // Fatura notuna tutanak bilgisi ekle
                const notEl=document.getElementById('fatura_notu');
                if(notEl) notEl.value=(notEl.value?notEl.value+'\n':'')+`Tutanak No: ${d.sira_no||''} | ${aciklama}`;

                // İlk kalem satırına tutanak bilgilerini yaz
                const kalemTr=document.querySelector('#kalemBody tr');
                if(kalemTr){
                    const acikEl=kalemTr.querySelector('input[name="kalem_aciklama[]"]');
                    const fiyatEl=kalemTr.querySelector('input[name="kalem_fiyat[]"]');
                    const kdvSel=kalemTr.querySelector('select[name="kalem_kdv[]"]');
                    if(acikEl) acikEl.value=aciklama;
                    // Önce eşleştirme tablosundan fiyat çek (marka+model bazlı)
                    if(marka){
                        const encMarka=encodeURIComponent(marka);
                        const encModel=encodeURIComponent(model);
                        fetch(`tutanak_kontrol.php?action=get_eslestirme_by_marka_model&marka_adi=${encMarka}&model_adi=${encModel}`)
                            .then(re=>re.json())
                            .then(er=>{
                                const ed=er.data;
                                let bedel=null, kdv=null;
                                if(ed){
                                    if(fromTutanak==='devir'){
                                        bedel=ed.devir_bedeli;
                                        kdv=ed.devir_kdv;
                                    } else {
                                        bedel=ed.hurda_bedeli;
                                        kdv=ed.hurda_kdv;
                                    }
                                }
                                // Eşleştirme yoksa tutanaktaki satis_bedeli'ni kullan
                                if(!bedel && d.satis_bedeli) bedel=d.satis_bedeli;
                                if(fiyatEl && bedel){
                                    // Ayarlardaki bedel KDV dahil → KDV hariç'e çevir
                                    const kdvOran = parseFloat(kdv ?? 18);
                                    const kdvHaric = parseFloat(bedel) / (1 + kdvOran / 100);
                                    fiyatEl.value = kdvHaric.toFixed(2);
                                }
                                if(kdvSel && kdv!=null){
                                    let found=false;
                                    for(let opt of kdvSel.options){ if(parseFloat(opt.value)===parseFloat(kdv)){ kdvSel.value=opt.value; found=true; break; } }
                                    if(!found){ const o=new Option('%'+kdv,kdv,true,true); kdvSel.add(o); kdvSel.value=kdv; }
                                }
                                hesapla();
                            })
                            .catch(()=>{
                                if(fiyatEl && d.satis_bedeli) fiyatEl.value=parseFloat(d.satis_bedeli).toFixed(2);
                                hesapla();
                            });
                    } else {
                        if(fiyatEl && d.satis_bedeli) fiyatEl.value=parseFloat(d.satis_bedeli).toFixed(2);
                        hesapla();
                    }
                }

                // Müşteri: devir → alıcı, hurda → satıcı
                const musteriAdi=fromTutanak==='devir'?(d.alici_adi||''):(d.satici_adi||'');
                const musteriVkn=fromTutanak==='devir'?(d.alici_vergi_no||''):(d.satici_vergi_no||'');
                const musteriTel=fromTutanak==='devir'?(d.alici_tel||''):(d.satici_tel||'');

                if(musteriAdi){
                    // Önce mevcut müşteriler arasında ara, bulursa otomatik seç
                    fetch('musteri_kontrol.php',{
                        method:'POST',
                        headers:{'Content-Type':'application/x-www-form-urlencoded'},
                        body:'action=liste&ad_soyad='+encodeURIComponent(musteriAdi)+'&limit=5'
                    })
                    .then(res=>res.json())
                    .then(mv=>{
                        const musteriler=mv.musteriler||[];
                        if(musteriler.length>0){
                            // İlk müşteriyi otomatik seç
                            const m=musteriler[0];
                            musteriSec(m.id,m.ad_soyad,m.vergi_no||'',m.telefon||'',m.email||'');
                        } else {
                            // Müşteri bulunamazsa arama kutusuna adı yaz
                            const inp=document.getElementById('musteriAraInput');
                            if(inp) inp.value=musteriAdi;
                        }
                    })
                    .catch(()=>{
                        const inp=document.getElementById('musteriAraInput');
                        if(inp) inp.value=musteriAdi;
                    });
                }

                // Başlık uyarısı
                const bilgiBanner=document.createElement('div');
                bilgiBanner.style.cssText='background:#d1fae5;border:1px solid #6ee7b7;border-radius:8px;padding:10px 14px;margin-bottom:14px;font-size:13px;color:#065f46;display:flex;align-items:center;gap:8px';
                bilgiBanner.innerHTML='<span style="font-size:18px">📋</span> <span><strong>Tutanaktan aktarıldı:</strong> '+esc(aciklama)+' | Tutanak No: '+esc(d.sira_no||'')+'</span>';
                const icerik=document.querySelector('.sayfa-icerik');
                if(icerik) icerik.insertBefore(bilgiBanner,icerik.firstChild);
            })
            .catch(()=>{});
    }
});
</script>
</div><!-- /sayfa-icerik -->
</body>
</html>
