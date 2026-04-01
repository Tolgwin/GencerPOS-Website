<?php
header('Content-Type: application/json; charset=utf-8');
ob_start();
require_once 'db.php';
require_once 'auth.php';

function j($ok, $msg='', $extra=[]){
    ob_clean();
    echo json_encode(array_merge(['success'=>$ok,'message'=>$msg],$extra), JSON_UNESCAPED_UNICODE);
    exit;
}

// Yetkili Servis Eşleştirme tablosunu oluştur (yoksa)
try {
    $pdo->exec("CREATE TABLE IF NOT EXISTS yetkili_servis_eslestirme (
        id INT AUTO_INCREMENT PRIMARY KEY,
        urun_adi VARCHAR(200) NULL,
        marka_id INT NOT NULL,
        model_id INT NULL,
        firma_unvan VARCHAR(300) NOT NULL,
        yetki_no VARCHAR(100) NULL,
        muhur_no VARCHAR(100) NULL,
        aktif TINYINT DEFAULT 1,
        olusturma_tarihi TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        KEY(marka_id), KEY(model_id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
} catch(Exception $e){}

// satis_bedeli kolonu yoksa ekle
try { $pdo->exec("ALTER TABLE tutanak_devir ADD COLUMN satis_bedeli DECIMAL(15,2) NULL"); } catch(Exception $e){}
// fatura_id kolonları (hangi faturanın oluşturulduğunu takip eder)
try { $pdo->exec("ALTER TABLE tutanak_devir ADD COLUMN fatura_id INT NULL"); } catch(Exception $e){}
try { $pdo->exec("ALTER TABLE tutanak_hurda ADD COLUMN fatura_id INT NULL"); } catch(Exception $e){}
// yetkili_servis_eslestirme fiyat kolonları
try { $pdo->exec("ALTER TABLE yetkili_servis_eslestirme ADD COLUMN devir_bedeli DECIMAL(15,2) NULL"); } catch(Exception $e){}
try { $pdo->exec("ALTER TABLE yetkili_servis_eslestirme ADD COLUMN devir_kdv TINYINT DEFAULT 18"); } catch(Exception $e){}
try { $pdo->exec("ALTER TABLE yetkili_servis_eslestirme ADD COLUMN hurda_bedeli DECIMAL(15,2) NULL"); } catch(Exception $e){}
try { $pdo->exec("ALTER TABLE yetkili_servis_eslestirme ADD COLUMN hurda_kdv TINYINT DEFAULT 20"); } catch(Exception $e){}

try {
    $action = $_GET['action'] ?? $_POST['action'] ?? '';

    switch($action){

    // ── FİRMALAR ──────────────────────────────────────────────────────
    case 'get_firmalar':
        j(true,'',['data'=>$pdo->query("SELECT * FROM tutanak_firmalar WHERE aktif=1 ORDER BY company_name")->fetchAll()]);

    case 'add_firma':
        $ad=trim($_POST['company_name']??'');
        if(!$ad) j(false,'Firma adı boş olamaz');
        $pdo->prepare("INSERT INTO tutanak_firmalar (company_name,authorization_number,stamp_number) VALUES (?,?,?)")
            ->execute([$ad,$_POST['authorization_number']??'',$_POST['stamp_number']??'']);
        j(true,'Firma eklendi',['id'=>$pdo->lastInsertId()]);

    case 'update_firma':
        $id=(int)($_POST['id']??0);
        $pdo->prepare("UPDATE tutanak_firmalar SET company_name=?,authorization_number=?,stamp_number=? WHERE id=?")
            ->execute([trim($_POST['company_name']??''),$_POST['authorization_number']??'',$_POST['stamp_number']??'',$id]);
        j(true,'Firma güncellendi');

    case 'delete_firma':
        $pdo->prepare("UPDATE tutanak_firmalar SET aktif=0 WHERE id=?")->execute([(int)($_POST['id']??0)]);
        j(true,'Firma silindi');

    case 'get_firma':
        $r=$pdo->prepare("SELECT * FROM tutanak_firmalar WHERE id=?");
        $r->execute([(int)($_GET['id']??0)]);
        j(true,'',['data'=>$r->fetch()]);

    // ── MARKALAR (ortak tutanak markaları) ────────────────────────────
    case 'get_markalar_tutanak':
        j(true,'',['data'=>$pdo->query("SELECT * FROM markalar WHERE aktif=1 ORDER BY marka_adi")->fetchAll()]);

    // Markalar + bağlı firma bilgileriyle birlikte (ayarlar için)
    case 'get_markalar_with_firma':
        // varsayilan_firma_id kolonu yoksa ekle
        try { $pdo->exec("ALTER TABLE markalar ADD COLUMN varsayilan_firma_id INT NULL"); } catch(Exception $e){}
        $rows=$pdo->query("
            SELECT m.id, m.marka_adi, m.varsayilan_firma_id,
                   f.company_name, f.authorization_number, f.stamp_number
            FROM markalar m
            LEFT JOIN tutanak_firmalar f ON f.id = m.varsayilan_firma_id
            WHERE m.aktif=1 ORDER BY m.marka_adi
        ")->fetchAll();
        j(true,'',['data'=>$rows]);

    case 'save_marka_firma':
        try { $pdo->exec("ALTER TABLE markalar ADD COLUMN varsayilan_firma_id INT NULL"); } catch(Exception $e){}
        $mid=(int)($_POST['marka_id']??0);
        $fid=$_POST['firma_id']===''||$_POST['firma_id']===null ? null : (int)$_POST['firma_id'];
        $pdo->prepare("UPDATE markalar SET varsayilan_firma_id=? WHERE id=?")->execute([$fid,$mid]);
        j(true,'Kaydedildi');

    case 'get_firma_by_marka':
        $markaAdi=trim($_GET['marka_adi']??'');
        $stmt=$pdo->prepare("
            SELECT f.id, f.company_name, f.authorization_number, f.stamp_number
            FROM markalar m
            LEFT JOIN tutanak_firmalar f ON f.id = m.varsayilan_firma_id
            WHERE m.marka_adi=? AND m.aktif=1 LIMIT 1
        ");
        $stmt->execute([$markaAdi]);
        $row=$stmt->fetch();
        j(true,'',['data'=>$row ?: null]);

    case 'get_modeller_tutanak':
        $mid=(int)($_GET['marka_id']??0);
        $stmt=$pdo->prepare("SELECT * FROM modeller WHERE marka_id=? AND aktif=1 ORDER BY model_adi");
        $stmt->execute([$mid]);
        j(true,'',['data'=>$stmt->fetchAll()]);

    // ── DEVIR ─────────────────────────────────────────────────────────
    case 'get_devir_liste':
        $arama=trim($_GET['arama']??'');
        $df=$_GET['tarih_bas']??$_GET['date_from']??'';
        $dt=$_GET['tarih_bit']??$_GET['date_to']??'';
        $durum=$_GET['durum']??'';
        $where=['1=1']; $p=[];
        if($arama){ $where[]='(d.satici_adi LIKE ? OR d.alici_adi LIKE ? OR d.cihaz_sicil_no LIKE ? OR d.sira_no LIKE ? OR d.cihaz_marka LIKE ? OR d.cihaz_model LIKE ?)'; $p=array_merge($p,["%$arama%","%$arama%","%$arama%","%$arama%","%$arama%","%$arama%"]); }
        if($df){ $where[]='d.tarih >= ?'; $p[]=$df; }
        if($dt){ $where[]='d.tarih <= ?'; $p[]=$dt; }
        if($durum){ $where[]='d.durum = ?'; $p[]=$durum; }
        $ws=implode(' AND ',$where);
        $cntStmt=$pdo->prepare("SELECT COUNT(*) FROM tutanak_devir d WHERE $ws");
        $cntStmt->execute($p);
        $toplam=(int)$cntStmt->fetchColumn();
        $limit=(int)($_GET['limit']??50); $offset=(int)($_GET['offset']??0);
        $stmt=$pdo->prepare("SELECT d.*, d.tarih AS tutanak_tarih, d.cihaz_marka AS cihaz_marka_adi, d.cihaz_model AS cihaz_model_adi, d.satis_fatura_no AS fatura_no, d.gib_onay_kodu AS gib_onay_no, f.company_name AS firma_adi FROM tutanak_devir d LEFT JOIN tutanak_firmalar f ON d.firma_id=f.id WHERE $ws ORDER BY d.tarih DESC, d.id DESC LIMIT $limit OFFSET $offset");
        $stmt->execute($p);
        j(true,'',['data'=>$stmt->fetchAll(),'toplam'=>$toplam]);

    case 'get_devir':
        $stmt=$pdo->prepare("SELECT d.*,
            d.tarih AS tutanak_tarih,
            d.cihaz_marka AS cihaz_marka_adi,
            d.cihaz_model AS cihaz_model_adi,
            d.z_raporu_sayisi AS z_raporu_no,
            d.toplam_hasilat AS gt_tutari,
            d.toplam_kdv AS kdv_toplam,
            d.satis_fatura_no AS fatura_no,
            d.satis_fatura_tarihi AS fatura_tarih,
            d.gib_onay_kodu AS gib_onay_no,
            d.yetkili_servis_adi AS servis_adi,
            d.yetkili_servis_adres AS servis_adresi,
            d.kullanim_baslangic_tarihi AS ilk_z_tarihi,
            d.son_kullanim_tarihi AS son_z_tarihi,
            f.company_name AS firma_adi
            FROM tutanak_devir d LEFT JOIN tutanak_firmalar f ON d.firma_id=f.id WHERE d.id=?");
        $stmt->execute([(int)($_GET['id']??0)]);
        $r=$stmt->fetch(); if(!$r) j(false,'Bulunamadı');
        j(true,'',['data'=>$r]);

    case 'add_devir':
        $sira=$pdo->query("SELECT COALESCE(MAX(CAST(sira_no AS UNSIGNED)),0)+1 FROM tutanak_devir")->fetchColumn();
        $pdo->prepare("INSERT INTO tutanak_devir (sira_no,tarih,gib_onay_kodu,yetkili_servis_adi,yetkili_servis_adres,yetkili_servis_vergi_dairesi,yetkili_servis_vergi_no,firma_id,yetki_numarasi,muhur_numarasi,satici_adi,satici_adres,satici_vergi_dairesi,satici_vergi_no,satici_tel,alici_adi,alici_adres,alici_vergi_dairesi,alici_vergi_no,alici_tel,cihaz_marka,cihaz_model,cihaz_sicil_no,kullanim_baslangic_tarihi,son_kullanim_tarihi,z_raporu_sayisi,toplam_kdv,toplam_hasilat,satis_fatura_no,satis_fatura_tarihi,diger_tespitler,servis_id,satis_bedeli) VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)")
            ->execute([
                str_pad($sira,4,'0',STR_PAD_LEFT),
                $_POST['tarih']??date('Y-m-d'),
                $_POST['gib_onay_kodu']??'',
                $_POST['yetkili_servis_adi']??'',
                $_POST['yetkili_servis_adres']??'',
                $_POST['yetkili_servis_vergi_dairesi']??'',
                $_POST['yetkili_servis_vergi_no']??'',
                $_POST['firma_id']?:null,
                $_POST['yetki_numarasi']??'',
                $_POST['muhur_numarasi']??'',
                $_POST['satici_adi']??'',
                $_POST['satici_adres']??'',
                $_POST['satici_vergi_dairesi']??'',
                $_POST['satici_vergi_no']??'',
                $_POST['satici_tel']??'',
                $_POST['alici_adi']??'',
                $_POST['alici_adres']??'',
                $_POST['alici_vergi_dairesi']??'',
                $_POST['alici_vergi_no']??'',
                $_POST['alici_tel']??'',
                $_POST['cihaz_marka']??'',
                $_POST['cihaz_model']??'',
                $_POST['cihaz_sicil_no']??'',
                $_POST['kullanim_baslangic_tarihi']?:null,
                $_POST['son_kullanim_tarihi']?:null,
                $_POST['z_raporu_sayisi']?:null,
                $_POST['toplam_kdv']?:null,
                $_POST['toplam_hasilat']?:null,
                $_POST['satis_fatura_no']??'',
                $_POST['satis_fatura_tarihi']?:null,
                $_POST['diger_tespitler']??'',
                $_POST['servis_id']?:null,
                $_POST['satis_bedeli']?:null
            ]);
        j(true,'Tutanak eklendi',['id'=>$pdo->lastInsertId()]);

    case 'update_devir':
        $id=(int)($_POST['id']??0);
        $pdo->prepare("UPDATE tutanak_devir SET tarih=?,gib_onay_kodu=?,yetkili_servis_adi=?,yetkili_servis_adres=?,firma_id=?,yetki_numarasi=?,muhur_numarasi=?,satici_adi=?,satici_adres=?,satici_vergi_dairesi=?,satici_vergi_no=?,satici_tel=?,alici_adi=?,alici_adres=?,alici_vergi_dairesi=?,alici_vergi_no=?,alici_tel=?,cihaz_marka=?,cihaz_model=?,cihaz_sicil_no=?,kullanim_baslangic_tarihi=?,son_kullanim_tarihi=?,z_raporu_sayisi=?,toplam_kdv=?,toplam_hasilat=?,satis_fatura_no=?,satis_fatura_tarihi=?,diger_tespitler=?,satis_bedeli=? WHERE id=?")
            ->execute([$_POST['tarih']??date('Y-m-d'),$_POST['gib_onay_kodu']??'',$_POST['yetkili_servis_adi']??'',$_POST['yetkili_servis_adres']??'',$_POST['firma_id']?:null,$_POST['yetki_numarasi']??'',$_POST['muhur_numarasi']??'',$_POST['satici_adi']??'',$_POST['satici_adres']??'',$_POST['satici_vergi_dairesi']??'',$_POST['satici_vergi_no']??'',$_POST['satici_tel']??'',$_POST['alici_adi']??'',$_POST['alici_adres']??'',$_POST['alici_vergi_dairesi']??'',$_POST['alici_vergi_no']??'',$_POST['alici_tel']??'',$_POST['cihaz_marka']??'',$_POST['cihaz_model']??'',$_POST['cihaz_sicil_no']??'',$_POST['kullanim_baslangic_tarihi']?:null,$_POST['son_kullanim_tarihi']?:null,$_POST['z_raporu_sayisi']?:null,$_POST['toplam_kdv']?:null,$_POST['toplam_hasilat']?:null,$_POST['satis_fatura_no']??'',$_POST['satis_fatura_tarihi']?:null,$_POST['diger_tespitler']??'',$_POST['satis_bedeli']?:null,$id]);
        j(true,'Tutanak güncellendi',['id'=>$id]);

    case 'delete_devir':
        $pdo->prepare("DELETE FROM tutanak_devir WHERE id=?")->execute([(int)($_POST['id']??0)]);
        j(true,'Tutanak silindi');

    case 'set_tutanak_fatura':
        $tip=trim($_POST['tip']??'');
        $tid=(int)($_POST['tutanak_id']??0);
        $fid=(int)($_POST['fatura_id']??0);
        if(!$tid||!$fid) j(false,'Eksik parametre');
        if($tip==='devir'){
            $pdo->prepare("UPDATE tutanak_devir SET fatura_id=? WHERE id=?")->execute([$fid,$tid]);
        } else {
            $pdo->prepare("UPDATE tutanak_hurda SET fatura_id=? WHERE id=?")->execute([$fid,$tid]);
        }
        j(true,'İşaretlendi');

    // ── HURDA ─────────────────────────────────────────────────────────
    case 'get_hurda_liste':
        $arama=trim($_GET['arama']??'');
        $df=$_GET['tarih_bas']??$_GET['date_from']??'';
        $dt=$_GET['tarih_bit']??$_GET['date_to']??'';
        $mudahale=$_GET['mudahale_amaci']??'';
        $durum=$_GET['durum']??'';
        $where=['1=1']; $p=[];
        if($arama){ $where[]='(h.satici_adi LIKE ? OR h.cihaz_sicil_no LIKE ? OR h.sira_no LIKE ? OR h.mudahale_amaci LIKE ? OR h.cihaz_marka LIKE ? OR h.cihaz_model LIKE ?)'; $p=array_merge($p,["%$arama%","%$arama%","%$arama%","%$arama%","%$arama%","%$arama%"]); }
        if($df){ $where[]='h.tarih >= ?'; $p[]=$df; }
        if($dt){ $where[]='h.tarih <= ?'; $p[]=$dt; }
        if($mudahale){ $where[]='h.mudahale_amaci = ?'; $p[]=$mudahale; }
        if($durum){ $where[]='h.durum = ?'; $p[]=$durum; }
        $ws=implode(' AND ',$where);
        $cntStmt=$pdo->prepare("SELECT COUNT(*) FROM tutanak_hurda h WHERE $ws");
        $cntStmt->execute($p);
        $toplam=(int)$cntStmt->fetchColumn();
        $limit=(int)($_GET['limit']??50); $offset=(int)($_GET['offset']??0);
        $stmt=$pdo->prepare("SELECT h.*, h.tarih AS tutanak_tarih, h.cihaz_marka AS cihaz_marka_adi, h.cihaz_model AS cihaz_model_adi, f.company_name AS firma_adi FROM tutanak_hurda h LEFT JOIN tutanak_firmalar f ON h.firma_id=f.id WHERE $ws ORDER BY h.tarih DESC, h.id DESC LIMIT $limit OFFSET $offset");
        $stmt->execute($p);
        j(true,'',['data'=>$stmt->fetchAll(),'toplam'=>$toplam]);

    case 'get_hurda':
        $stmt=$pdo->prepare("SELECT h.*,
            h.tarih AS tutanak_tarih,
            h.cihaz_marka AS cihaz_marka_adi,
            h.cihaz_model AS cihaz_model_adi,
            h.z_raporu_sayisi AS z_raporu_no,
            h.toplam_hasilat AS gt_tutari,
            h.toplam_kdv AS kdv_toplam,
            h.yetkili_servis_adi AS servis_adi,
            h.yetkili_servis_adres AS servis_adresi,
            h.kullanim_baslangic_tarihi AS ilk_z_tarihi,
            h.son_kullanim_tarihi AS son_z_tarihi,
            f.company_name AS firma_adi
            FROM tutanak_hurda h LEFT JOIN tutanak_firmalar f ON h.firma_id=f.id WHERE h.id=?");
        $stmt->execute([(int)($_GET['id']??0)]);
        $r=$stmt->fetch(); if(!$r) j(false,'Bulunamadı');
        j(true,'',['data'=>$r]);

    case 'add_hurda':
        $sira=$pdo->query("SELECT COALESCE(MAX(CAST(sira_no AS UNSIGNED)),0)+1 FROM tutanak_hurda")->fetchColumn();
        $pdo->prepare("INSERT INTO tutanak_hurda (sira_no,tarih,yetkili_servis_adi,yetkili_servis_adres,yetkili_servis_vergi_dairesi,yetkili_servis_vergi_no,firma_id,yetki_numarasi,muhur_numarasi,satici_adi,satici_adres,satici_vergi_dairesi,satici_vergi_no,cihaz_marka,cihaz_model,cihaz_sicil_no,kullanim_baslangic_tarihi,son_kullanim_tarihi,z_raporu_sayisi,toplam_kdv,toplam_hasilat,mudahale_amaci,diger_tespitler,servis_id) VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)")
            ->execute([
                str_pad($sira,4,'0',STR_PAD_LEFT),
                $_POST['tarih']??date('Y-m-d'),
                $_POST['yetkili_servis_adi']??'',
                $_POST['yetkili_servis_adres']??'',
                $_POST['yetkili_servis_vergi_dairesi']??'',
                $_POST['yetkili_servis_vergi_no']??'',
                $_POST['firma_id']?:null,
                $_POST['yetki_numarasi']??'',
                $_POST['muhur_numarasi']??'',
                $_POST['satici_adi']??'',
                $_POST['satici_adres']??'',
                $_POST['satici_vergi_dairesi']??'',
                $_POST['satici_vergi_no']??'',
                $_POST['cihaz_marka']??'',
                $_POST['cihaz_model']??'',
                $_POST['cihaz_sicil_no']??'',
                $_POST['kullanim_baslangic_tarihi']?:null,
                $_POST['son_kullanim_tarihi']?:null,
                $_POST['z_raporu_sayisi']?:null,
                $_POST['toplam_kdv']?:null,
                $_POST['toplam_hasilat']?:null,
                $_POST['mudahale_amaci']??'',
                $_POST['diger_tespitler']??'',
                $_POST['servis_id']?:null
            ]);
        j(true,'Tutanak eklendi',['id'=>$pdo->lastInsertId()]);

    case 'update_hurda':
        $id=(int)($_POST['id']??0);
        $pdo->prepare("UPDATE tutanak_hurda SET tarih=?,yetkili_servis_adi=?,yetkili_servis_adres=?,firma_id=?,yetki_numarasi=?,muhur_numarasi=?,satici_adi=?,satici_adres=?,satici_vergi_dairesi=?,satici_vergi_no=?,cihaz_marka=?,cihaz_model=?,cihaz_sicil_no=?,kullanim_baslangic_tarihi=?,son_kullanim_tarihi=?,z_raporu_sayisi=?,toplam_kdv=?,toplam_hasilat=?,mudahale_amaci=?,diger_tespitler=? WHERE id=?")
            ->execute([$_POST['tarih']??date('Y-m-d'),$_POST['yetkili_servis_adi']??'',$_POST['yetkili_servis_adres']??'',$_POST['firma_id']?:null,$_POST['yetki_numarasi']??'',$_POST['muhur_numarasi']??'',$_POST['satici_adi']??'',$_POST['satici_adres']??'',$_POST['satici_vergi_dairesi']??'',$_POST['satici_vergi_no']??'',$_POST['cihaz_marka']??'',$_POST['cihaz_model']??'',$_POST['cihaz_sicil_no']??'',$_POST['kullanim_baslangic_tarihi']?:null,$_POST['son_kullanim_tarihi']?:null,$_POST['z_raporu_sayisi']?:null,$_POST['toplam_kdv']?:null,$_POST['toplam_hasilat']?:null,$_POST['mudahale_amaci']??'',$_POST['diger_tespitler']??'',$id]);
        j(true,'Tutanak güncellendi',['id'=>$id]);

    case 'delete_hurda':
        $pdo->prepare("DELETE FROM tutanak_hurda WHERE id=?")->execute([(int)($_POST['id']??0)]);
        j(true,'Tutanak silindi');

    // ── MÜŞTERİ ARAMA (alıcı için) ───────────────────────────────────
    case 'get_musteriler':
        $arama=trim($_GET['arama']??'');
        if($arama){
            $stmt=$pdo->prepare("SELECT id, ad_soyad AS ad, telefon, email, adres, vergi_dairesi, vergi_no FROM musteriler WHERE (ad_soyad LIKE ? OR telefon LIKE ? OR email LIKE ?) ORDER BY ad_soyad ASC LIMIT 30");
            $stmt->execute(["%$arama%","%$arama%","%$arama%"]);
        } else {
            $stmt=$pdo->query("SELECT id, ad_soyad AS ad, telefon, email, adres, vergi_dairesi, vergi_no FROM musteriler ORDER BY ad_soyad ASC LIMIT 100");
        }
        j(true,'',['data'=>$stmt->fetchAll()]);

    case 'add_musteri':
        $ad=trim($_POST['ad_soyad']??'');
        if(!$ad) j(false,'Ad Soyad boş olamaz');
        $pdo->prepare("INSERT INTO musteriler (ad_soyad,telefon,email,adres,vergi_dairesi,vergi_no,musteri_tipi) VALUES (?,?,?,?,?,?,?)")
            ->execute([$ad,trim($_POST['telefon']??''),trim($_POST['email']??''),trim($_POST['adres']??''),trim($_POST['vergi_dairesi']??''),trim($_POST['vergi_no']??''),$_POST['musteri_tipi']??'bireysel']);
        $newId=$pdo->lastInsertId();
        $tel=trim($_POST['telefon']??'');
        if($tel) $pdo->prepare("INSERT INTO musteri_telefonlar (musteri_id,telefon,etiket,varsayilan) VALUES (?,?,?,1)")->execute([$newId,$tel,'Cep']);
        j(true,'Müşteri eklendi',['id'=>$newId,'ad'=>$ad,'telefon'=>$tel,'vergi_dairesi'=>$_POST['vergi_dairesi']??'','vergi_no'=>$_POST['vergi_no']??'','adres'=>$_POST['adres']??'']);

    // ── YETKİLİ SERVİS EŞLEŞTİRME CRUD ─────────────────────────────
    case 'get_eslestirme_listesi':
        $rows=$pdo->query("
            SELECT e.*, ma.marka_adi, mo.model_adi
            FROM yetkili_servis_eslestirme e
            JOIN markalar ma ON ma.id=e.marka_id
            LEFT JOIN modeller mo ON mo.id=e.model_id
            WHERE e.aktif=1
            ORDER BY ma.marka_adi, mo.model_adi, e.id
        ")->fetchAll();
        j(true,'',['data'=>$rows]);

    case 'get_eslestirme':
        $stmt=$pdo->prepare("SELECT * FROM yetkili_servis_eslestirme WHERE id=?");
        $stmt->execute([(int)($_GET['id']??0)]);
        j(true,'',['data'=>$stmt->fetch()]);

    case 'save_eslestirme':
        $id=(int)($_POST['id']??0);
        $markaId=(int)($_POST['marka_id']??0);
        $modelId=$_POST['model_id']!==''&&$_POST['model_id']!==null ? (int)$_POST['model_id'] : null;
        $firmaUnvan=trim($_POST['firma_unvan']??'');
        if(!$markaId) j(false,'Marka seçilmedi');
        if(!$firmaUnvan) j(false,'Firma ünvanı boş olamaz');
        $devirBedeli=trim($_POST['devir_bedeli']??'')?:null;
        $devirKdv=(int)($_POST['devir_kdv']??18);
        $hurdaBedeli=trim($_POST['hurda_bedeli']??'')?:null;
        $hurdaKdv=(int)($_POST['hurda_kdv']??20);
        if($id){
            $pdo->prepare("UPDATE yetkili_servis_eslestirme SET urun_adi=?,marka_id=?,model_id=?,firma_unvan=?,yetki_no=?,muhur_no=?,devir_bedeli=?,devir_kdv=?,hurda_bedeli=?,hurda_kdv=? WHERE id=?")
                ->execute([trim($_POST['urun_adi']??''),$markaId,$modelId,$firmaUnvan,trim($_POST['yetki_no']??''),trim($_POST['muhur_no']??''),$devirBedeli,$devirKdv,$hurdaBedeli,$hurdaKdv,$id]);
        } else {
            $pdo->prepare("INSERT INTO yetkili_servis_eslestirme (urun_adi,marka_id,model_id,firma_unvan,yetki_no,muhur_no,devir_bedeli,devir_kdv,hurda_bedeli,hurda_kdv) VALUES (?,?,?,?,?,?,?,?,?,?)")
                ->execute([trim($_POST['urun_adi']??''),$markaId,$modelId,$firmaUnvan,trim($_POST['yetki_no']??''),trim($_POST['muhur_no']??''),$devirBedeli,$devirKdv,$hurdaBedeli,$hurdaKdv]);
        }
        j(true,'Kaydedildi',['id'=>$id?:$pdo->lastInsertId()]);

    case 'delete_eslestirme':
        $pdo->prepare("UPDATE yetkili_servis_eslestirme SET aktif=0 WHERE id=?")->execute([(int)($_POST['id']??0)]);
        j(true,'Silindi');

    // Marka adı + model adına göre en uygun eşleştirmeyi bul (fiyat bilgileriyle)
    case 'get_eslestirme_by_marka_model':
        $markaAdi=trim($_GET['marka_adi']??'');
        $modelAdi=trim($_GET['model_adi']??'');
        $stmt=$pdo->prepare("
            SELECT e.firma_unvan, e.yetki_no, e.muhur_no,
                   e.devir_bedeli, e.devir_kdv, e.hurda_bedeli, e.hurda_kdv
            FROM yetkili_servis_eslestirme e
            JOIN markalar ma ON ma.id=e.marka_id
            LEFT JOIN modeller mo ON mo.id=e.model_id
            WHERE ma.marka_adi=? AND e.aktif=1
            ORDER BY
                CASE WHEN mo.model_adi=? THEN 0 ELSE 1 END,
                e.model_id IS NULL ASC,
                e.id ASC
            LIMIT 1
        ");
        $stmt->execute([$markaAdi, $modelAdi]);
        $row=$stmt->fetch();
        j(true,'',['data'=>$row?:null]);

    default:
        j(false,'Geçersiz işlem: '.$action);
    }

} catch(Exception $e){
    ob_clean();
    echo json_encode(['success'=>false,'message'=>$e->getMessage()],JSON_UNESCAPED_UNICODE);
}
ob_end_flush();
