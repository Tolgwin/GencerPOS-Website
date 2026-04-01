<?php
require 'db.php';

$sqls = [
"CREATE TABLE IF NOT EXISTS roller (
    id          INT AUTO_INCREMENT PRIMARY KEY,
    ad          VARCHAR(50) NOT NULL UNIQUE,
    aciklama    VARCHAR(200),
    izinler     JSON,
    olusturma   DATETIME DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",

"CREATE TABLE IF NOT EXISTS kullanicilar (
    id              INT AUTO_INCREMENT PRIMARY KEY,
    ad_soyad        VARCHAR(100) NOT NULL,
    kullanici_adi   VARCHAR(60)  NOT NULL UNIQUE,
    email           VARCHAR(120),
    sifre_hash      VARCHAR(255) NOT NULL,
    rol_id          INT NOT NULL DEFAULT 1,
    aktif           TINYINT(1)   NOT NULL DEFAULT 1,
    son_giris       DATETIME,
    olusturma       DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (rol_id) REFERENCES roller(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",

"INSERT IGNORE INTO roller (id, ad, aciklama, izinler) VALUES
 (1, 'admin',    'Tam Yetki',
  '{\"fatura\":true,\"musteri\":true,\"urun\":true,\"kasa\":true,\"rapor\":true,\"personel\":true,\"servis\":true,\"tutanak\":true,\"ayarlar\":true,\"kullanici\":true,\"db\":true,\"import_export\":true}'),
 (2, 'muhasebe', 'Muhasebe + Raporlar',
  '{\"fatura\":true,\"musteri\":true,\"urun\":false,\"kasa\":true,\"rapor\":true,\"personel\":false,\"servis\":false,\"tutanak\":false,\"ayarlar\":false,\"kullanici\":false,\"db\":false,\"import_export\":true}'),
 (3, 'teknisyen','Servis + Tutanak',
  '{\"fatura\":false,\"musteri\":true,\"urun\":false,\"kasa\":false,\"rapor\":false,\"personel\":false,\"servis\":true,\"tutanak\":true,\"ayarlar\":false,\"kullanici\":false,\"db\":false,\"import_export\":false}'),
 (4, 'standart', 'Temel Kullanım',
  '{\"fatura\":true,\"musteri\":true,\"urun\":true,\"kasa\":false,\"rapor\":false,\"personel\":false,\"servis\":false,\"tutanak\":false,\"ayarlar\":false,\"kullanici\":false,\"db\":false,\"import_export\":false}')"
];

$hatalar = [];
foreach ($sqls as $sql) {
    try {
        $pdo->exec($sql);
    } catch (PDOException $e) {
        $hatalar[] = $e->getMessage();
    }
}

// Varsayılan admin kullanıcısı (admin / Admin123!)
$kontrol = $pdo->query("SELECT COUNT(*) FROM kullanicilar WHERE kullanici_adi='admin'")->fetchColumn();
if (!$kontrol) {
    $pdo->prepare("
        INSERT INTO kullanicilar (ad_soyad, kullanici_adi, email, sifre_hash, rol_id)
        VALUES (?, ?, ?, ?, 1)
    ")->execute(['Sistem Yöneticisi', 'admin', 'admin@faturaapp.com', password_hash('Admin123!', PASSWORD_DEFAULT)]);
    echo "Admin kullanıcısı oluşturuldu.\n";
}

if ($hatalar) {
    echo "Hatalar:\n" . implode("\n", $hatalar) . "\n";
} else {
    echo "Tablolar hazır.\n";
}
