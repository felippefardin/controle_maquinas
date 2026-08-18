<?php
declare(strict_types=1);
define('PAGINA_PUBLICA', true);
require __DIR__ . '/config.php';

$erro = '';
$sucesso = '';
$etapa = (string) ($_POST['etapa'] ?? 'solicitar');
$usuarioInformado = trim((string) ($_POST['usuario'] ?? ''));
$emailInformado = strtolower(trim((string) ($_POST['email'] ?? '')));

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
    exigirPostComCsrf();
    if ($etapa === 'solicitar') {
        if ($usuarioInformado === '' || !filter_var($emailInformado, FILTER_VALIDATE_EMAIL)) {
            $erro = 'Informe o usuário e um e-mail válido.';
        } else {
            $stmt = $pdo->prepare('SELECT id FROM usuarios WHERE usuario = ? AND LOWER(email) = ? LIMIT 1');
            $stmt->execute([$usuarioInformado, $emailInformado]);
            $id = $stmt->fetchColumn();
            if ($id) {
                $codigo = (string) random_int(100000, 999999);
                $stmt = $pdo->prepare('UPDATE usuarios SET codigo_recuperacao_hash = ?, codigo_recuperacao_expira_em = DATE_ADD(NOW(), INTERVAL 15 MINUTE), codigo_recuperacao_tentativas = 0 WHERE id = ?');
                $stmt->execute([password_hash($codigo, PASSWORD_DEFAULT), (int) $id]);
                try {
                    enviarEmailSistema($emailInformado, 'Código para recuperar sua senha', "Seu código de recuperação é: {$codigo}\n\nEle expira em 15 minutos. Se você não solicitou, ignore esta mensagem.");
                } catch (Throwable $e) {
                    error_log('Falha ao enviar recuperação: ' . $e->getMessage());
                    $pdo->prepare('UPDATE usuarios SET codigo_recuperacao_hash = NULL, codigo_recuperacao_expira_em = NULL WHERE id = ?')->execute([(int) $id]);
                    $erro = 'Não foi possível enviar o e-mail agora. Tente novamente em instantes.';
                }
            }
            if ($erro === '') {
                $sucesso = 'Se os dados estiverem corretos, o código foi enviado. Verifique também Spam e Lixeira.';
                $etapa = 'redefinir';
            }
        }
    } elseif ($etapa === 'redefinir') {
        $codigo = trim((string) ($_POST['codigo'] ?? ''));
        $senha = (string) ($_POST['senha'] ?? '');
        $confirmacao = (string) ($_POST['confirmar_senha'] ?? '');
        if (!preg_match('/^\d{6}$/', $codigo)) $erro = 'Informe o código de 6 dígitos.';
        elseif (strlen($senha) < 8) $erro = 'A nova senha deve ter no mínimo 8 caracteres.';
        elseif ($senha !== $confirmacao) $erro = 'A confirmação da senha não confere.';
        else {
            $stmt = $pdo->prepare('SELECT id, codigo_recuperacao_hash, codigo_recuperacao_expira_em, codigo_recuperacao_tentativas FROM usuarios WHERE usuario = ? AND LOWER(email) = ? LIMIT 1');
            $stmt->execute([$usuarioInformado, $emailInformado]);
            $registro = $stmt->fetch();
            $valido = $registro && $registro['codigo_recuperacao_hash'] && (int) $registro['codigo_recuperacao_tentativas'] < 5 && strtotime((string) $registro['codigo_recuperacao_expira_em']) >= time() && password_verify($codigo, $registro['codigo_recuperacao_hash']);
            if (!$valido) {
                if ($registro) $pdo->prepare('UPDATE usuarios SET codigo_recuperacao_tentativas = codigo_recuperacao_tentativas + 1 WHERE id = ?')->execute([(int) $registro['id']]);
                $erro = 'Código inválido ou expirado.';
            } else {
                $pdo->prepare('UPDATE usuarios SET senha_hash = ?, codigo_recuperacao_hash = NULL, codigo_recuperacao_expira_em = NULL, codigo_recuperacao_tentativas = 0 WHERE id = ?')->execute([password_hash($senha, PASSWORD_DEFAULT), (int) $registro['id']]);
                $sucesso = 'Senha alterada com sucesso. Você já pode entrar.';
                $etapa = 'concluido';
            }
        }
    }
}
?>
<!doctype html><html lang="pt-br"><head><meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1"><title>Recuperar senha</title><link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet"></head>
<body class="bg-light d-flex align-items-center min-vh-100"><main class="container" style="max-width:480px"><div class="card border-0 shadow rounded-4"><div class="card-body p-4 p-md-5"><h1 class="h3 fw-bold">Recuperar senha</h1>
<?php if ($erro): ?><div class="alert alert-danger"><?= e($erro) ?></div><?php endif; ?><?php if ($sucesso): ?><div class="alert alert-success"><?= e($sucesso) ?></div><?php endif; ?>
<?php if ($etapa === 'concluido'): ?><a href="login.php" class="btn btn-primary w-100">Voltar ao login</a>
<?php elseif ($etapa === 'redefinir'): ?><p class="text-muted">Digite o código recebido e escolha uma nova senha.</p><form method="POST"><?= csrfField() ?><input type="hidden" name="etapa" value="redefinir"><input type="hidden" name="usuario" value="<?= e($usuarioInformado) ?>"><input type="hidden" name="email" value="<?= e($emailInformado) ?>"><div class="mb-3"><label class="form-label">Código de 6 dígitos</label><input class="form-control" name="codigo" inputmode="numeric" pattern="\d{6}" maxlength="6" required autofocus></div><div class="mb-3"><label class="form-label">Nova senha</label><input class="form-control" type="password" name="senha" minlength="8" required></div><div class="mb-4"><label class="form-label">Confirmar nova senha</label><input class="form-control" type="password" name="confirmar_senha" minlength="8" required></div><button class="btn btn-primary w-100">Redefinir senha</button></form>
<?php else: ?><p class="text-muted">Informe seu usuário e o e-mail cadastrado para receber um código.</p><form method="POST"><?= csrfField() ?><input type="hidden" name="etapa" value="solicitar"><div class="mb-3"><label class="form-label">Usuário</label><input class="form-control" name="usuario" required autofocus></div><div class="mb-4"><label class="form-label">E-mail cadastrado</label><input class="form-control" type="email" name="email" required></div><button class="btn btn-primary w-100">Enviar código</button></form><?php endif; ?><a href="login.php" class="btn btn-link w-100 mt-2">Voltar</a>
</div></div></main></body></html>
