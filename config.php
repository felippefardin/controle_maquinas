<?php
$host = 'localhost';
$db   = 'controle_maquinas';
$user = 'root'; 
$pass = '';
date_default_timezone_set('America/Sao_Paulo');
$host = 'localhost';
try {
    $pdo = new PDO("mysql:host=$host;dbname=$db;charset=utf8", $user, $pass);
} catch (PDOException $e) {
    die("Erro ao conectar: " . $e->getMessage());
}
?>