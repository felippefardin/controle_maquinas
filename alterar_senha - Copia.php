<?php
declare(strict_types=1);
require __DIR__ . '/config.php';

$erro = '';
$sucesso = '';
if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
    exigirPostComCsrf();
    $senhaAtual = (string) ($_POST['senha_atual'] ?? '');
    $novaSenha = (string) ($_POST['nova_senha'] ?? '');
    $confirmacao = (string) ($_POST['confirmar_senha'] ?? '');

    $stmt = $pdo->prepare('SELECT senha_hash FROM usuarios WHERE id = ? LIMIT 1');
    $stmt->execute([(int) ($_SESSION['usuario_id'] ?? 0)]);
    $senhaHash = $stmt->fetchColumn();

    if (!$senhaHash || !password_verify($senhaAtual, (string) $senhaHash)) {
        usleep(350000);
        $erro = 'A senha atual está incorreta.';
    } elseif (strlen($novaSenha) < 8) {
        $erro = 'A nova senha deve ter no mínimo 8 caracteres.';
    } elseif ($novaSenha !== $confirmacao) {
        $erro = 'A confirmação da nova senha não confere.';
    } elseif (password_verify($novaSenha, (string) $senhaHash)) {
        $erro = 'A nova senha deve ser diferente da senha atual.';
    } else {
        $stmt = $pdo->prepare('UPDATE usuarios SET senha_hash = ? WHERE id = ?');
        $stmt->execute([password_hash($novaSenha, PASSWORD_DEFAULT), (int) $_SESSION['usuario_id']]);
        session_regenerate_id(true);
        $sucesso = 'Senha alterada com sucesso.';
    }
}

require __DIR__ . '/header.php';
?>
<div class="mx-auto" style="max-width: 520px">
    <div class="d-flex justify-content-between align-items-center mb-4"><div><h1 class="h3 fw-bold mb-1">Alterar senha</h1><p class="text-muted mb-0">Usuário: <?= e(usuarioLogado()) ?></p></div><a href="index.php" class="btn btn-outline-secondary">Voltar</a></div>
    <div class="card border-0 shadow-sm rounded-4"><div class="card-body p-4">
        <?php if ($erro): ?><div class="alert alert-danger" role="alert"><?= e($erro) ?></div><?php endif; ?>
        <?php if ($sucesso): ?><div class="alert alert-success" role="alert"><?= e($sucesso) ?></div><?php endif; ?>
        <form method="POST">
            <?= csrfField() ?>
            <div class="mb-3"><label class="form-label" for="senha_atual">Senha atual</label><input class="form-control" type="password" id="senha_atual" name="senha_atual" autocomplete="current-password" required autofocus></div>
            <div class="mb-3"><label class="form-label" for="nova_senha">Nova senha</label><input class="form-control" type="password" id="nova_senha" name="nova_senha" minlength="8" autocomplete="new-password" required><div class="form-text">Use pelo menos 8 caracteres.</div></div>
            <div class="mb-4"><label class="form-label" for="confirmar_senha">Confirmar nova senha</label><input class="form-control" type="password" id="confirmar_senha" name="confirmar_senha" minlength="8" autocomplete="new-password" required></div>
            <button class="btn btn-primary w-100">Salvar nova senha</button>
        </form>
    </div></div>
</div>
<?php require __DIR__ . '/footer.php'; ?>
