<?php
require "db.php";
$tables = $pdo->query("SHOW TABLES")->fetchAll(PDO::FETCH_COLUMN);
echo implode("\n", $tables);
echo "\n---\n";
$cols = $pdo->query("SHOW COLUMNS FROM personeller")->fetchAll(PDO::FETCH_ASSOC);
foreach($cols as $c) echo "personeller: ".$c['Field']." ".$c['Type']."\n";
$cols2 = $pdo->query("SHOW COLUMNS FROM personel_prim")->fetchAll(PDO::FETCH_ASSOC);
foreach($cols2 as $c) echo "personel_prim: ".$c['Field']." ".$c['Type']."\n";
