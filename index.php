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

<div class="row mb-5 align-items-center">   
    <div class="col-md-5">
        <h1 class="fw-bolder text-dark display-6">Controle de Máquinas </h1>
        <p class="text-muted">Gerenciamento de mesas de trabalho.</p>
    </div>

    <div class="col-md-7 text-md-end">

        <!-- Ícone adicionado aqui para garantir visibilidade ao lado dos botões do topo -->
        <a href="maquinas_manutencao.php" 
           class="btn btn-outline-danger px-3 py-2 fw-bold shadow-sm me-2" 
           title="Máquinas em Manutenção">

            <i class="fas fa-tools"></i> 
            <span class="badge bg-danger ms-1">
                <?php echo $total_manutencao; ?>
            </span>
        </a>

        <a href="tutorial.php" 
           class="btn btn-outline-primary px-4 py-2 fw-bold shadow-sm">
            📖 Tutorial
        </a>

        <a href="arquivo_mesas.php" 
           class="btn btn-outline-secondary px-4 py-2 fw-bold shadow-sm">
            📁 Arquivo
        </a>

        <a href="itens_avulsos.php" 
           class="btn btn-outline-info px-4 py-2 fw-bold shadow-sm">
            📦 Itens Avulsos
        </a>

    </div>
</div>


<div class="row g-3 mb-4" aria-label="Resumo do inventário">

    <div class="col-6 col-lg-3">
        <div class="card border-0 shadow-sm rounded-4 h-100">
            <div class="card-body">
                <div class="text-muted small">Mesas ativas</div>
                <div class="fs-2 fw-bold">
                    <?= (int) $resumo['mesas_ativas'] ?>
                </div>
            </div>
        </div>
    </div>

    <div class="col-6 col-lg-3">
        <div class="card border-0 shadow-sm rounded-4 h-100">
            <div class="card-body">
                <div class="text-muted small">Equipamentos</div>
                <div class="fs-2 fw-bold">
                    <?= (int) $resumo['equipamentos'] ?>
                </div>
            </div>
        </div>
    </div>

    <div class="col-6 col-lg-3">
        <div class="card border-0 shadow-sm rounded-4 h-100">
            <div class="card-body">
                <div class="text-muted small">Itens avulsos</div>
                <div class="fs-2 fw-bold text-info">
                    <?= (int) $resumo['itens_avulsos'] ?>
                </div>
            </div>
        </div>
    </div>

    <div class="col-6 col-lg-3">
        <div class="card border-0 shadow-sm rounded-4 h-100">
            <div class="card-body">
                <div class="text-muted small">Em manutenção</div>
                <div class="fs-2 fw-bold text-danger">
                    <?= (int) $total_manutencao ?>
                </div>
            </div>
        </div>
    </div>

</div>


<div class="row mb-5">

    <div class="col-md-12">

        <form action="index.php" 
              method="GET" 
              class="d-flex bg-white p-2 rounded-pill shadow-sm border">

            <input 
                type="text" 
                name="busca" 
                class="form-control border-0 rounded-pill ms-2" 
                placeholder="Buscar por patrimônio, nome ou IP..." 
                value="<?= htmlspecialchars($busca) ?>"
            >

            <button type="submit" class="btn btn-primary rounded-pill px-4">
                Buscar
            </button>

            <?php if($busca): ?>

                <a href="index.php" 
                   class="btn btn-link text-secondary text-decoration-none">
                    Limpar
                </a>

            <?php endif; ?>

        </form>

    </div>

</div>


<div class="card mb-5 shadow-sm border-0 bg-white rounded-4 overflow-hidden">

    <div class="card-body p-4">

        <!-- Mantido 'identificacao' aqui caso o seu acoes.php espere isso ao criar, ou ajuste no acoes.php se necessário -->

        <form action="acoes.php?acao=criar_mesa" 
              method="POST" 
              class="row g-3 align-items-center">

            <?= csrfField() ?>

            <div class="col-sm-4">

                <input 
                    type="text" 
                    name="identificacao" 
                    class="form-control rounded-pill" 
                    placeholder="Nome da Mesa (ex: Mesa 10)" 
                    required
                >

            </div>

            <div class="col-auto">

                <button type="submit" 
                        class="btn btn-success fw-bold px-4 rounded-pill">
                    + Criar Mesa
                </button>

            </div>

        </form>

    </div>

</div>


<div class="row">

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

foreach ($mesas as $mesa):

?>

<div class="col-12 mb-4">

    <div class="card mesa-card shadow-sm border-0 rounded-4">

        <div class="card-header bg-white d-flex justify-content-between align-items-center py-3 px-4 border-0">

            <form action="acoes.php?acao=editar_mesa" 
                  method="POST" 
                  class="d-flex gap-2 align-items-center">

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
                    class="form-control form-control-sm border-0 fw-bold" 
                    value="<?= htmlspecialchars($mesa['nome'] ?? '') ?>" 
                    required 
                    style="width: 200px;"
                >

                <button type="submit" 
                        class="btn btn-sm btn-outline-warning rounded-pill px-3">
                    Salvar
                </button>

            </form>


            <div>

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
                        title="Arquivar Mesa">
                        📁
                    </button>

                </form>


                <a 
                    href="historico_mesa.php?id=<?= $mesa['id'] ?>" 
                    class="btn btn-sm btn-outline-info rounded-pill me-2">
                    🕒 Histórico
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
                        Excluir Mesa
                    </button>

                </form>

            </div>

        </div>


        <div class="card-body px-4 pb-4 pt-0">

            <div class="mb-3">

                <a 
                    href="gerenciar_itens.php?mesa_id=<?= $mesa['id'] ?>" 
                    class="btn btn-sm btn-primary rounded-pill px-3 shadow-sm">
                    + Adicionar Equipamento
                </a>

            </div>


            <div class="list-group list-group-flush border rounded-4 overflow-hidden shadow-none">

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


            <div class="list-group-item d-flex justify-content-between align-items-center py-3 px-4 border-bottom bg-light">

                <div class="d-flex align-items-center gap-2">

                    <a 
                        href="manutencao.php?item_id=<?= $item['id'] ?>"
                        class="btn btn-sm btn-danger rounded-pill px-3 fw-bold"
                        title="Abrir manutenção">

                        🛠 Em manutenção

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


            <div class="list-group-item d-flex justify-content-between align-items-center py-3 px-4 border-bottom">

                <div>

                    <span class="badge bg-dark me-2 rounded-pill">
                        <?= e($item['tipo']) ?>
                    </span>


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


                <div class="btn-group">


                    <?php if ($item['tipo'] == 'CPU' && empty($item['ip_maquina'])): ?>


                        <button 
                            type="button" 
                            class="btn btn-sm btn-outline-secondary rounded-pill me-1" 
                            data-bs-toggle="modal" 
                            data-bs-target="#modalIP" 
                            data-id="<?= $item['id'] ?>">

                            ➕ IP

                        </button>


                    <?php elseif (!empty($item['ip_maquina'])): ?>


                        <a 
                            href="rdp://<?= htmlspecialchars($item['ip_maquina']) ?>" 
                            class="btn btn-sm btn-outline-primary rounded-pill px-3 me-1" 
                            title="Acessar via RDP">

                            🖥️

                        </a>


                    <?php endif; ?>


                    <a 
                        href="manutencao.php?item_id=<?= $item['id'] ?>" 
                        class="btn btn-sm btn-outline-dark rounded-pill px-3 me-1">

                        🛠️

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


<div class="modal fade" id="modalIP" tabindex="-1" aria-hidden="true">

    <div class="modal-dialog">

        <form action="acoes.php?acao=salvar_ip_item" method="POST">

            <?= csrfField() ?>

            <div class="modal-content">

                <div class="modal-header">

                    <h5 class="modal-title">
                        Configurar IP da CPU
                    </h5>

                    <button 
                        type="button" 
                        class="btn-close" 
                        data-bs-dismiss="modal">
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


<div class="modal fade" id="modalTroca" tabindex="-1" aria-hidden="true">

    <div class="modal-dialog">

        <form action="acoes.php?acao=trocar_itens" method="POST">

            <?= csrfField() ?>

            <div class="modal-content">


                <div class="modal-header">

                    <h5 class="modal-title">
                        Trocar equipamento entre mesas
                    </h5>

                    <button 
                        type="button" 
                        class="btn-close" 
                        data-bs-dismiss="modal">
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


<?php include 'footer.php'; ?>