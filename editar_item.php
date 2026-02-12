<?php 
include 'config.php';
$id = $_GET['id'];
$stmt = $pdo->prepare("SELECT * FROM itens WHERE id = ?");
$stmt->execute([$id]);
$item = $stmt->fetch();
?>
<!DOCTYPE html>
<html lang="pt-br">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="style.css">
<head>
    <meta charset="UTF-8">
    <title>Editar Equipamento</title>
    <style>
        body { font-family: sans-serif; padding: 20px; line-height: 1.6; }
        form { background: #eee; padding: 20px; border-radius: 8px; display: inline-block; }
        input, select { display: block; margin-bottom: 10px; padding: 8px; width: 250px; }
    </style>
</head>
<body>
    <h2>Editar Equipamento</h2>
    <form action="acoes.php?acao=editar_item" method="POST">
        <input type="hidden" name="id" value="<?= $item['id'] ?>">
        
        <label>Tipo:</label>
        <select name="tipo" id="tipo" onchange="toggleNome(this.value)">
            <option value="Tela" <?= $item['tipo'] == 'Tela' ? 'selected' : '' ?>>Tela</option>
            <option value="CPU" <?= $item['tipo'] == 'CPU' ? 'selected' : '' ?>>CPU</option>
            <option value="Outros" <?= $item['tipo'] == 'Outros' ? 'selected' : '' ?>>Outros</option>
        </select>

        <div id="campo_nome" style="display: <?= $item['tipo'] == 'Outros' ? 'block' : 'none' ?>;">
            <label>Nome do Item:</label>
            <input type="text" name="nome_personalizado" value="<?= $item['nome_personalizado'] ?>">
        </div>

        <label>Patrimônio / Protocolo:</label>
        <input type="text" name="patrimonio" value="<?= $item['patrimonio_protocolo'] ?>" required>

        <button type="submit" style="background: #007bff; color: white; border: none; padding: 10px; cursor: pointer;">Salvar Alterações</button>
        <a href="index.php">Cancelar</a>
    </form>

    <script>
        function toggleNome(val) {
            document.getElementById('campo_nome').style.display = (val === 'Outros') ? 'block' : 'none';
        }
    </script>
</body>
</html>