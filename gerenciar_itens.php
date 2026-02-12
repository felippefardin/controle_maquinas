<?php 
include 'config.php';
$mesa_id = $_GET['mesa_id'];
$stmt = $pdo->prepare("SELECT * FROM mesas WHERE id = ?");
$stmt->execute([$mesa_id]);
$mesa = $stmt->fetch();


if (!$mesa) {
    header("Location: index.php");
    exit;
}
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gerenciar Mesa <?= htmlspecialchars($mesa['identificacao']) ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="style.css">
</head>
<body class="bg-light">

<div class="container mt-5">
    <div class="row justify-content-center">
        <div class="col-md-6">
            
            <div class="mb-4">
                <a href="index.php" class="btn btn-outline-secondary btn-sm">
                    ⬅ Voltar para o Painel
                </a>
            </div>

            <div class="card shadow-sm border-0">
                <div class="card-header bg-primary text-white py-3">
                    <h5 class="mb-0">📍 Gerenciar Mesa: <?= htmlspecialchars($mesa['identificacao']) ?></h5>
                </div>
                <div class="card-body p-4">
                    <h6 class="card-subtitle mb-4 text-muted">Adicionar novo equipamento à mesa</h6>
                    
                    <form action="acoes.php?acao=adicionar_item" method="POST">
                        <input type="hidden" name="mesa_id" value="<?= $mesa_id ?>">

                        <div class="mb-3">
                            <label for="tipo" class="form-label fw-bold">Tipo de Equipamento</label>
                            <select name="tipo" id="tipo" class="form-select" onchange="toggleNome(this.value)">
                                <option value="Tela">Tela</option>
                                <option value="CPU">CPU</option>
                                <option value="Outros">Outros</option>
                            </select>
                        </div>

                        <div class="mb-3" id="campo_nome_personalizado" style="display:none">
                            <label for="nome_personalizado" class="form-label fw-bold">Nome do Item</label>
                            <input type="text" name="nome_personalizado" id="nome_personalizado" class="form-control" placeholder="Ex: Teclado, Impressora...">
                        </div>

                        <div class="mb-4">
                            <label for="patrimonio" class="form-label fw-bold">Número de Patrimônio / Protocolo</label>
                            <input type="text" name="patrimonio" id="patrimonio" class="form-control" placeholder="Digite o código" required>
                        </div>

                        <div class="d-grid">
                            <button type="submit" class="btn btn-success btn-lg">
                                Confirmar e Adicionar
                            </button>
                        </div>
                    </form>
                </div>
            </div>

            <div class="text-center mt-4 text-muted small">
                O item será vinculado automaticamente à <strong><?= htmlspecialchars($mesa['identificacao']) ?></strong>.
            </div>

        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

<script>
    function toggleNome(val) {
        const campoNome = document.getElementById('campo_nome_personalizado');
        const inputNome = document.getElementById('nome_personalizado');
        
        if (val === 'Outros') {
            campoNome.style.display = 'block';
            inputNome.setAttribute('required', 'required'); 
        } else {
            campoNome.style.display = 'none';
            inputNome.removeAttribute('required');
            inputNome.value = ''; 
        }
    }
</script>

</body>
</html>