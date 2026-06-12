<?php 
include 'config.php'; 
include 'header.php'; 

$busca = isset($_GET['busca']) ? trim($_GET['busca']) : '';
?>

<div class="row mb-5 align-items-center">
    <div class="col-md-6">
        <h1 class="fw-bolder text-dark display-6">🖥️ Controle de Máquinas</h1>
        <p class="text-muted">Gerencie o parque tecnológico e as mesas de trabalho.</p>
    </div>
    <div class="col-md-6 text-md-end">
        <a href="arquivo_mesas.php" class="btn btn-outline-secondary px-4 py-2 fw-bold shadow-sm">🗑️ Arquivo</a>
        <a href="itens_avulsos.php" class="btn btn-outline-info px-4 py-2 fw-bold shadow-sm">📦 Itens Avulsos</a>
    </div>
</div>

<div class="row mb-5">
    <div class="col-md-12">
        <form action="index.php" method="GET" class="d-flex bg-white p-2 rounded-pill shadow-sm border">
            <input type="text" name="busca" class="form-control border-0 rounded-pill ms-2" placeholder="Buscar por patrimônio ou nome..." value="<?= htmlspecialchars($busca) ?>">
            <button type="submit" class="btn btn-primary rounded-pill px-4">Buscar</button>
            <?php if($busca): ?>
                <a href="index.php" class="btn btn-link text-secondary text-decoration-none">Limpar</a>
            <?php endif; ?>
        </form>
    </div>
</div>

<div class="card mb-5 shadow-sm border-0 bg-white rounded-4 overflow-hidden">
    <div class="card-body p-4">
        <form action="acoes.php?acao=criar_mesa" method="POST" class="row g-3 align-items-center">
    <div class="col-sm-4">
        <input type="text" name="identificacao" class="form-control rounded-pill" placeholder="Nome da Mesa (ex: Mesa 10)" required>
    </div>
    <div class="col-sm-3">
        <input type="text" name="ip_mesa" class="form-control rounded-pill" placeholder="IP da Mesa (Opcional)">
    </div>
    <div class="col-auto">
        <button type="submit" class="btn btn-success fw-bold px-4 rounded-pill">+ Criar Mesa</button>
    </div>
</form>
    </div>
</div>

<div class="row">
    <?php
    if ($busca) {
        $sql = "SELECT DISTINCT m.* FROM mesas m LEFT JOIN itens i ON m.id = i.mesa_id 
                WHERE m.status = 'ativo' AND (m.identificacao LIKE :q OR i.nome_personalizado LIKE :q OR i.patrimonio_protocolo LIKE :q) 
                ORDER BY m.id DESC";
        $stmt = $pdo->prepare($sql);
        $stmt->execute(['q' => "%$busca%"]);
    } else {
        $stmt = $pdo->query("SELECT * FROM mesas WHERE status = 'ativo' ORDER BY id DESC");
    }
    $mesas = $stmt->fetchAll();

    foreach ($mesas as $mesa):
    ?>
    <div class="col-12 mb-4">
        <div class="card mesa-card shadow-sm border-0 rounded-4">
            <div class="card-header bg-white d-flex justify-content-between align-items-center py-3 px-4 border-0">
                <form action="acoes.php?acao=editar_mesa" method="POST" class="d-flex gap-2 align-items-center">
                    <input type="hidden" name="id" value="<?= $mesa['id'] ?>">
                    <span class="text-uppercase small fw-bold text-secondary">Mesa:</span>
                    <input type="text" name="ip_mesa" class="form-control form-control-sm" value="<?= htmlspecialchars($mesa['ip_mesa'] ?? '') ?>" placeholder="IP da Mesa" style="width: 120px;">
                    <input type="text" name="identificacao" class="form-control form-control-sm border-0 bg-light fw-bold" value="<?= htmlspecialchars($mesa['identificacao']) ?>" required style="width: 150px;">
                    <button type="submit" class="btn btn-sm btn-warning rounded-pill px-3">Salvar</button>
                </form>
                <div>
                    <a href="historico_mesa.php?id=<?= $mesa['id'] ?>" class="btn btn-sm btn-outline-info rounded-pill me-2">🕒 Histórico</a>
                    <a href="acoes.php?acao=deletar_mesa&id=<?= $mesa['id'] ?>" class="btn btn-sm btn-outline-danger rounded-pill" onclick="return confirm('Excluir mesa permanentemente?')">Excluir Mesa</a>
                </div>
            </div>
            <div class="card-body px-4 pb-4 pt-0">
                <div class="mb-3">
                    <a href="gerenciar_itens.php?mesa_id=<?= $mesa['id'] ?>" class="btn btn-sm btn-primary rounded-pill px-3 shadow-sm">+ Adicionar Equipamento</a>
                </div>
                
                <div class="list-group list-group-flush border rounded-4 overflow-hidden shadow-none">
                    <?php
                    $stmt_i = $pdo->prepare("SELECT * FROM itens WHERE mesa_id = ?");
                    $stmt_i->execute([$mesa['id']]);
                    $itens = $stmt_i->fetchAll();
                    
                    if(!$itens) echo "<div class='p-3 text-muted small text-center bg-light'>Nenhum equipamento cadastrado nesta mesa.</div>";

                    foreach ($itens as $item):
                        $is_match = ($busca && (stripos($item['patrimonio_protocolo'], $busca) !== false || stripos($item['nome_personalizado'], $busca) !== false));
                        $em_manutencao = ($item['status'] == 'Manutenção');
                    ?>
                        <div class="list-group-item d-flex justify-content-between align-items-center <?= $is_match ? 'bg-warning-subtle' : '' ?> <?= $em_manutencao ? 'bg-light' : '' ?> py-3 px-4 border-bottom">
                            <div>
                                <span class="badge <?= $em_manutencao ? 'bg-danger' : 'bg-dark' ?> me-2 rounded-pill">
                                    <?= $item['tipo'] ?>
                                </span>
                                <strong class="<?= $em_manutencao ? 'text-danger' : 'text-dark' ?>">
                                    <?= $item['tipo'] == 'Outros' ? htmlspecialchars($item['nome_personalizado']) : $item['tipo'] ?>
                                </strong>
                                <span class="text-muted ms-2 small font-monospace">
                                    <?= htmlspecialchars($item['patrimonio_protocolo']) ?>
                                    <?= !empty($item['ip_maquina']) ? ' | IP: ' . htmlspecialchars($item['ip_maquina']) : '' ?>
                                </span>
                            </div>
                            
                            <div class="btn-group">
                                <a href="manutencao.php?item_id=<?= $item['id'] ?>" class="btn btn-sm <?= $em_manutencao ? 'btn-danger' : 'btn-outline-dark' ?> rounded-pill px-3 me-1">🛠️</a>
                                <a href="editar_item.php?id=<?= $item['id'] ?>" class="btn btn-sm btn-link text-decoration-none text-muted">Editar</a>
                                <a href="acoes.php?acao=remover_item&id=<?= $item['id'] ?>" class="btn btn-sm btn-link text-danger text-decoration-none" onclick="return confirm('Remover item?')">Remover</a>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
    </div>
    <?php endforeach; ?>
</div>

<?php include 'footer.php'; ?>