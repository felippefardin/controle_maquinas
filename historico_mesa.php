<?php
include 'config.php';
$mesa_id = $_GET['id'];

// Busca os dados da mesa (mesmo se deletada)
$stmt = $pdo->prepare("SELECT * FROM mesas WHERE id = ?");
$stmt->execute([$mesa_id]);
$mesa = $stmt->fetch();

// Busca o histórico
$stmtLog = $pdo->prepare("SELECT * FROM historico_mesas WHERE mesa_id = ? ORDER BY data_alteracao DESC");
$stmtLog->execute([$mesa_id]);
$logs = $stmtLog->fetchAll();
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <title>Histórico - <?php echo $mesa['identificacao']; ?></title>
</head>
<body class="p-4">
    <div class="container">
        <h2>Histórico da Mesa: <?php echo htmlspecialchars($mesa['identificacao']); ?></h2>
        <a href="index.php" class="btn btn-secondary mb-3">Voltar</a>
        
        <table class="table table-striped">
            <thead>
                <tr>
                    <th>Data/Hora</th>
                    <th>Descrição da Alteração</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($logs as $log): ?>
                <tr>
                    <td><?php echo date('d/m/Y H:i', strtotime($log['data_alteracao'])); ?></td>
                    <td><?php echo htmlspecialchars($log['descricao_alteracao']); ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</body>
</html>