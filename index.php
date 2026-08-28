<?php
include 'config.php';
include 'header.php';

$busca = isset($_GET['busca']) ? trim($_GET['busca']) : '';

// Consulta para contar quantas máquinas/itens estão em manutenção
$stmt_manutencao = $pdo->query("SELECT COUNT(*) FROM itens WHERE status = 'Manutenção'");
$total_manutencao = $stmt_manutencao->fetchColumn();

$resumo = $pdo->query("
    SELECT
        (SELECT COUNT(*) FROM mesas WHERE status = 'ativo') AS mesas_ativas,
        (SELECT COUNT(*) FROM itens) AS equipamentos,
        (SELECT COUNT(*) FROM itens WHERE mesa_id IS NULL) AS itens_avulsos
")->fetch();

$itensParaTroca = $pdo->query("
    SELECT i.id, i.tipo, i.patrimonio_protocolo, i.mesa_id, m.nome AS mesa_nome
    FROM itens i
    INNER JOIN mesas m ON m.id = i.mesa_id AND m.status = 'ativo'
    WHERE i.status = 'Ativo'
    ORDER BY i.tipo, m.nome, i.patrimonio_protocolo
")->fetchAll();
?>

<main class="inventory-page">
<section class="page-heading" aria-labelledby="page-title">
    <div>
        <p class="eyebrow">GESTÃO DE PATRIMÔNIO</p>
        <h1 id="page-title">Controle de Máquinas</h1>
        <p class="page-description">Suas mesas, equipamentos e manutenções em um só lugar.</p>
    </div>
    <button class="btn btn-primary new-desk-button" type="button" data-bs-toggle="collapse" data-bs-target="#novaMesaForm" aria-expanded="false" aria-controls="novaMesaForm">
        <?= cmIcon('plus') ?> Nova mesa
    </button>
</section>

<section class="inventory-stats" aria-label="Resumo do inventário">
    <div class="stat-card">
        <span class="stat-icon"><?= cmIcon('desk') ?></span>
        <div><span class="stat-label">Mesas ativas</span><strong class="stat-value"><?= (int) $resumo['mesas_ativas'] ?></strong></div>
    </div>
    <div class="stat-card">
        <span class="stat-icon stat-icon-blue"><?= cmIcon('monitor') ?></span>
        <div><span class="stat-label">Equipamentos</span><strong class="stat-value"><?= (int) $resumo['equipamentos'] ?></strong></div>
    </div>
    <a class="stat-card" href="itens_avulsos.php">
        <span class="stat-icon stat-icon-teal"><?= cmIcon('box') ?></span>
        <div><span class="stat-label">Itens avulsos</span><strong class="stat-value"><?= (int) $resumo['itens_avulsos'] ?></strong></div>
        <?= cmIcon('arrow', 'stat-arrow') ?>
    </a>
    <a class="stat-card" href="maquinas_manutencao.php">
        <span class="stat-icon stat-icon-amber"><?= cmIcon('tool') ?></span>
        <div><span class="stat-label">Em manutenção</span><strong class="stat-value"><?= (int) $total_manutencao ?></strong></div>
        <?= cmIcon('arrow', 'stat-arrow') ?>
    </a>
</section>

<div class="collapse" id="novaMesaForm">
    <section class="create-desk-panel" aria-labelledby="create-desk-title">
        <div><h2 id="create-desk-title">Adicionar uma mesa</h2><p>Crie um espaço para organizar os equipamentos.</p></div>
        <form action="acoes.php?acao=criar_mesa" method="POST" class="create-desk-form">
            <?= csrfField() ?>
            <div class="create-desk-field"><label for="identificacao" class="form-label">Nome da mesa</label><input id="identificacao" type="text" name="identificacao" class="form-control" placeholder="Ex.: Mesa 10" required></div>
            <button type="submit" class="btn btn-primary"><?= cmIcon('plus') ?> Criar mesa</button>
        </form>
    </section>
</div>

<section class="inventory-toolbar" aria-label="Buscar mesas e equipamentos">
    <div><h2>Mesas de trabalho</h2><p>Organize e acompanhe os equipamentos de cada mesa.</p></div>
    <form action="index.php" method="GET" class="inventory-search" role="search">
        <label for="busca" class="visually-hidden">Buscar por patrimônio, nome ou IP</label>
        <?= cmIcon('search') ?>
        <input id="busca" type="search" name="busca" class="form-control" placeholder="Patrimônio, nome ou IP..." value="<?= htmlspecialchars($busca) ?>">
        <?php if ($busca): ?><a href="index.php" class="clear-search" aria-label="Limpar busca" title="Limpar busca"><?= cmIcon('close') ?></a><?php endif; ?>
        <button type="submit" class="btn btn-primary">Buscar</button>
    </form>
</section>

<div class="row desk-list">

<?php

if ($busca) {

    $sql = "
        SELECT DISTINCT m.*
        FROM mesas m

        LEFT JOIN itens i
            ON m.id = i.mesa_id

        WHERE m.status = 'ativo'

        AND (
            m.nome LIKE :q_nome
            OR i.nome_personalizado LIKE :q_item
            OR i.patrimonio_protocolo LIKE :q_patrimonio
            OR i.ip_maquina LIKE :q_ip
        )

        ORDER BY m.id DESC
    ";

    $stmt = $pdo->prepare($sql);

    $termoBusca = "%{$busca}%";

    $stmt->execute([
        'q_nome' => $termoBusca,
        'q_item' => $termoBusca,
        'q_patrimonio' => $termoBusca,
        'q_ip' => $termoBusca,
    ]);

} else {

    $stmt = $pdo->query("
        SELECT *
        FROM mesas
        WHERE status = 'ativo'
        ORDER BY id DESC
    ");
}

/*
|--------------------------------------------------------------------------
| CARREGA AS MESAS
|--------------------------------------------------------------------------
*/

$mesas = $stmt->fetchAll();

/*
|--------------------------------------------------------------------------
| EXIBE AS MESAS
|--------------------------------------------------------------------------
*/

if (!$mesas): ?>
    <div class="col-12"><div class="inventory-empty">
        <?= cmIcon($busca ? 'search' : 'desk') ?>
        <h3><?= $busca ? 'Nenhuma mesa encontrada' : 'Tudo pronto para a primeira mesa' ?></h3>
        <p><?= $busca ? 'Tente outro nome, patrimônio ou endereço IP.' : 'Clique em Nova mesa para começar a organizar seus equipamentos.' ?></p>
        <?php if ($busca): ?><a class="btn btn-outline-primary" href="index.php">Limpar busca</a><?php endif; ?>
    </div></div>
<?php endif;
foreach ($mesas as $mesa):

?>

<div class="col-12 mb-4">

    <div class="card mesa-card shadow-sm border-0 rounded-4">

        <div class="card-header mesa-heading">

            <form action="acoes.php?acao=editar_mesa"
                  method="POST"
                  class="mesa-name-form">
                <span class="mesa-icon"><?= cmIcon('desk') ?></span>

                <?= csrfField() ?>

                <input
                    type="hidden"
                    name="id"
                    value="<?= $mesa['id'] ?>"
                >

                <!-- Alterado name para 'nome' e value para $mesa['nome'] -->

                <input
                    type="text"
                    name="nome"
                    class="form-control mesa-name"
                    aria-label="Nome da mesa"
                    value="<?= htmlspecialchars($mesa['nome'] ?? '') ?>"
                    required
                    title="Editar nome da mesa"
                >

                <button type="submit"
                        class="btn btn-sm mesa-save" title="Salvar nome da mesa">
                    Salvar
                </button>

            </form>

            <div class="mesa-actions">
                <span class="status-active"><span></span> Ativa</span>
                <form action="acoes.php?acao=arquivar_mesa"
                      method="POST"
                      class="d-inline"
                      onsubmit="return confirm('Arquivar esta mesa?')">

                    <?= csrfField() ?>

                    <input
                        type="hidden"
                        name="id"
                        value="<?= (int) $mesa['id'] ?>"
                    >

                    <button
                        class="btn btn-sm btn-outline-secondary rounded-pill me-2"
                        title="Arquivar mesa" aria-label="Arquivar mesa">
                        <?= cmIcon('archive') ?>
                    </button>

                </form>

                <a
                    href="historico_mesa.php?id=<?= $mesa['id'] ?>"
                    class="btn btn-sm btn-outline-info rounded-pill me-2">
                    <?= cmIcon('clock') ?> Histórico
                </a>

                <form
                    action="acoes.php?acao=deletar_mesa"
                    method="POST"
                    class="d-inline"
                    onsubmit="return confirm('Excluir mesa permanentemente?')">

                    <?= csrfField() ?>

                    <input
                        type="hidden"
                        name="id"
                        value="<?= (int) $mesa['id'] ?>"
                    >

                    <button
                        class="btn btn-sm btn-outline-danger rounded-pill">
                        <?= cmIcon('trash') ?> Excluir
                    </button>

                </form>

            </div>

        </div>

        <div class="card-body mesa-content">

            <div class="equipment-toolbar"><span>Equipamentos da mesa</span>

                <a
                    href="gerenciar_itens.php?mesa_id=<?= $mesa['id'] ?>"
                    class="btn btn-sm btn-primary rounded-pill px-3 shadow-sm">
                    <?= cmIcon('plus') ?> Adicionar equipamento
                </a>

            </div>

            <div class="list-group list-group-flush equipment-list">

            <?php

            $stmt_i = $pdo->prepare("
                SELECT
                    i.*,

                    EXISTS(
                        SELECT 1
                        FROM manutencoes mt

                        WHERE mt.substituto_item_id = i.id
                        AND mt.status_manutencao = 'Aberto'

                    ) AS equipamento_substituto

                FROM itens i

                WHERE i.mesa_id = ?

                ORDER BY
                    equipamento_substituto DESC,
                    CASE WHEN i.status = 'Manutenção' THEN 1 ELSE 0 END,
                    i.id
            ");

            $stmt_i->execute([$mesa['id']]);

            $itens = $stmt_i->fetchAll();

            if(!$itens) {

                echo "
                    <div class='p-3 text-muted small text-center bg-light'>
                        Nenhum equipamento cadastrado nesta mesa.
                    </div>
                ";

            }

            foreach ($itens as $item):

                $em_manutencao = ($item['status'] == 'Manutenção');

            ?>

            <?php if ($em_manutencao): ?>

            <div class="list-group-item equipment-row equipment-maintenance">

                <div class="d-flex align-items-center gap-2">

                    <a
                        href="manutencao.php?item_id=<?= $item['id'] ?>"
                        class="btn btn-sm btn-danger rounded-pill px-3 fw-bold"
                        title="Abrir manutenção">

                        <?= cmIcon('tool') ?> Em manutenção

                    </a>

                    <span class="text-muted small">

                        <?= htmlspecialchars(
                            $item['tipo'] == 'Outros'
                            ? $item['nome_personalizado']
                            : $item['tipo']
                        ) ?>

                        —

                        <?= htmlspecialchars($item['patrimonio_protocolo']) ?>

                    </span>

                </div>

                <a
                    href="manutencao.php?item_id=<?= $item['id'] ?>"
                    class="btn btn-sm btn-outline-danger rounded-pill px-3">

                    Ver detalhes

                </a>

            </div>

            <?php else: ?>

            <div class="list-group-item equipment-row">

                <div class="equipment-info">

                    <span class="equipment-icon" title="<?= e($item['tipo']) ?>"><?= cmIcon($item['tipo'] === 'Monitor' ? 'monitor' : ($item['tipo'] === 'CPU' ? 'cpu' : 'box')) ?></span>

                    <?php if (!empty($item['equipamento_substituto'])): ?>

                        <span class="badge bg-success me-2 rounded-pill">
                            Equipamento atual
                        </span>

                    <?php endif; ?>

                    <strong>

                        <?=
                            $item['tipo'] == 'Outros'
                            ? htmlspecialchars($item['nome_personalizado'])
                            : $item['tipo']
                        ?>

                    </strong>

                    <span class="text-muted ms-2 small font-monospace">

                        <?= htmlspecialchars($item['patrimonio_protocolo']) ?>

                        <?=
                            !empty($item['ip_maquina'])
                            ? ' | IP: ' . htmlspecialchars($item['ip_maquina'])
                            : ''
                        ?>

                    </span>

                </div>

                <div class="equipment-actions">

                    <?php if ($item['tipo'] == 'CPU' && empty($item['ip_maquina'])): ?>

                        <button
                            type="button"
                            class="btn btn-sm btn-outline-secondary rounded-pill me-1"
                            data-bs-toggle="modal"
                            data-bs-target="#modalIP"
                            data-id="<?= $item['id'] ?>">

                            <?= cmIcon('plus') ?> IP

                        </button>

                    <?php elseif (!empty($item['ip_maquina'])): ?>

                        <a
                            href="rdp://<?= htmlspecialchars($item['ip_maquina']) ?>"
                            class="btn btn-sm btn-outline-primary rounded-pill px-3 me-1"
                            title="Acessar via RDP" aria-label="Acessar via RDP">

                            <?= cmIcon('monitor') ?>

                        </a>

                    <?php endif; ?>

                    <a
                        href="manutencao.php?item_id=<?= $item['id'] ?>"
                        class="btn btn-sm btn-outline-dark rounded-pill px-3 me-1" title="Abrir manutenção" aria-label="Abrir manutenção">

                        <?= cmIcon('tool') ?>

                    </a>

                    <button
                        type="button"
                        class="btn btn-sm btn-outline-success rounded-pill px-3 me-1"

                        data-bs-toggle="modal"
                        data-bs-target="#modalTroca"

                        data-item-id="<?= (int) $item['id'] ?>"

                        data-item-tipo="<?= e($item['tipo']) ?>"

                        data-item-label="<?= e(
                            $item['tipo'] . ' — ' . $item['patrimonio_protocolo']
                        ) ?>">

                        Trocar

                    </button>

                    <a
                        href="editar_item.php?id=<?= $item['id'] ?>"
                        class="btn btn-sm btn-link text-decoration-none text-muted">

                        Editar

                    </a>

                    <form
                        action="acoes.php?acao=remover_item"
                        method="POST"
                        class="d-inline"
                        onsubmit="return confirm('Remover item?')">

                        <?= csrfField() ?>

                        <input
                            type="hidden"
                            name="id"
                            value="<?= (int) $item['id'] ?>"
                        >

                        <button
                            class="btn btn-sm btn-link text-danger text-decoration-none">

                            Remover

                        </button>

                    </form>

                </div>

            </div>

            <?php endif; ?>

            <?php endforeach; ?>

            </div>

        </div>

    </div>

</div>

<?php endforeach; ?>

</div>

<!-- ==========================================================
     MODAL IP
========================================================== -->

<div class="modal fade" id="modalIP" tabindex="-1" aria-hidden="true" aria-labelledby="modalIPTitle">

    <div class="modal-dialog">

        <form action="acoes.php?acao=salvar_ip_item" method="POST">

            <?= csrfField() ?>

            <div class="modal-content">

                <div class="modal-header">

                    <h5 class="modal-title" id="modalIPTitle">
                        Configurar IP da CPU
                    </h5>

                    <button
                        type="button"
                        class="btn-close"
                        data-bs-dismiss="modal" aria-label="Fechar">
                    </button>

                </div>

                <div class="modal-body">

                    <input
                        type="hidden"
                        name="item_id"
                        id="modal_item_id"
                    >

                    <input
                        type="text"
                        name="ip_maquina"
                        aria-label="Endereço IP da CPU"
                        class="form-control"
                        placeholder="172.17.16.45"
                        required
                    >

                </div>

                <div class="modal-footer">

                    <button
                        type="submit"
                        class="btn btn-primary">

                        Salvar IP

                    </button>

                </div>

            </div>

        </form>

    </div>

</div>

<!-- ==========================================================
     MODAL TROCA
========================================================== -->

<div class="modal fade" id="modalTroca" tabindex="-1" aria-hidden="true" aria-labelledby="modalTrocaTitle">

    <div class="modal-dialog">

        <form action="acoes.php?acao=trocar_itens" method="POST">

            <?= csrfField() ?>

            <div class="modal-content">

                <div class="modal-header">

                    <h5 class="modal-title" id="modalTrocaTitle">
                        Trocar equipamento entre mesas
                    </h5>

                    <button
                        type="button"
                        class="btn-close"
                        data-bs-dismiss="modal" aria-label="Fechar">
                    </button>

                </div>

                <div class="modal-body">

                    <input
                        type="hidden"
                        name="item_origem_id"
                        id="troca_item_origem_id"
                    >

                    <p class="small text-muted">

                        Equipamento selecionado:

                        <strong id="troca_item_origem_label"></strong>

                    </p>

                    <label
                        for="troca_item_destino_id"
                        class="form-label">

                        Equipamento da outra mesa

                    </label>

                    <select
                        name="item_destino_id"
                        id="troca_item_destino_id"
                        class="form-select"
                        required>

                        <option value="">
                            Selecione...
                        </option>

                        <?php foreach ($itensParaTroca as $opcao): ?>

                            <option
                                value="<?= (int) $opcao['id'] ?>"
                                data-tipo="<?= e($opcao['tipo']) ?>">

                                <?= e(
                                    $opcao['mesa_nome']
                                    . ' — '
                                    . $opcao['tipo']
                                    . ' — '
                                    . $opcao['patrimonio_protocolo']
                                ) ?>

                            </option>

                        <?php endforeach; ?>

                    </select>

                    <div class="form-text">

                        Somente equipamentos ativos e do mesmo tipo podem ser trocados.

                    </div>

                </div>

                <div class="modal-footer">

                    <button
                        type="button"
                        class="btn btn-secondary"
                        data-bs-dismiss="modal">

                        Cancelar

                    </button>

                    <button
                        type="submit"
                        class="btn btn-success">

                        Confirmar troca

                    </button>

                </div>

            </div>

        </form>

    </div>

</div>

<script>

var modalIP = document.getElementById('modalIP');

modalIP.addEventListener('show.bs.modal', function (event) {

    var button = event.relatedTarget;

    document.getElementById('modal_item_id').value =
        button.getAttribute('data-id');

});

var modalTroca = document.getElementById('modalTroca');

modalTroca.addEventListener('show.bs.modal', function (event) {

    var button = event.relatedTarget;

    var origemId = button.getAttribute('data-item-id');

    var tipo = button.getAttribute('data-item-tipo');

    document.getElementById('troca_item_origem_id').value =
        origemId;

    document.getElementById('troca_item_origem_label').textContent =
        button.getAttribute('data-item-label');

    var select = document.getElementById('troca_item_destino_id');

    select.value = '';

    Array.from(select.options).forEach(function (option) {

        option.hidden =
            option.value !== ''
            &&
            (
                option.value === origemId
                ||
                option.getAttribute('data-tipo') !== tipo
            );

    });

});

</script>

</main>
<?php include 'footer.php'; ?>
