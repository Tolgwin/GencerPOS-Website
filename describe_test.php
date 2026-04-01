<?php
require 'db.php';
$r = $pdo->query('DESCRIBE odeme_hesaplari');
foreach ($r as $row) echo $row['Field'] . ' | ' . $row['Type'] . "\n";
