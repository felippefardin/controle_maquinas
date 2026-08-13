<?php 
include 'config.php'; 
include 'header.php'; 

$item_id = isset($_GET['item_id']) ? (int) $_GET['item_id'] : 0;

$stmt = $pdo->prepare("SELECT i.*, m.nome as mesa_nome FROM itens i LEFT JOIN mesas m ON i.mesa_id = m.id WHERE i.id = ?");
$stmt->execute([$item_id]);
$item = $stmt->fetch();

$stmt_m = $pdo->prepare("SELECT * FROM manutencoes WHERE item_id = ? AND status_manutencao = 'Aberto' LIMIT 1");
$stmt_m->execute([$item_id]);
$manutencao = $stmt_m->fetch();

$stmt_avulsos = $pdo->query("
    SELECT id, tipo, nome_personalizado, patrimonio_protocolo
    FROM itens
    WHERE mesa_id IS NULL AND status = 'Ativo'
    ORDER BY tipo, patrimonio_protocolo
");
$itens_avulsos = $stmt_avulsos->fetchAll();

$anexos = [];
$substituto = null;
if ($manutencao) {
    $stmt_a = $pdo->prepare("SELECT * FROM manutencao_anexos WHERE manutencao_id = ? ORDER BY data_upload DESC, id DESC");
    $stmt_a->execute([$manutencao['id']]);
    $anexos = $stmt_a->fetchAll();

    if (!empty($manutencao['substituto_item_id'])) {
        $stmt_s = $pdo->prepare("SELECT * FROM itens WHERE id = ?");
        $stmt_s->execute([$manutencao['substituto_item_id']]);
        $substituto = $stmt_s->fetch();
    }
}
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
                        <span class="fw-semibold">
    <?= !empty($item['mesa_nome']) ? htmlspecialchars($item['mesa_nome']) : 'Avulso' ?>
</span>
                    </div>
                </div>

                <hr class="my-4">

                <?php if (!$manutencao): ?>
                    <form action="acoes.php?acao=iniciar_manutencao" method="POST" enctype="multipart/form-data">
                        <input type="hidden" name="item_id" value="<?= $item_id ?>">
                        <div class="mb-4">
                            <label class="form-label fw-bold text-dark">Relato Inicial do Problema:</label>
                            <textarea name="problema" class="form-control rounded-3 border-2" rows="3" required placeholder="Descreva o defeito encontrado..."></textarea>
                        </div>
                        <?php if (!empty($item['mesa_id'])): ?>
                            <div class="mb-4">
                                <label class="form-label fw-bold text-dark">Equipamento substituto (opcional):</label>
                                <select name="substituto_item_id" id="substituto_item_id"
                                        class="form-select rounded-3 border-2"
                                        onchange="alternarIpSubstituto()">
                                    <option value="">Não usar substituto</option>
                                    <?php foreach ($itens_avulsos as $avulso): ?>
                                        <option value="<?= $avulso['id'] ?>">
                                            <?= htmlspecialchars($avulso['tipo'] === 'Outros' ? $avulso['nome_personalizado'] : $avulso['tipo']) ?>
                                            — <?= htmlspecialchars($avulso['patrimonio_protocolo']) ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                                <div class="form-text">O item avulso aparecerá nesta mesa até a manutenção ser concluída.</div>
                            </div>
                            <div class="mb-4">
                                <label class="form-label fw-bold text-dark">IP do equipamento substituto (opcional):</label>
                                <input type="text" name="substituto_ip_maquina" id="substituto_ip_maquina"
                                       class="form-control rounded-3 border-2"
                                       placeholder="Ex.: 172.17.16.45" disabled>
                                <div class="form-text">Se não informar, o IP já cadastrado no item avulso será mantido.</div>
                            </div>
                        <?php endif; ?>
                        <div class="mb-4">
                            <label class="form-label fw-bold text-dark">Documentos da manutenção (opcional):</label>
                            <input type="file" name="documentos[]" class="form-control rounded-3 border-2" multiple
                                   accept=".pdf,.doc,.docx,.xls,.xlsx,.csv,.txt,.jpg,.jpeg,.png">
                            <div class="form-text">Selecione vários arquivos de uma vez. Limite de 10 MB por arquivo.</div>
                        </div>
                        <button type="submit" class="btn btn-danger w-100 fw-bold py-2 rounded-pill shadow-sm">Abrir Ordem de Serviço</button>
                    </form>
                <?php else: ?>
                    <div class="alert alert-warning border-0 rounded-3 mb-4 shadow-sm">
                        <small class="text-uppercase fw-bold text-warning-emphasis">Problema Inicial:</small>
                        <p class="mb-0 mt-1"><?= nl2br(htmlspecialchars($manutencao['descricao_problema'])) ?></p>
                    </div>

                    <?php if ($substituto): ?>
                        <div class="alert alert-info border-0 rounded-3 mb-4 shadow-sm">
                            <small class="text-uppercase fw-bold">Substituto temporário na mesa:</small>
                            <p class="mb-0 mt-1">
                                <?= htmlspecialchars($substituto['tipo'] === 'Outros' ? $substituto['nome_personalizado'] : $substituto['tipo']) ?>
                                — <?= htmlspecialchars($substituto['patrimonio_protocolo']) ?>
                            </p>
                        </div>
                    <?php endif; ?>

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

                    <div class="mb-4">
                        <h6 class="fw-bold text-secondary mb-3">📎 Documentos anexados:</h6>
                        <?php if ($anexos): ?>
                            <div class="list-group mb-3">
                                <?php foreach ($anexos as $anexo): ?>
                                    <a class="list-group-item list-group-item-action d-flex justify-content-between align-items-center"
                                       href="download_anexo.php?id=<?= $anexo['id'] ?>"
                                       target="_blank" rel="noopener"
                                       title="Abrir documento em uma nova aba">
                                        <span><?= htmlspecialchars($anexo['nome_original']) ?></span>
                                        <small class="text-muted"><?= number_format($anexo['tamanho'] / 1024, 1, ',', '.') ?> KB</small>
                                    </a>
                                <?php endforeach; ?>
                            </div>
                        <?php else: ?>
                            <p class="text-muted small">Nenhum documento anexado.</p>
                        <?php endif; ?>

                        <form action="acoes.php?acao=anexar_documentos" method="POST" enctype="multipart/form-data"
                              class="bg-white p-3 border rounded-3 shadow-sm">
                            <input type="hidden" name="manutencao_id" value="<?= $manutencao['id'] ?>">
                            <input type="hidden" name="item_id" value="<?= $item_id ?>">
                            <label class="form-label small fw-bold text-muted">Adicionar documentos:</label>
                            <input type="file" name="documentos[]" class="form-control form-control-sm mb-2" multiple required
                                   accept=".pdf,.doc,.docx,.xls,.xlsx,.csv,.txt,.jpg,.jpeg,.png">
                            <button type="submit" class="btn btn-sm btn-outline-primary rounded-pill px-4">Anexar arquivos</button>
                        </form>
                    </div>

                    <form action="acoes.php?acao=concluir_manutencao" method="POST" class="mt-4 border-top pt-4">
                        <input type="hidden" name="item_id" value="<?= $item_id ?>">
                        <input type="hidden" name="manutencao_id" value="<?= $manutencao['id'] ?>">
                        <?php if ($substituto): ?>
                            <div class="alert alert-success border-0 rounded-3 text-center">
                                <h6 class="fw-bold mb-2">Ao concluir, qual equipamento deve permanecer na mesa?</h6>
                                <p class="small mb-0">Escolha entre permanecer com o equipamento atual ou retornar com o equipamento anterior.</p>
                            </div>
                            <div class="row g-2">
                                <div class="col-md-6">
                                    <button type="submit" name="decisao" value="manter_atual"
                                            class="btn btn-primary w-100 py-3 rounded-3 shadow-sm"
                                            onclick="return confirm('Confirmar que o equipamento atual permanecerá na mesa?')">
                                        Permanecer com o atual
                                    </button>
                                </div>
                                <div class="col-md-6">
                                    <button type="submit" name="decisao" value="retornar_anterior"
                                            class="btn btn-success w-100 py-3 rounded-3 shadow-sm"
                                            onclick="return confirm('Confirmar o retorno do equipamento anterior para a mesa?')">
                                        Retornar equipamento anterior
                                    </button>
                                </div>
                            </div>
                        <?php else: ?>
                            <button type="submit" name="decisao" value="retornar_anterior"
                                    class="btn btn-success w-100 btn-lg rounded-pill shadow-sm"
                                    onclick="return confirm('O aparelho está pronto para voltar à mesa?')">
                                ✅ Concluir Manutenção
                            </button>
                        <?php endif; ?>
                    </form>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<script>
function alternarIpSubstituto() {
    const seletor = document.getElementById('substituto_item_id');
    const campoIp = document.getElementById('substituto_ip_maquina');
    if (!seletor || !campoIp) return;

    campoIp.disabled = seletor.value === '';
    if (campoIp.disabled) campoIp.value = '';
}
</script>

<?php include 'footer.php'; ?>
