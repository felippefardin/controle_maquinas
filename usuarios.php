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
        $email = strtolower(trim((string) ($_POST['email'] ?? '')));
        $senha = (string) ($_POST['senha'] ?? '');
        if (!preg_match('/^[a-zA-Z0-9._-]{3,80}$/', $usuario)) $erro = 'Use de 3 a 80 caracteres: letras, números, ponto, hífen ou sublinhado.';
        elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) $erro = 'Informe um e-mail válido para recuperação.';
        elseif (strlen($senha) < 8) $erro = 'A senha deve ter no mínimo 8 caracteres.';
        else {
            try {
                $stmt = $pdo->prepare('INSERT INTO usuarios (usuario, email, senha_hash) VALUES (?, ?, ?)');
                $stmt->execute([$usuario, $email, password_hash($senha, PASSWORD_DEFAULT)]);
                $sucesso = 'Login cadastrado. A recuperação será enviada para o e-mail informado.';
            } catch (PDOException $e) {
                $erro = (string) $e->getCode() === '23000' ? 'Usuário ou e-mail já cadastrado.' : 'Não foi possível cadastrar o login.';
            }
        }
    } elseif ($acao === 'atualizar_email') {
        $id = inteiroPositivo($_POST['id'] ?? null);
        $email = strtolower(trim((string) ($_POST['email'] ?? '')));
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) $erro = 'Informe um e-mail válido.';
        else {
            try {
                $pdo->prepare('UPDATE usuarios SET email = ? WHERE id = ?')->execute([$email, $id]);
                $sucesso = 'E-mail de recuperação atualizado.';
            } catch (PDOException $e) { $erro = 'Esse e-mail já está associado a outro login.'; }
        }
    } elseif ($acao === 'excluir') {
        $id = inteiroPositivo($_POST['id'] ?? null);
        $total = (int) $pdo->query('SELECT COUNT(*) FROM usuarios')->fetchColumn();
        if ($total <= 1) $erro = 'O último login do sistema não pode ser excluído.';
        else {
            $stmt = $pdo->prepare('DELETE FROM usuarios WHERE id = ?');
            $stmt->execute([$id]);
            if ($id === (int) ($_SESSION['usuario_id'] ?? 0)) {
                $_SESSION = []; session_destroy(); header('Location: login.php'); exit;
            }
            $sucesso = $stmt->rowCount() ? 'Login excluído.' : 'Login não encontrado.';
        }
    }
}
$usuarios = $pdo->query('SELECT id, usuario, email, criado_em FROM usuarios ORDER BY usuario')->fetchAll();
require __DIR__ . '/header.php';
?>
<div class="d-flex justify-content-between align-items-center mb-4"><div><h1 class="h3 fw-bold mb-1">Gerenciar logins</h1><p class="text-muted mb-0">Cada login deve possuir um e-mail para recuperação.</p></div><a href="index.php" class="btn btn-outline-primary">Voltar</a></div>
<?php if ($erro): ?><div class="alert alert-danger"><?= e($erro) ?></div><?php endif; ?>
<?php if ($sucesso): ?><div class="alert alert-success"><?= e($sucesso) ?></div><?php endif; ?>
<div class="row g-4">
<div class="col-lg-5"><div class="card border-0 shadow-sm rounded-4"><div class="card-body p-4"><h2 class="h5 mb-3">Novo login</h2>
<form method="POST"><?= csrfField() ?><input type="hidden" name="acao" value="criar"><div class="mb-3"><label class="form-label">Usuário</label><input class="form-control" name="usuario" minlength="3" maxlength="80" pattern="[a-zA-Z0-9._-]+" required></div><div class="mb-3"><label class="form-label">E-mail para recuperação</label><input class="form-control" type="email" name="email" required></div><div class="mb-3"><label class="form-label">Senha</label><input class="form-control" type="password" name="senha" minlength="8" autocomplete="new-password" required></div><button class="btn btn-success">Cadastrar login</button></form>
</div></div></div>
<div class="col-lg-7"><div class="card border-0 shadow-sm rounded-4"><div class="card-body p-4"><h2 class="h5 mb-3">Logins cadastrados</h2><div class="table-responsive"><table class="table align-middle"><thead><tr><th>Usuário e e-mail</th><th>Criado em</th><th></th></tr></thead><tbody>
<?php foreach ($usuarios as $registro): ?><tr><td><strong><?= e($registro['usuario']) ?></strong><?= (int) $registro['id'] === (int) ($_SESSION['usuario_id'] ?? 0) ? ' <span class="badge bg-primary">atual</span>' : '' ?><form method="POST" class="d-flex gap-2 mt-2"><?= csrfField() ?><input type="hidden" name="acao" value="atualizar_email"><input type="hidden" name="id" value="<?= (int) $registro['id'] ?>"><input class="form-control form-control-sm" type="email" name="email" value="<?= e($registro['email'] ?? '') ?>" placeholder="E-mail de recuperação" required><button class="btn btn-sm btn-outline-primary">Salvar</button></form></td><td><?= e(date('d/m/Y H:i', strtotime($registro['criado_em']))) ?></td><td class="text-end"><form method="POST" onsubmit="return confirm('Excluir este login?')"><?= csrfField() ?><input type="hidden" name="acao" value="excluir"><input type="hidden" name="id" value="<?= (int) $registro['id'] ?>"><button class="btn btn-sm btn-outline-danger">Excluir</button></form></td></tr><?php endforeach; ?>
</tbody></table></div></div></div></div></div>
<?php require __DIR__ . '/footer.php'; ?>
