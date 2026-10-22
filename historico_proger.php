<?php
require 'config.php';
$id = inteiroPositivo($_GET['id'] ?? null);
$q = $pdo->prepare('SELECT * FROM usuarios_proger WHERE id = ?'); $q->execute([$id]); $u = $q->fetch();
if (!$u) { http_response_code(404); exit('Servidor não encontrado.'); }
$q = $pdo->prepare('SELECT * FROM mesa_ocupacoes WHERE usuario_proger_id = ? ORDER BY inicio DESC, id DESC'); $q->execute([$id]); $periodos = $q->fetchAll();
$q = $pdo->prepare('SELECT * FROM historico_mesas WHERE usuario_proger_id = ? ORDER BY data_alteracao DESC'); $q->execute([$id]); $logs = $q->fetchAll();
require 'header.php';
?>
<main><h1>Histórico de <?= e($u['usuario']) ?></h1><p><?= $u['arquivado_em'] ? 'Servidor arquivado. Históricos preservados.' : 'Servidor ativo.' ?></p><a href="usuarios_proger.php<?= $u['arquivado_em'] ? '?arquivo=1' : '' ?>" class="btn btn-secondary mb-4">Voltar</a>
<h2 class="h4">Ocupação de mesas</h2><div class="table-responsive"><table class="table"><thead><tr><th>Mesa</th><th>Usuário registrado</th><th>Início</th><th>Fim</th></tr></thead><tbody>
<?php if (!$periodos): ?><tr><td colspan="4">Nenhum período vinculado a este servidor.</td></tr><?php endif; ?>
<?php foreach ($periodos as $periodo): ?><tr><td><a href="historico_mesa.php?id=<?= (int) $periodo['mesa_id'] ?>">Mesa #<?= (int) $periodo['mesa_id'] ?></a></td><td><?= e($periodo['servidor']) ?></td><td><?= e(date('d/m/Y H:i', strtotime($periodo['inicio']))) ?></td><td><?= $periodo['fim'] ? e(date('d/m/Y H:i', strtotime($periodo['fim']))) : 'Em andamento' ?></td></tr><?php endforeach; ?>
</tbody></table></div><h2 class="h4">Alterações nas mesas</h2><p class="text-muted">Os registros antigos permanecem no histórico de cada mesa. A vinculação de eventos ao servidor começa com este cadastro.</p><div class="table-responsive"><table class="table"><thead><tr><th>Data</th><th>Mesa</th><th>Evento</th></tr></thead><tbody>
<?php if (!$logs): ?><tr><td colspan="3">Nenhuma alteração vinculada.</td></tr><?php endif; ?>
<?php foreach ($logs as $log): ?><tr><td><?= e(date('d/m/Y H:i', strtotime($log['data_alteracao']))) ?></td><td><a href="historico_mesa.php?id=<?= (int) $log['mesa_id'] ?>">#<?= (int) $log['mesa_id'] ?></a></td><td><?= e($log['evento']) ?></td></tr><?php endforeach; ?></tbody></table></div></main>
<?php require 'footer.php'; ?>
