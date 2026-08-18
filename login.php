<?php
declare(strict_types=1);
define('PAGINA_PUBLICA', true);
require __DIR__ . '/config.php';

if (usuarioLogado() !== null) {
    header('Location: index.php');
    exit;
}

$erro = '';
$destino = destinoSeguro($_GET['destino'] ?? $_POST['destino'] ?? 'index.php');

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
    exigirPostComCsrf();
    $usuario = trim((string) ($_POST['usuario'] ?? ''));
    $senha = (string) ($_POST['senha'] ?? '');
    $stmt = $pdo->prepare('SELECT id, usuario, senha_hash FROM usuarios WHERE usuario = ? LIMIT 1');
    $stmt->execute([$usuario]);
    $registro = $stmt->fetch();

    if ($registro && password_verify($senha, $registro['senha_hash'])) {
        session_regenerate_id(true);
        $_SESSION['usuario_id'] = (int) $registro['id'];
        $_SESSION['usuario_nome'] = (string) $registro['usuario'];
        header('Location: ' . $destino);
        exit;
    }
    usleep(350000);
    $erro = 'Usuário ou senha inválidos.';
}
?>
<!doctype html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Entrar — Controle de Máquinas</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light d-flex align-items-center min-vh-100">
<main class="container" style="max-width: 430px">
    <div class="card border-0 shadow rounded-4"><div class="card-body p-4 p-md-5">
        <h1 class="h3 fw-bold mb-1">Controle de Máquinas</h1>
        <p class="text-muted mb-4">Entre com seu usuário e senha.</p>
        <?php if ($erro): ?><div class="alert alert-danger" role="alert"><?= e($erro) ?></div><?php endif; ?>
        <form method="POST">
            <?= csrfField() ?>
            <input type="hidden" name="destino" value="<?= e($destino) ?>">
            <div class="mb-3"><label class="form-label" for="usuario">Usuário</label><input class="form-control form-control-lg" id="usuario" name="usuario" autocomplete="username" required autofocus></div>
            <div class="mb-4"><label class="form-label" for="senha">Senha</label><input class="form-control form-control-lg" type="password" id="senha" name="senha" autocomplete="current-password" required></div>
            <button class="btn btn-primary btn-lg w-100">Entrar</button>
            <a href="recuperar_senha.php" class="btn btn-link w-100 mt-2">Esqueci minha senha</a>
        </form>
    </div></div>
</main>
</body>
</html>
