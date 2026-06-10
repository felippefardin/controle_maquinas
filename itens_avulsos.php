<?php 
include 'config.php';
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <title>Itens Avulsos</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light p-4">
    <div class="container">
        <a href="index.php" class="btn btn-secondary mb-3">⬅ Voltar ao Painel</a>
        <h3>Gerenciamento de Itens Avulsos</h3>
        
        <div class="card mb-4">
            <div class="card-body">
                <form action="acoes.php?acao=adicionar_item" method="POST">
                    <input type="hidden" name="mesa_id" value=""> 
                    <div class="row g-3 align-items-end">
                        <div class="col-md-3">
                            <label class="form-label small fw-bold">Tipo:</label>
                            <select name="tipo" class="form-select" id="select_tipo" onchange="toggleNome(this.value)">
                                <option value="Tela">Tela</option>
                                <option value="CPU">CPU</option>
                                <option value="Outros">Outros</option>
                            </select>
                        </div>
                        <div class="col-md-3" id="campo_nome_personalizado" style="display:none">
                            <label class="form-label small fw-bold">Nome do Aparelho:</label>
                            <input type="text" name="nome_personalizado" id="input_nome" class="form-control">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label small fw-bold">Patrimônio/Protocolo:</label>
                            <input type="text" name="patrimonio" class="form-control" required>
                        </div>
                        <div class="col-md-2">
                            <button type="submit" class="btn btn-primary w-100">Adicionar</button>
                        </div>
                    </div>
                </form>
            </div>
        </div>

        <table class="table table-striped table-hover bg-white shadow-sm border">
            <thead class="table-dark">
                <tr>
                    <th>Tipo</th>
                    <th>Patrimônio</th>
                    <th>Ações</th>
                </tr>
            </thead>
            <tbody>
                <?php
                $stmt = $pdo->query("SELECT * FROM itens WHERE mesa_id IS NULL");
                while ($item = $stmt->fetch()): ?>
                <tr>
                    <td><?= $item['tipo'] == 'Outros' ? htmlspecialchars($item['nome_personalizado']) : $item['tipo'] ?></td>
                    <td><?= htmlspecialchars($item['patrimonio_protocolo']) ?></td>
                    <td>
                        <a href="editar_item.php?id=<?= $item['id'] ?>" class="btn btn-sm btn-warning">Editar</a>
                        <a href="acoes.php?acao=remover_item&id=<?= $item['id'] ?>" class="btn btn-sm btn-danger" onclick="return confirm('Confirmar exclusão?')">Excluir</a>
                    </td>
                </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
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