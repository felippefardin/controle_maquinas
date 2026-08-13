<?php
declare(strict_types=1);
require __DIR__ . '/config.php';

$erro = '';
$sucesso = '';
if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
    exigirPostComCsrf();
    $acao = (string) ($_POST['acao'] ?? '');

    if ($acao === 'criar') {
        $usuario = trim((string) ($_POST['usuario'] ?? ''));
        $senha = (string) ($_POST['senha'] ?? '');
        if (!preg_match('/^[a-zA-Z0-9._-]{3,80}$/', $usuario)) {
            $erro = 'Use de 3 a 80 caracteres: letras, números, ponto, hífen ou sublinhado.';
        } elseif (strlen($senha) < 8) {
            $erro = 'A senha deve ter no mínimo 8 caracteres.';
        } else {
            try {
                $codigo = strtoupper(bin2hex(random_bytes(4)));
                $stmt = $pdo->prepare('INSERT INTO usuarios (usuario, senha_hash, codigo_recuperacao_hash) VALUES (?, ?, ?)');
                $stmt->execute([$usuario, password_hash($senha, PASSWORD_DEFAULT), password_hash($codigo, PASSWORD_DEFAULT)]);
                $sucesso = "Login cadastrado. Código de recuperação: {$codigo}. Guarde-o em local seguro; ele será exibido apenas agora.";
            } catch (PDOException $e) {
                if ((string) $e->getCode() === '23000') $erro = 'Esse usuário já existe.';
                else { error_log($e->getMessage()); $erro = 'Não foi possível cadastrar o login.'; }
            }
        }
    }

    if ($acao === 'gerar_codigo') {
        $id = inteiroPositivo($_POST['id'] ?? null);
        $codigo = strtoupper(bin2hex(random_bytes(4)));
        $stmt = $pdo->prepare('UPDATE usuarios SET codigo_recuperacao_hash = ? WHERE id = ?');
        $stmt->execute([password_hash($codigo, PASSWORD_DEFAULT), $id]);
        $sucesso = "Novo código de recuperação: {$codigo}. O código anterior deixou de funcionar.";
    }

    if ($acao === 'excluir') {
        $id = inteiroPositivo($_POST['id'] ?? null);
        $total = (int) $pdo->query('SELECT COUNT(*) FROM usuarios')->fetchColumn();
        if ($total <= 1) {
            $erro = 'O último login do sistema não pode ser excluído.';
        } else {
            $stmt = $pdo->prepare('DELETE FROM usuarios WHERE id = ?');
            $stmt->execute([$id]);
            if ($id === (int) ($_SESSION['usuario_id'] ?? 0)) {
                $_SESSION = [];
                session_destroy();
                header('Location: login.php');
                exit;
            }
            $sucesso = $stmt->rowCount() ? 'Login excluído.' : 'Login não encontrado.';
        }
    }
}

$usuarios = $pdo->query('SELECT id, usuario, criado_em FROM usuarios ORDER BY usuario')->fetchAll();
require __DIR__ . '/header.php';
?>
<div class="d-flex justify-content-between align-items-center mb-4"><div><h1 class="h3 fw-bold mb-1">Gerenciar logins</h1><p class="text-muted mb-0">Todos os logins acessam o mesmo inventário.</p></div><a href="index.php" class="btn btn-outline-primary">Voltar</a></div>
<?php if ($erro): ?><div class="alert alert-danger"><?= e($erro) ?></div><?php endif; ?>
<?php if ($sucesso): ?><div class="alert alert-success"><?= e($sucesso) ?></div><?php endif; ?>
<div class="row g-4">
    <div class="col-lg-5"><div class="card border-0 shadow-sm rounded-4"><div class="card-body p-4"><h2 class="h5 mb-3">Novo login</h2>
        <form method="POST"><input type="hidden" name="acao" value="criar"><div class="mb-3"><label class="form-label" for="usuario">Usuário</label><input class="form-control" id="usuario" name="usuario" minlength="3" maxlength="80" pattern="[a-zA-Z0-9._-]+" required></div><div class="mb-3"><label class="form-label" for="senha">Senha</label><input class="form-control" type="password" id="senha" name="senha" minlength="8" autocomplete="new-password" required></div><button class="btn btn-success">Cadastrar login</button></form>
    </div></div></div>
    <div class="col-lg-7"><div class="card border-0 shadow-sm rounded-4"><div class="card-body p-4"><h2 class="h5 mb-3">Logins cadastrados</h2><div class="table-responsive"><table class="table align-middle"><thead><tr><th>Usuário</th><th>Criado em</th><th></th></tr></thead><tbody>
        <?php foreach ($usuarios as $registro): ?><tr><td><strong><?= e($registro['usuario']) ?></strong><?= (int) $registro['id'] === (int) ($_SESSION['usuario_id'] ?? 0) ? ' <span class="badge bg-primary">atual</span>' : '' ?></td><td><?= e(date('d/m/Y H:i', strtotime($registro['criado_em']))) ?></td><td class="text-end"><form method="POST" class="d-inline"><input type="hidden" name="acao" value="gerar_codigo"><input type="hidden" name="id" value="<?= (int) $registro['id'] ?>"><button class="btn btn-sm btn-outline-primary">Gerar código</button></form> <form method="POST" class="d-inline" onsubmit="return confirm('Excluir este login?')"><input type="hidden" name="acao" value="excluir"><input type="hidden" name="id" value="<?= (int) $registro['id'] ?>"><button class="btn btn-sm btn-outline-danger">Excluir</button></form></td></tr><?php endforeach; ?>
    </tbody></table></div></div></div></div>
</div>
<?php require __DIR__ . '/footer.php'; ?>
