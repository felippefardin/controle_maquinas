<?php
include 'config.php';
include 'header.php';

// Consulta usando PDO para buscar apenas os itens em manutenção
try {
    $stmt = $pdo->prepare("SELECT i.*, m.nome as nome_mesa FROM itens i LEFT JOIN mesas m ON i.mesa_id = m.id WHERE i.status = 'Manutenção' ORDER BY i.id DESC");
    $stmt->execute();
    $itens_manutencao = $stmt->fetchAll();
} catch (Exception $e) {
    $itens_manutencao = [];
}
?>

<div class="container" style="margin-top: 30px;">
    <div class="row mb-4 align-items-center">
        <div class="col-md-6">
            <h2 class="fw-bolder text-dark">Máquinas em Manutenção</h2>
            <p class="text-muted">Lista informativa de todos os equipamentos atualmente em manutenção.</p>
        </div>
        <div class="col-md-6 text-md-end">
            <a href="index.php" class="btn btn-secondary rounded-pill px-4">Voltar para a Página Inicial</a>
        </div>
    </div>

    <div class="card shadow-sm border-0 rounded-4 overflow-hidden">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-striped table-hover mb-0 align-middle">
                    <thead class="table-dark">
                        <tr>
                            <th class="py-3 px-4">Tipo / Equipamento</th>
                            <th class="py-3 px-4">Patrimônio / Protocolo</th>
                            <th class="py-3 px-4">Mesa Vinculada</th>
                            <th class="py-3 px-4">IP da Máquina</th>
                            <th class="py-3 px-4 text-center">Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (count($itens_manutencao) > 0): ?>
                            <?php foreach($itens_manutencao as $item): ?>
                                <tr>
                                    <td class="px-4">
                                        <strong><?= $item['tipo'] == 'Outros' ? htmlspecialchars($item['nome_personalizado']) : htmlspecialchars($item['tipo']) ?></strong>
                                    </td>
                                    <td class="px-4 font-monospace"><?= htmlspecialchars($item['patrimonio_protocolo']) ?></td>
                                    <td class="px-4"><?= htmlspecialchars($item['nome_mesa'] ?? 'Sem Mesa') ?></td>
                                    <td class="px-4 font-monospace"><?= !empty($item['ip_maquina']) ? htmlspecialchars($item['ip_maquina']) : '-' ?></td>
                                    <td class="px-4 text-center">
                                        <span class="badge bg-danger rounded-pill px-3 py-2">Em Manutenção</span>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="5" class="text-center py-4 text-muted">Nenhuma máquina encontrada em manutenção no momento.</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<?php include 'footer.php'; ?>