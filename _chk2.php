<?php
require "db.php";
$c = $pdo->query("SHOW COLUMNS FROM hesap_hareketleri")->fetchAll(PDO::FETCH_COLUMN, 0);
echo "hesap_hareketleri: " . implode(", ", $c) . "\n";
$c2 = $pdo->query("SELECT * FROM odeme_hesaplari LIMIT 1")->fetch(PDO::FETCH_ASSOC);
print_r($c2);
