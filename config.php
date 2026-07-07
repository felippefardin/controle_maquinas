<?php
$host = 'localhost';
$db   = 'controle_maquinas';
$user = 'root'; 
$pass = '';
date_default_timezone_set('America/Sao_Paulo');

try {
    $pdo = new PDO("mysql:host=$host;dbname=$db;charset=utf8", $user, $pass);
    // Adiciona tratamento de erros para que, se algo falhar, o PHP te avise em vez de ficar em branco
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("Erro ao conectar: " . $e->getMessage());
}

// function registrarLog($pdo, $mesa_id, $mensagem) {
//     try {
//         $stmt = $pdo->prepare("INSERT INTO historico_mesas (mesa_id, descricao_alteracao, data_alteracao) VALUES (?, ?, NOW())");
//         $stmt->execute([$mesa_id, $mensagem]);
//     } catch (PDOException $e) {
//         // Se a tabela não existir, o log não falhará o site todo
//     }
// }
function registrarLog($pdo, $mesa_id, $mensagem) {
    // Corrigido para os nomes das colunas que você criou: 'evento' e 'data'
    // Nota: 'data' é preenchido pelo banco com CURRENT_TIMESTAMP, mas pode ser passado via SQL
    $stmt = $pdo->prepare("INSERT INTO historico_mesas (mesa_id, evento, data_alteracao) VALUES (?, ?, NOW())");
    $stmt->execute([$mesa_id, $mensagem]);
}
?>