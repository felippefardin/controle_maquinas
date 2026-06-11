<?php 
include 'config.php'; 
include 'header.php'; 

$item_id = $_GET['item_id'];

$stmt = $pdo->prepare("SELECT i.*, m.identificacao as mesa_nome FROM itens i JOIN mesas m ON i.mesa_id = m.id WHERE i.id = ?");
$stmt->execute([$item_id]);
$item = $stmt->fetch();

$stmt_m = $pdo->prepare("SELECT * FROM manutencoes WHERE item_id = ? AND status_manutencao = 'Aberto' LIMIT 1");
$stmt_m->execute([$item_id]);
$manutencao = $stmt_m->fetch();
?>

<div class="row justify-content-center">
    <div class="col-md-8">
        <div class="card shadow-sm border-0 rounded-4">
            <div class="card-header bg-dark text-white d-flex justify-content-between align-items-center py-3 px-4 rounded-top-4">
                <h5 class="mb-0 fw-bold">🛠️ Registro de Manutenção</h5>
                <a href="index.php" class="btn btn-sm btn-outline-light rounded-pill px-3">Voltar ao Painel</a>
            </div>
            
            <div class="card-body p-4">
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <div>
                        <h4 class="fw-bold mb-1"><?= htmlspecialchars($item['tipo']) ?></h4>
                        <span class="badge bg-secondary rounded-pill font-monospace"><?= htmlspecialchars($item['patrimonio_protocolo']) ?></span>
                    </div>
                    <div class="text-end">
                        <small class="text-muted d-block">Local de Origem</small>
                        <span class="fw-semibold"><?= htmlspecialchars($item['mesa_nome']) ?></span>
                    </div>
                </div>

                <hr class="my-4">

                <?php if (!$manutencao): ?>
                    <form action="acoes.php?acao=iniciar_manutencao" method="POST">
                        <input type="hidden" name="item_id" value="<?= $item_id ?>">
                        <div class="mb-4">
                            <label class="form-label fw-bold text-dark">Relato Inicial do Problema:</label>
                            <textarea name="problema" class="form-control rounded-3 border-2" rows="3" required placeholder="Descreva o defeito encontrado..."></textarea>
                        </div>
                        <button type="submit" class="btn btn-danger w-100 fw-bold py-2 rounded-pill shadow-sm">Abrir Ordem de Serviço</button>
                    </form>
                <?php else: ?>
                    <div class="alert alert-warning border-0 rounded-3 mb-4 shadow-sm">
                        <small class="text-uppercase fw-bold text-warning-emphasis">Problema Inicial:</small>
                        <p class="mb-0 mt-1"><?= nl2br(htmlspecialchars($manutencao['descricao_problema'])) ?></p>
                    </div>

                    <div class="mb-4">
                        <h6 class="fw-bold text-secondary mb-3">📜 Histórico de Movimentações:</h6>
                        <div class="bg-light border p-3 rounded-3 mb-3 shadow-inner" style="max-height: 200px; overflow-y: auto;">
                            <?= $manutencao['movimentacoes'] ? nl2br(htmlspecialchars($manutencao['movimentacoes'])) : '<span class="text-muted fst-italic">Nenhuma movimentação registrada.</span>' ?>
                        </div>

                        <form action="acoes.php?acao=registrar_movimento" method="POST" class="bg-white p-3 border rounded-3 shadow-sm">
                            <input type="hidden" name="manutencao_id" value="<?= $manutencao['id'] ?>">
                            <input type="hidden" name="item_id" value="<?= $item_id ?>">
                            <div class="mb-2">
                                <label class="form-label small fw-bold text-muted">Adicionar Atualização:</label>
                                <textarea name="movimento" class="form-control form-control-sm border-2" rows="2" required placeholder="Ex: Peça encomendada..."></textarea>
                            </div>
                            <button type="submit" class="btn btn-sm btn-primary rounded-pill px-4">Adicionar Movimento</button>
                        </form>
                    </div>

                    <form action="acoes.php?acao=concluir_manutencao" method="POST" class="mt-4 border-top pt-4">
                        <input type="hidden" name="item_id" value="<?= $item_id ?>">
                        <input type="hidden" name="manutencao_id" value="<?= $manutencao['id'] ?>">
                        <button type="submit" class="btn btn-success w-100 btn-lg rounded-pill shadow-sm" onclick="return confirm('O aparelho está pronto para voltar à mesa?')">
                            ✅ Concluir Manutenção
                        </button>
                    </form>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<?php include 'footer.php'; ?>