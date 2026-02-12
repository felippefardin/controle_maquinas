<?php 
include 'config.php';
$item_id = $_GET['item_id'];

$stmt = $pdo->prepare("SELECT i.*, m.identificacao as mesa_nome FROM itens i JOIN mesas m ON i.mesa_id = m.id WHERE i.id = ?");
$stmt->execute([$item_id]);
$item = $stmt->fetch();

$stmt_m = $pdo->prepare("SELECT * FROM manutencoes WHERE item_id = ? AND status_manutencao = 'Aberto' LIMIT 1");
$stmt_m->execute([$item_id]);
$manutencao = $stmt_m->fetch();
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <title>Manutenção - <?= $item['patrimonio_protocolo'] ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light py-5">
<div class="container">
    <div class="row justify-content-center">
        <div class="col-md-8">
            <div class="card shadow border-0">
                <div class="card-header bg-dark text-white d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">🛠️ Registro de Manutenção</h5>
                    <a href="index.php" class="btn btn-sm btn-outline-light">Voltar</a>
                </div>
                <div class="card-body">
                    <h5><?= $item['tipo'] ?> <small class="text-muted">(<?= $item['patrimonio_protocolo'] ?>)</small></h5>
                    <p class="text-muted small">Local de Origem: <?= $item['mesa_nome'] ?></p>
                    <hr>

                    <?php if (!$manutencao): ?>
                        <form action="acoes.php?acao=iniciar_manutencao" method="POST">
                            <input type="hidden" name="item_id" value="<?= $item_id ?>">
                            <div class="mb-3">
                                <label class="form-label fw-bold">Relato Inicial do Problema:</label>
                                <textarea name="problema" class="form-control" rows="3" required placeholder="Descreva o que aconteceu..."></textarea>
                            </div>
                            <button type="submit" class="btn btn-danger w-100">Abrir Ordem de Serviço</button>
                        </form>
                    <?php else: ?>
                        <div class="alert alert-warning">
                            <strong>Problema Inicial:</strong><br><?= nl2br(htmlspecialchars($manutencao['descricao_problema'])) ?>
                        </div>

                        <div class="mb-4">
                            <h6>📜 Histórico de Movimentações:</h6>
                            <div class="bg-white border p-3 rounded mb-3" style="max-height: 200px; overflow-y: auto;">
                                <?= $manutencao['movimentacoes'] ? nl2br(htmlspecialchars($manutencao['movimentacoes'])) : '<span class="text-muted italic">Nenhuma movimentação registrada.</span>' ?>
                            </div>

                            <form action="acoes.php?acao=registrar_movimento" method="POST" class="bg-light p-3 border rounded">
                                <input type="hidden" name="manutencao_id" value="<?= $manutencao['id'] ?>">
                                <input type="hidden" name="item_id" value="<?= $item_id ?>">
                                <div class="mb-2">
                                    <label class="form-label small fw-bold">Adicionar Atualização (Ex: "Peça comprada", "Aguardando técnico"):</label>
                                    <textarea name="movimento" class="form-control form-control-sm" rows="2" required></textarea>
                                </div>
                                <button type="submit" class="btn btn-sm btn-primary">Adicionar Movimento</button>
                            </form>
                        </div>

                        <form action="acoes.php?acao=concluir_manutencao" method="POST" class="mt-4 border-top pt-3">
                            <input type="hidden" name="item_id" value="<?= $item_id ?>">
                            <input type="hidden" name="manutencao_id" value="<?= $manutencao['id'] ?>">
                            <button type="submit" class="btn btn-success w-100 btn-lg" onclick="return confirm('O aparelho está pronto para voltar à mesa?')">Concluir Manutenção</button>
                        </form>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>
</body>
</html>