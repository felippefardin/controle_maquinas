<?php 
include 'config.php';
$id = $_GET['id'];
$stmt = $pdo->prepare("SELECT * FROM itens WHERE id = ?");
$stmt->execute([$id]);
$item = $stmt->fetch();

// Define a origem para o redirecionamento: se mesa_id for vazio, é 'avulso'
$origem = empty($item['mesa_id']) ? 'avulso' : 'mesa';
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <title>Editar Equipamento</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="style.css">
    <style>
        body { font-family: sans-serif; padding: 20px; line-height: 1.6; }
        form { background: #eee; padding: 20px; border-radius: 8px; display: inline-block; min-width: 350px; }
        input, select { display: block; margin-bottom: 10px; padding: 8px; width: 100%; }
    </style>
</head>
<body>
    <h2>Editar Equipamento</h2>
    <form action="acoes.php?acao=editar_item" method="POST">
        <input type="hidden" name="id" value="<?= $item['id'] ?>">
        <input type="hidden" name="origem" value="<?= $origem ?>">
        
        <label>Tipo:</label>
        <select name="tipo" id="tipo" onchange="toggleNome(this.value)">
            <option value="Tela" <?= $item['tipo'] == 'Tela' ? 'selected' : '' ?>>Tela</option>
            <option value="CPU" <?= $item['tipo'] == 'CPU' ? 'selected' : '' ?>>CPU</option>
            <option value="Outros" <?= $item['tipo'] == 'Outros' ? 'selected' : '' ?>>Outros</option>
        </select>

        <div id="campo_nome" style="display: <?= $item['tipo'] == 'Outros' ? 'block' : 'none' ?>;">
            <label>Nome do Item:</label>
            <input type="text" name="nome_personalizado" value="<?= htmlspecialchars($item['nome_personalizado'] ?? '') ?>">
        </div>

       <label>Vincular à Mesa:</label>
<select name="mesa_id">
    <option value="">Nenhuma (Avulso)</option>
    <?php
    // Corrigido para buscar pela coluna 'nome' conforme a estrutura atual
    $mesas = $pdo->query("SELECT id, nome FROM mesas WHERE status = 'ativo'")->fetchAll();
    foreach($mesas as $m) {
        $selected = ($item['mesa_id'] == $m['id']) ? 'selected' : '';
        // Exibindo 'nome' em vez de 'identificacao'
        echo "<option value='{$m['id']}' {$selected}>" . htmlspecialchars($m['nome']) . "</option>";
    }
    ?>
</select>

        <label>Patrimônio / Protocolo:</label>
        <input type="text" name="patrimonio" value="<?= htmlspecialchars($item['patrimonio_protocolo']) ?>" required>

        <label>IP da Máquina:</label>
        <input type="text" name="ip_maquina" value="<?= htmlspecialchars($item['ip_maquina'] ?? '') ?>">

        <button type="submit" class="btn btn-primary">Salvar Alterações</button>
        <a href="<?= ($origem == 'avulso') ? 'itens_avulsos.php' : 'index.php' ?>" class="btn btn-secondary">Cancelar</a>
    </form>

    <script>
        function toggleNome(val) {
            document.getElementById('campo_nome').style.display = (val === 'Outros') ? 'block' : 'none';
        }
    </script>
</body>
</html>