<?php
require 'db.php';
$id = (int) $_GET['id'];

$pdo->prepare("DELETE FROM fatura_kalemleri WHERE fatura_id = ?")->execute([$id]);
$pdo->prepare("DELETE FROM faturalar WHERE id = ?")->execute([$id]);

header("Location: index.php");
exit;
?>