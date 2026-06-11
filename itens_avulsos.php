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
        
        <div class="card mb-5">
            <div class="card-body p-4">
                <form action="acoes.php?acao=adicionar_item" method="POST" class="row g-3 align-items-end">
                    <input type="hidden" name="mesa_id" value=""> 
                    
                    <div class="col-md-3">
                        <label class="form-label small fw-semibold text-muted">Tipo de Equipamento</label>
                        <select name="tipo" class="form-select border-2" id="select_tipo" onchange="toggleNome(this.value)">
                            <option value="Tela">Tela</option>
                            <option value="CPU">CPU</option>
                            <option value="Piso">Piso</option>
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
                            <th class="text-end pe-4">Ações</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        $stmt = $pdo->query("SELECT * FROM itens WHERE mesa_id IS NULL");
                        while ($item = $stmt->fetch()): ?>
                        <tr>
                            <td class="ps-4 fw-semibold text-secondary">
                                <?= $item['tipo'] == 'Outros' ? htmlspecialchars($item['nome_personalizado']) : $item['tipo'] ?>
                            </td>
                            <td><span class="badge bg-light text-dark border"><?= htmlspecialchars($item['patrimonio_protocolo']) ?></span></td>
                            <td class="text-end pe-4">
                                <a href="editar_item.php?id=<?= $item['id'] ?>" class="btn btn-sm btn-outline-warning btn-rounded">Editar</a>
                                <a href="acoes.php?acao=remover_item&id=<?= $item['id'] ?>" class="btn btn-sm btn-outline-danger btn-rounded" onclick="return confirm('Confirmar exclusão?')">Excluir</a>
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