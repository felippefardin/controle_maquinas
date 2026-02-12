<?php include 'config.php'; 
$busca = isset($_GET['busca']) ? trim($_GET['busca']) : '';
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Controle de Máquinas</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="style.css">
</head>
<body>

<div class="container py-4">
    <div class="row mb-4 align-items-center">
        <div class="col-md-6">
            <h1 class="display-5 fw-bold text-primary">🖥️ Controle de Máquinas</h1>
        </div>
        <div class="col-md-6">
            <form action="index.php" method="GET" class="d-flex">
                <input type="text" name="busca" class="form-control me-2" placeholder="Buscar patrimônio ou nome..." value="<?= htmlspecialchars($busca) ?>">
                <button type="submit" class="btn btn-primary">Buscar</button>
                <?php if($busca): ?>
                    <a href="index.php" class="btn btn-outline-secondary ms-2">Limpar</a>
                <?php endif; ?>
            </form>
        </div>
    </div>

    <div class="card mb-4 shadow-sm border-0">
        <div class="card-body bg-light">
            <form action="acoes.php?acao=criar_mesa" method="POST" class="row g-3 align-items-center">
                <div class="col-auto">
                    <label class="fw-bold text-secondary">Nova Mesa:</label>
                </div>
                <div class="col-sm-4">
                    <input type="text" name="identificacao" class="form-control" placeholder="Ex: Mesa 10 ou RH" required>
                </div>
                <div class="col-auto">
                    <button type="submit" class="btn btn-success fw-bold">+ Criar Mesa</button>
                </div>
            </form>
        </div>
    </div>

    <div class="row">
        <?php
       
        if ($busca) {
            $sql = "SELECT DISTINCT m.* FROM mesas m LEFT JOIN itens i ON m.id = i.mesa_id 
                    WHERE m.identificacao LIKE :q OR i.nome_personalizado LIKE :q OR i.patrimonio_protocolo LIKE :q ORDER BY m.id DESC";
            $stmt = $pdo->prepare($sql);
            $stmt->execute(['q' => "%$busca%"]);
        } else {
            $stmt = $pdo->query("SELECT * FROM mesas ORDER BY id DESC");
        }
        $mesas = $stmt->fetchAll();

        foreach ($mesas as $mesa):
        ?>
        <div class="col-12 mb-4">
            <div class="card mesa-card shadow-sm border-0">
                <div class="card-header bg-white d-flex justify-content-between align-items-center py-3 border-bottom">
                    <form action="acoes.php?acao=editar_mesa" method="POST" class="d-flex gap-2">
                        <input type="hidden" name="id" value="<?= $mesa['id'] ?>">
                        <input type="text" name="identificacao" class="form-control form-control-sm fw-bold border-primary-subtle" value="<?= htmlspecialchars($mesa['identificacao']) ?>">
                        <button type="submit" class="btn btn-sm btn-warning">Salvar</button>
                    </form>
                    <a href="acoes.php?acao=deletar_mesa&id=<?= $mesa['id'] ?>" class="btn btn-sm btn-outline-danger" onclick="return confirm('Excluir mesa permanentemente?')">Excluir Mesa</a>
                </div>
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <a href="gerenciar_itens.php?mesa_id=<?= $mesa['id'] ?>" class="btn btn-sm btn-primary">+ Adicionar Equipamento</a>
                    </div>
                    
                    <div class="list-group list-group-flush border rounded overflow-hidden">
                        <?php
                        $stmt_i = $pdo->prepare("SELECT * FROM itens WHERE mesa_id = ?");
                        $stmt_i->execute([$mesa['id']]);
                        $itens = $stmt_i->fetchAll();
                        
                        if(!$itens) echo "<div class='p-3 text-muted small text-center'>Nenhum equipamento nesta mesa.</div>";

                        foreach ($itens as $item):
                            $is_match = ($busca && (stripos($item['patrimonio_protocolo'], $busca) !== false || stripos($item['nome_personalizado'], $busca) !== false));
                            $em_manutencao = ($item['status'] == 'Manutenção');
                        ?>
                            <div class="list-group-item d-flex justify-content-between align-items-center <?= $is_match ? 'bg-warning-subtle' : '' ?> <?= $em_manutencao ? 'bg-light' : '' ?>">
                                <div>
                                    <span class="badge <?= $em_manutencao ? 'bg-danger' : 'bg-secondary' ?> me-2">
                                        <?= $item['tipo'] ?>
                                    </span>
                                    <strong class="<?= $em_manutencao ? 'text-danger' : '' ?>">
                                        <?= $item['tipo'] == 'Outros' ? htmlspecialchars($item['nome_personalizado']) : $item['tipo'] ?>
                                    </strong>
                                    <span class="text-muted ms-2">Patrimônio: <?= htmlspecialchars($item['patrimonio_protocolo']) ?></span>
                                    
                                    <?php if($em_manutencao): ?>
                                        <span class="ms-3 text-danger fw-bold small">⚠️ (Aparelho em Manutenção)</span>
                                    <?php endif; ?>
                                </div>
                                
                                <div class="btn-group">
                                    <a href="manutencao.php?item_id=<?= $item['id'] ?>" class="btn btn-sm <?= $em_manutencao ? 'btn-danger' : 'btn-outline-dark' ?>" title="Gerenciar Manutenção">
                                        🛠️ <?= $em_manutencao ? 'Ver Status' : '' ?>
                                    </a>
                                    <a href="editar_item.php?id=<?= $item['id'] ?>" class="btn btn-sm btn-link text-decoration-none">Editar</a>
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
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>