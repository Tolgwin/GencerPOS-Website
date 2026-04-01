CREATE TABLE IF NOT EXISTS servis_katalog (
  id INT AUTO_INCREMENT PRIMARY KEY,
  ad VARCHAR(200) NOT NULL,
  kategori ENUM('yedek_parca','iscilik') NOT NULL DEFAULT 'yedek_parca',
  kod VARCHAR(50),
  birim_fiyat DECIMAL(12,2) DEFAULT 0.00,
  kdv_orani DECIMAL(5,2) DEFAULT 20.00,
  birim VARCHAR(20) DEFAULT 'Adet',
  aciklama TEXT,
  aktif TINYINT(1) DEFAULT 1,
  olusturma DATETIME DEFAULT CURRENT_TIMESTAMP
);
