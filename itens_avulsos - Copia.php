<?php
include 'config.php';

$busca = $_GET['busca'] ?? '';
$sql_base = "
    SELECT i.*, mt.id AS manutencao_ativa_id, m.nome AS mesa_em_uso
    FROM itens i
    LEFT JOIN manutencoes mt
        ON mt.substituto_item_id = i.id
       AND mt.status_manutencao = 'Aberto'
    LEFT JOIN mesas m ON m.id = mt.mesa_id
    WHERE (i.mesa_id IS NULL OR mt.id IS NOT NULL)
";
if ($busca) {
    $stmt = $pdo->prepare(
        $sql_base .
        " AND (i.patrimonio_protocolo LIKE ? OR i.nome_personalizado LIKE ? OR i.tipo LIKE ? OR m.nome LIKE ?)
          ORDER BY (mt.id IS NOT NULL) DESC, i.id DESC"
    );
    $stmt->execute(["%$busca%", "%$busca%", "%$busca%", "%$busca%"]);
} else {
    $stmt = $pdo->query($sql_base . " ORDER BY (mt.id IS NOT NULL) DESC, i.id DESC");
}
$itens = $stmt->fetchAll();
$totalItens = count($itens);
$totalEmUso = count(array_filter($itens, static fn(array $item): bool => !empty($item['manutencao_ativa_id'])));
$totalDisponiveis = $totalItens - $totalEmUso;

include 'header.php';
?>

<main class="inventory-page loose-items-page">
    <section class="page-heading" aria-labelledby="page-title">
        <div>
            <p class="eyebrow">GESTÃO DE PATRIMÔNIO</p>
            <h1 id="page-title">Itens avulsos</h1>
            <p class="page-description">Gerencie os equipamentos de reserva e acompanhe os que estão em uso temporário.</p>
        </div>
        <button class="btn btn-primary new-desk-button" type="button" data-bs-toggle="collapse" data-bs-target="#novoItemForm" aria-expanded="false" aria-controls="novoItemForm">
            <?= cmIcon('plus') ?> Novo equipamento
        </button>
    </section>

    <?php if ($busca): ?>
        <p class="loose-filter-note">Resumo dos resultados para <strong>“<?= e($busca) ?>”</strong>.</p>
    <?php endif; ?>
    <section class="inventory-stats loose-stats" aria-label="<?= $busca ? 'Resumo dos resultados da busca' : 'Resumo dos itens avulsos' ?>">
        <div class="stat-card">
            <span class="stat-icon stat-icon-blue"><?= cmIcon('box') ?></span>
            <div><span class="stat-label"><?= $busca ? 'Itens encontrados' : 'Total de itens' ?></span><strong class="stat-value"><?= $totalItens ?></strong></div>
        </div>
        <div class="stat-card">
            <span class="stat-icon stat-icon-teal"><?= cmIcon('monitor') ?></span>
            <div><span class="stat-label">Disponíveis</span><strong class="stat-value"><?= $totalDisponiveis ?></strong></div>
        </div>
        <div class="stat-card">
            <span class="stat-icon stat-icon-amber"><?= cmIcon('clock') ?></span>
            <div><span class="stat-label">Em uso temporário</span><strong class="stat-value"><?= $totalEmUso ?></strong></div>
        </div>
    </section>

    <div class="collapse" id="novoItemForm">
        <section class="loose-create-panel" aria-labelledby="create-item-title">
            <div class="loose-create-heading">
                <span class="mesa-icon"><?= cmIcon('plus') ?></span>
                <div><h2 id="create-item-title">Cadastrar equipamento</h2><p>Adicione um item ao estoque, sem vinculá-lo a uma mesa.</p></div>
            </div>
            <form action="acoes.php?acao=adicionar_item" method="POST" class="loose-create-form">
                <?= csrfField() ?>
                <input type="hidden" name="mesa_id" value="">
                <div class="loose-form-field">
                    <label class="form-label" for="select_tipo">Tipo de equipamento</label>
                    <select name="tipo" class="form-select" id="select_tipo" onchange="toggleNome(this.value)">
                        <option value="Tela">Tela</option>
                        <option value="CPU">CPU</option>
                        <option value="Outros">Outros</option>
                    </select>
                </div>
                <div class="loose-form-field" id="campo_nome_personalizado" style="display:none">
                    <label class="form-label" for="input_nome">Nome do aparelho</label>
                    <input type="text" name="nome_personalizado" id="input_nome" class="form-control" placeholder="Ex.: Impressora">
                </div>
                <div class="loose-form-field loose-patrimony-field">
                    <label class="form-label" for="patrimonio">Patrimônio / Protocolo</label>
                    <input type="text" name="patrimonio" id="patrimonio" class="form-control" required placeholder="Digite o código do equipamento">
                </div>
                <button type="submit" class="btn btn-primary"><?= cmIcon('plus') ?> Adicionar</button>
            </form>
        </section>
    </div>

    <section class="inventory-toolbar" aria-label="Buscar itens avulsos">
        <div>
            <h2>Equipamentos <span class="loose-count"><?= $totalItens ?></span></h2>
            <p><?= $busca ? 'Exibindo os itens que correspondem à sua busca.' : 'Consulte o patrimônio, a disponibilidade e as ações de cada item.' ?></p>
        </div>
        <form action="itens_avulsos.php" method="GET" class="inventory-search" role="search">
            <label for="busca" class="visually-hidden">Buscar por patrimônio, nome ou tipo</label>
            <?= cmIcon('search') ?>
            <input id="busca" type="search" name="busca" class="form-control" placeholder="Patrimônio, nome ou tipo..." value="<?= e($busca) ?>">
            <?php if ($busca): ?>
                <a href="itens_avulsos.php" class="clear-search" aria-label="Limpar busca" title="Limpar busca"><?= cmIcon('close') ?></a>
            <?php endif; ?>
            <button type="submit" class="btn btn-primary">Buscar</button>
        </form>
    </section>

    <?php if (!$itens): ?>
        <div class="inventory-empty">
            <?= cmIcon($busca ? 'search' : 'box') ?>
            <h3><?= $busca ? 'Nenhum equipamento encontrado' : 'Seu estoque começa aqui' ?></h3>
            <p><?= $busca ? 'Tente outro patrimônio, nome ou tipo de equipamento.' : 'Clique em Novo equipamento para cadastrar o primeiro item avulso.' ?></p>
            <?php if ($busca): ?><a href="itens_avulsos.php" class="btn btn-outline-primary">Limpar busca</a><?php endif; ?>
        </div>
    <?php else: ?>
        <div class="loose-table-panel">
            <table class="table loose-table align-middle mb-0">
                <caption class="visually-hidden">Equipamentos avulsos, patrimônios, situações e ações disponíveis</caption>
                <thead>
                    <tr>
                        <th scope="col">Equipamento</th>
                        <th scope="col">Patrimônio / Protocolo</th>
                        <th scope="col">Situação</th>
                        <th scope="col" class="text-end">Ações</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($itens as $item): ?>
                        <tr>
                            <td class="loose-equipment-cell">
                                <div class="loose-equipment">
                                    <span class="loose-equipment-icon"><?= cmIcon($item['tipo'] === 'CPU' ? 'cpu' : ($item['tipo'] === 'Tela' ? 'monitor' : 'box')) ?></span>
                                    <div><strong><?= e($item['tipo'] == 'Outros' ? $item['nome_personalizado'] : $item['tipo']) ?></strong><small><?= e($item['tipo'] == 'Outros' ? 'Outros equipamentos' : ($item['tipo'] === 'CPU' ? 'Computador' : 'Monitor')) ?></small></div>
                                </div>
                            </td>
                            <td class="loose-patrimony-cell" data-label="Patrimônio"><span class="loose-patrimony"><?= e($item['patrimonio_protocolo']) ?></span></td>
                            <td class="loose-status-cell" data-label="Situação">
                                <?php if (!empty($item['manutencao_ativa_id'])): ?>
                                    <span class="loose-status is-in-use"><span class="loose-status-dot"></span> Em uso temporário</span>
                                    <small class="loose-in-use-desk">Mesa: <?= e($item['mesa_em_uso'] ?? 'não identificada') ?></small>
                                <?php else: ?>
                                    <span class="loose-status is-available"><span class="loose-status-dot"></span> Disponível</span>
                                <?php endif; ?>
                            </td>
                            <td class="loose-actions-cell">
                                <div class="loose-actions">
                                    <?php if (!empty($item['manutencao_ativa_id'])): ?>
                                        <button type="button" class="btn btn-sm btn-outline-secondary" disabled title="O equipamento está em uso temporário"><?= cmIcon('clock') ?> Em uso</button>
                                    <?php else: ?>
                                        <a href="editar_item.php?id=<?= $item['id'] ?>" class="btn btn-sm btn-outline-secondary">Editar</a>
                                        <form action="acoes.php?acao=remover_item" method="POST" class="d-inline" onsubmit="return confirm('Confirmar exclusão?')">
                                            <?= csrfField() ?>
                                            <input type="hidden" name="id" value="<?= (int) $item['id'] ?>">
                                            <button class="btn btn-sm btn-outline-danger"><?= cmIcon('trash') ?> Excluir</button>
                                        </form>
                                    <?php endif; ?>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
            <div class="loose-table-footer"><?= $totalItens ?> <?= $totalItens === 1 ? 'equipamento listado' : 'equipamentos listados' ?><?= $busca ? ' nesta busca' : '' ?></div>
        </div>
    <?php endif; ?>
</main>

<script>
function toggleNome(val) {
    const campo = document.getElementById('campo_nome_personalizado');
    const input = document.getElementById('input_nome');
    if (val === 'Outros') {
        campo.style.display = 'block';
        input.setAttribute('required', 'required');
    } else {
        campo.style.display = 'none';
        input.removeAttribute('required');
        input.value = '';
    }
}
toggleNome(document.getElementById('select_tipo').value);
</script>

<?php include 'footer.php'; ?>
