<?php include 'config.php'; ?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <title>Itens Avulsos | Controle</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body { font-family: 'Inter', sans-serif; background-color: #f8fafc; }
        .card { border: none; border-radius: 12px; box-shadow: 0 4px 6px -1px rgba(0,0,0,0.1); }
        .table { border-radius: 12px; overflow: hidden; }
        .table thead { background: #2d3436; color: white; }
        .btn-rounded { border-radius: 8px; padding: 8px 16px; }
    </style>
</head>
<body class="p-4">
    <div class="container py-4">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h3 class="fw-bold text-dark">📦 Itens Avulsos</h3>
            <a href="index.php" class="btn btn-outline-secondary btn-rounded">⬅ Voltar ao Painel</a>
        </div>
        
        <div class="card mb-4">
            <div class="card-body p-3">
                <form action="itens_avulsos.php" method="GET" class="d-flex gap-2">
                    <input type="text" name="busca" class="form-control" placeholder="Buscar por patrimônio ou nome..." value="<?= htmlspecialchars($_GET['busca'] ?? '') ?>">
                    <button type="submit" class="btn btn-primary">🔍 Buscar</button>
                    <?php if(!empty($_GET['busca'])): ?>
                        <a href="itens_avulsos.php" class="btn btn-link">Limpar</a>
                    <?php endif; ?>
                </form>
            </div>
        </div>
        
        <div class="card mb-5">
            <div class="card-body p-4">
                <form action="acoes.php?acao=adicionar_item" method="POST" class="row g-3 align-items-end">
                    <input type="hidden" name="mesa_id" value=""> 
                    
                    <div class="col-md-3">
                        <label class="form-label small fw-semibold text-muted">Tipo de Equipamento</label>
                        <select name="tipo" class="form-select border-2" id="select_tipo" onchange="toggleNome(this.value)">
                            <option value="Tela">Tela</option>
                            <option value="CPU">CPU</option>                            
                            <option value="Outros">Outros</option>
                        </select>
                    </div>
                    
                    <div class="col-md-3" id="campo_nome_personalizado" style="display:none">
                        <label class="form-label small fw-semibold text-muted">Nome do Aparelho</label>
                        <input type="text" name="nome_personalizado" id="input_nome" class="form-control border-2">
                    </div>
                    
                    <div class="col-md-4">
                        <label class="form-label small fw-semibold text-muted">Patrimônio / Protocolo</label>
                        <input type="text" name="patrimonio" class="form-control border-2" required placeholder="Digite o código...">
                    </div>
                    
                    <div class="col-md-2">
                        <button type="submit" class="btn btn-primary w-100 fw-bold btn-rounded shadow-sm">Adicionar</button>
                    </div>
                </form>
            </div>
        </div>

        <div class="card">
            <div class="card-body p-0">
                <table class="table align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th class="ps-4">Tipo</th>
                            <th>Patrimônio</th>
                            <th>Situação</th>
                            <th class="text-end pe-4">Ações</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
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
                        
                        while ($item = $stmt->fetch()): ?>
                        <tr>
                            <td class="ps-4 fw-semibold text-secondary">
                                <?= e($item['tipo'] == 'Outros' ? $item['nome_personalizado'] : $item['tipo']) ?>
                            </td>
                            <td><span class="badge bg-light text-dark border"><?= htmlspecialchars($item['patrimonio_protocolo']) ?></span></td>
                            <td>
                                <?php if (!empty($item['manutencao_ativa_id'])): ?>
                                    <span class="badge bg-warning text-dark">
                                        Em uso na mesa <?= htmlspecialchars($item['mesa_em_uso'] ?? 'não identificada') ?>
                                    </span>
                                <?php else: ?>
                                    <span class="badge bg-success">Disponível</span>
                                <?php endif; ?>
                            </td>
                            <td class="text-end pe-4">
                                <?php if (!empty($item['manutencao_ativa_id'])): ?>
                                    <button type="button" class="btn btn-sm btn-outline-secondary btn-rounded" disabled
                                            title="O equipamento está em uso temporário">
                                        Em uso
                                    </button>
                                <?php else: ?>
                                    <a href="editar_item.php?id=<?= $item['id'] ?>" class="btn btn-sm btn-outline-warning btn-rounded">Editar</a>
                                    <form action="acoes.php?acao=remover_item" method="POST" class="d-inline" onsubmit="return confirm('Confirmar exclusão?')">
                                        <input type="hidden" name="id" value="<?= (int) $item['id'] ?>">
                                        <button class="btn btn-sm btn-outline-danger btn-rounded">Excluir</button>
                                    </form>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

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
    </script>
</body>
</html>
