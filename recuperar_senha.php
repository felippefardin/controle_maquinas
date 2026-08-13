<?php
declare(strict_types=1);
define('PAGINA_PUBLICA', true);
require __DIR__ . '/config.php';

$erro = '';
$sucesso = '';
if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
    exigirPostComCsrf();
    $usuario = trim((string) ($_POST['usuario'] ?? ''));
    $codigo = strtoupper(trim((string) ($_POST['codigo'] ?? '')));
    $senha = (string) ($_POST['senha'] ?? '');
    $confirmacao = (string) ($_POST['confirmar_senha'] ?? '');

    if (strlen($senha) < 8) {
        $erro = 'A nova senha deve ter no mínimo 8 caracteres.';
    } elseif ($senha !== $confirmacao) {
        $erro = 'A confirmação da senha não confere.';
    } else {
        $stmt = $pdo->prepare('SELECT id, codigo_recuperacao_hash FROM usuarios WHERE usuario = ? LIMIT 1');
        $stmt->execute([$usuario]);
        $registro = $stmt->fetch();
        if (!$registro || !$registro['codigo_recuperacao_hash'] || !password_verify($codigo, $registro['codigo_recuperacao_hash'])) {
            usleep(350000);
            $erro = 'Usuário ou código de recuperação inválido.';
        } else {
            $novoCodigo = strtoupper(bin2hex(random_bytes(4)));
            $stmt = $pdo->prepare('UPDATE usuarios SET senha_hash = ?, codigo_recuperacao_hash = ? WHERE id = ?');
            $stmt->execute([password_hash($senha, PASSWORD_DEFAULT), password_hash($novoCodigo, PASSWORD_DEFAULT), $registro['id']]);
            $sucesso = "Senha alterada. Seu novo código de recuperação é {$novoCodigo}. Guarde-o; o código usado foi invalidado.";
        }
    }
}
?>
<!doctype html><html lang="pt-br"><head><meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1"><title>Recuperar senha</title><link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet"></head>
<body class="bg-light d-flex align-items-center min-vh-100"><main class="container" style="max-width:480px"><div class="card border-0 shadow rounded-4"><div class="card-body p-4 p-md-5"><h1 class="h3 fw-bold">Recuperar senha</h1><p class="text-muted">Informe o código de recuperação fornecido quando o login foi criado.</p>
<?php if ($erro): ?><div class="alert alert-danger"><?= e($erro) ?></div><?php endif; ?><?php if ($sucesso): ?><div class="alert alert-success"><?= e($sucesso) ?></div><a class="btn btn-primary w-100" href="login.php">Ir para o login</a><?php else: ?>
<form method="POST"><?= csrfField() ?><div class="mb-3"><label class="form-label">Usuário</label><input class="form-control" name="usuario" required autofocus></div><div class="mb-3"><label class="form-label">Código de recuperação</label><input class="form-control text-uppercase" name="codigo" maxlength="32" required></div><div class="mb-3"><label class="form-label">Nova senha</label><input class="form-control" type="password" name="senha" minlength="8" required></div><div class="mb-4"><label class="form-label">Confirmar nova senha</label><input class="form-control" type="password" name="confirmar_senha" minlength="8" required></div><button class="btn btn-primary w-100">Alterar senha</button><a href="login.php" class="btn btn-link w-100 mt-2">Voltar</a></form><?php endif; ?>
</div></div></main></body></html>
