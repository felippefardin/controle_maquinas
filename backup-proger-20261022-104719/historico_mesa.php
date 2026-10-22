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

garantirEstruturaOcupacao($pdo);
$stmtPeriodos = $pdo->prepare("SELECT * FROM mesa_ocupacoes WHERE mesa_id = ? ORDER BY inicio DESC, id DESC");
$stmtPeriodos->execute([$mesa_id]);
$periodos = $stmtPeriodos->fetchAll();
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="style.css?v=<?= (int) filemtime(__DIR__ . '/style.css') ?>">
    <title>Histórico - <?php echo $mesa['nome']; ?></title>
</head>
<body class="p-4">
    <div class="container">
        <h2>Histórico da Mesa: <?php echo htmlspecialchars($mesa['nome']); ?></h2>
        <a href="index.php" class="btn btn-secondary mb-3">Voltar</a>

        <h3 class="h5 mt-3">Períodos de ocupação</h3>
        <div class="table-responsive mb-4">
        <table class="table table-bordered align-middle">
            <thead><tr><th>Situação</th><th>Servidor</th><th>Início</th><th>Fim</th><th>Duração</th></tr></thead>
            <tbody>
            <?php if (!$periodos): ?>
                <tr><td colspan="5" class="text-muted text-center">Nenhum período estruturado registrado. O histórico anterior permanece disponível abaixo.</td></tr>
            <?php endif; ?>
            <?php foreach ($periodos as $periodo):
                $inicio = new DateTimeImmutable($periodo['inicio']);
                $fim = !empty($periodo['fim']) ? new DateTimeImmutable($periodo['fim']) : new DateTimeImmutable();
                $dias = $inicio->diff($fim)->days;
            ?>
                <tr>
                    <td><span class="badge <?= $periodo['situacao'] === 'vazia' ? 'text-bg-warning' : 'text-bg-success' ?>"><?= $periodo['situacao'] === 'vazia' ? 'Vazia' : 'Ocupada' ?></span></td>
                    <td><?= e($periodo['servidor'] ?: '—') ?></td>
                    <td><?= $inicio->format('d/m/Y H:i') ?></td>
                    <td><?= !empty($periodo['fim']) ? $fim->format('d/m/Y H:i') : 'Em andamento' ?></td>
                    <td><?= (int) $dias ?> dia(s)</td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
        </div>

        <h3 class="h5">Histórico de alterações</h3>
        
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
                    <td><?php echo htmlspecialchars($log['evento']); ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</body>
</html>
