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

$stmtAvulsos = $pdo->query("
    SELECT id, tipo, nome_personalizado, patrimonio_protocolo
    FROM itens
    WHERE mesa_id IS NULL
      AND status = 'Ativo'
    ORDER BY tipo, patrimonio_protocolo
");
$itensAvulsos = $stmtAvulsos->fetchAll();
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gerenciar Mesa <?= htmlspecialchars($mesa['nome']) ?></title>
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
                    <h5 class="mb-0">📍 Gerenciar Mesa: <?= htmlspecialchars($mesa['nome']) ?></h5>
                </div>
                <!-- <div class="mb-3">
                    <label for="ip_maquina" class="form-label fw-bold">Endereço IP (Opcional)</label>
                    <input type="text" name="ip_maquina" id="ip_maquina" class="form-control" placeholder="Ex: 192.168.1.100">
                </div> -->
                <div class="card-body p-4">
                    <h6 class="card-subtitle mb-4 text-muted">Adicionar equipamento à mesa</h6>
                    
                    <form action="acoes.php?acao=adicionar_item" method="POST">
                        <?= csrfField() ?>
                        <input type="hidden" name="mesa_id" value="<?= $mesa_id ?>">

                        <div class="mb-4">
                            <label class="form-label fw-bold d-block">Como deseja adicionar?</label>
                            <div class="form-check">
                                <input class="form-check-input" type="radio" name="origem_item" id="origem_novo" value="novo" checked onchange="toggleOrigemItem()">
                                <label class="form-check-label" for="origem_novo">Digitar um novo patrimônio</label>
                            </div>
                            <div class="form-check">
                                <input class="form-check-input" type="radio" name="origem_item" id="origem_avulso" value="avulso" onchange="toggleOrigemItem()" <?= empty($itensAvulsos) ? 'disabled' : '' ?>>
                                <label class="form-check-label" for="origem_avulso">Usar um equipamento dos itens avulsos</label>
                            </div>
                        </div>

                        <div id="campos_novo_item">

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

                        </div>

                        <div class="mb-4" id="campo_item_avulso" style="display:none">
                            <label for="item_avulso_id" class="form-label fw-bold">Equipamento avulso</label>
                            <select name="item_avulso_id" id="item_avulso_id" class="form-select">
                                <option value="">Selecione um equipamento</option>
                                <?php foreach ($itensAvulsos as $itemAvulso): ?>
                                    <option value="<?= (int) $itemAvulso['id'] ?>">
                                        <?= htmlspecialchars(
                                            ($itemAvulso['tipo'] === 'Outros' && !empty($itemAvulso['nome_personalizado'])
                                                ? $itemAvulso['nome_personalizado']
                                                : $itemAvulso['tipo'])
                                            . ' — Patrimônio: ' . $itemAvulso['patrimonio_protocolo']
                                        ) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                            <?php if (empty($itensAvulsos)): ?>
                                <div class="form-text">Não há itens avulsos disponíveis no momento.</div>
                            <?php endif; ?>
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
                O item será vinculado automaticamente à <strong><?= htmlspecialchars($mesa['nome']) ?></strong>.
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

    function toggleOrigemItem() {
        const usarAvulso = document.getElementById('origem_avulso').checked;
        const camposNovo = document.getElementById('campos_novo_item');
        const campoAvulso = document.getElementById('campo_item_avulso');
        const patrimonio = document.getElementById('patrimonio');
        const itemAvulso = document.getElementById('item_avulso_id');

        camposNovo.style.display = usarAvulso ? 'none' : 'block';
        campoAvulso.style.display = usarAvulso ? 'block' : 'none';
        patrimonio.required = !usarAvulso;
        itemAvulso.required = usarAvulso;
    }
</script>

</body>
</html>
