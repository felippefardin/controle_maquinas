<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Controle de Máquinas</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="style.css">
    <meta name="referrer" content="same-origin">
</head>
<body class="bg-light">
<div class="container py-4">
<div class="d-flex justify-content-end align-items-center gap-2 mb-3 small">
    <span class="text-muted">Usuário: <strong><?= e(usuarioLogado()) ?></strong></span>
    <a href="alterar_senha.php" class="btn btn-sm btn-outline-primary">Alterar senha</a>
    <a href="usuarios.php" class="btn btn-sm btn-outline-secondary">Gerenciar logins</a>
    <form action="logout.php" method="POST" class="d-inline">
        <?= csrfField() ?>
        <button class="btn btn-sm btn-outline-danger">Sair</button>
    </form>
</div>
