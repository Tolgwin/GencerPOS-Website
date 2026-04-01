<?php
require "db.php";

// 1) personel_urun_prim tablosu
$pdo->exec("
CREATE TABLE IF NOT EXISTS personel_urun_prim (
    id INT AUTO_INCREMENT PRIMARY KEY,
    personel_id INT NOT NULL,
    urun_id INT NOT NULL,
    prim_orani DECIMAL(5,2) DEFAULT NULL COMMENT 'Yüzde oran (NULL ise sabit tutar)',
    prim_sabit_tutar DECIMAL(15,2) DEFAULT NULL COMMENT 'Sabit tutar (NULL ise oran)',
    olusturma DATETIME DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY uk_per_urun (personel_id, urun_id),
    FOREIGN KEY (personel_id) REFERENCES personeller(id) ON DELETE CASCADE,
    FOREIGN KEY (urun_id) REFERENCES urunler(id) ON DELETE CASCADE
)
");
echo "personel_urun_prim OK\n";

// 2) personel_prim'e urun_id ve kalem_tutari ekle
$cols = $pdo->query("SHOW COLUMNS FROM personel_prim")->fetchAll(PDO::FETCH_COLUMN, 0);
if (!in_array('urun_id', $cols)) {
    $pdo->exec("ALTER TABLE personel_prim ADD COLUMN urun_id INT NULL AFTER fatura_id");
    echo "personel_prim.urun_id eklendi\n";
} else echo "personel_prim.urun_id zaten var\n";
if (!in_array('kalem_tutari', $cols)) {
    $pdo->exec("ALTER TABLE personel_prim ADD COLUMN kalem_tutari DECIMAL(15,2) NULL AFTER urun_id");
    echo "personel_prim.kalem_tutari eklendi\n";
} else echo "personel_prim.kalem_tutari zaten var\n";
if (!in_array('urun_adi', $cols)) {
    $pdo->exec("ALTER TABLE personel_prim ADD COLUMN urun_adi VARCHAR(200) NULL AFTER kalem_tutari");
    echo "personel_prim.urun_adi eklendi\n";
} else echo "personel_prim.urun_adi zaten var\n";

echo "DONE\n";
