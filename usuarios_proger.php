<?php
require 'config.php';
$erro = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    exigirPostComCsrf();
    try {
        $pdo->beginTransaction();
        $acao = $_POST['acao'] ?? '';
        if ($acao === 'adicionar') {
            $q = $pdo->prepare('INSERT INTO usuarios_proger (usuario) VALUES (?)');
            $q->execute([nomeProger($_POST['usuario'] ?? '')]);
        } elseif (in_array($acao, ['editar', 'arquivar', 'restaurar'], true)) {
            $id = inteiroPositivo($_POST['id'] ?? null);
            $q = $pdo->prepare('SELECT * FROM usuarios_proger WHERE id = ? FOR UPDATE');
            $q->execute([$id]); $u = $q->fetch();
            if (!$u) throw new RuntimeException('Servidor não encontrado.');
            if ($acao === 'editar') {
                $nome = nomeProger($_POST['usuario'] ?? '');
                if ($u['arquivado_em']) throw new RuntimeException('Restaure o servidor antes de editar.');
                $q = $pdo->prepare('SELECT id FROM mesas WHERE usuario_proger_id = ? FOR UPDATE');
                $q->execute([$id]);
                foreach ($q->fetchAll() as $mesa) registrarLog($pdo, $mesa['id'], "Cadastro do servidor alterado de {$u['usuario']} para {$nome}. Os períodos anteriores preservam o nome original.");
                $q = $pdo->prepare('UPDATE usuarios_proger SET usuario = ? WHERE id = ?'); $q->execute([$nome, $id]);
                $q = $pdo->prepare('UPDATE mesas SET nome = ? WHERE usuario_proger_id = ?'); $q->execute([$nome, $id]);
            } elseif ($acao === 'arquivar' && !$u['arquivado_em']) {
                $agora = date('Y-m-d H:i:s');
                $q = $pdo->prepare('SELECT id FROM mesas WHERE usuario_proger_id = ? FOR UPDATE'); $q->execute([$id]);
                foreach ($q->fetchAll() as $mesa) {
                    encerrarPeriodoMesa($pdo, (int) $mesa['id'], $agora);
                    iniciarPeriodoMesa($pdo, (int) $mesa['id'], 'vazia', null, $agora);
                    registrarLog($pdo, $mesa['id'], "Servidor {$u['usuario']} arquivado. Mesa desocupada; histórico preservado.");
                    $limpar = $pdo->prepare("UPDATE mesas SET nome = 'Mesa vazia', usuario_proger_id = NULL WHERE id = ?"); $limpar->execute([$mesa['id']]);
                }
                $q = $pdo->prepare('UPDATE usuarios_proger SET arquivado_em = ? WHERE id = ?'); $q->execute([$agora, $id]);
            } elseif ($acao === 'restaurar') {
                $q = $pdo->prepare('UPDATE usuarios_proger SET arquivado_em = NULL WHERE id = ?'); $q->execute([$id]);
            }
        } else throw new RuntimeException('Ação inválida.');
        $pdo->commit(); header('Location: usuarios_proger.php?salvo=1'); exit;
    } catch (Throwable $e) {
        if ($pdo->inTransaction()) $pdo->rollBack();
        $erro = $e instanceof PDOException ? 'Não foi possível salvar. Verifique se esse nome já está cadastrado, inclusive nos arquivados.' : $e->getMessage();
    }
}
$arquivados = ($_GET['arquivo'] ?? '') === '1';
$usuarios = $pdo->query('SELECT * FROM usuarios_proger WHERE arquivado_em IS ' . ($arquivados ? 'NOT NULL' : 'NULL') . ' ORDER BY usuario')->fetchAll();
$contagem = $pdo->query('SELECT COUNT(*) AS total, COUNT(CASE WHEN arquivado_em IS NULL THEN 1 END) AS ativos, COUNT(CASE WHEN arquivado_em IS NOT NULL THEN 1 END) AS arquivados FROM usuarios_proger')->fetch();
require 'header.php';
?>
<main class="proger-users-page">
<h1>Usuários Proger</h1><p><a class="btn btn-primary proger-manage-folders" href="pastas.php">Gerenciar pastas</a></p>
<p>Cadastre os servidores que ocupam as mesas. Excluir arquiva o cadastro e preserva seus históricos.</p>
<?php if ($erro): ?><div class="alert alert-danger" role="alert"><?= e($erro) ?></div><?php endif; ?>
<?php if (isset($_GET['salvo'])): ?><div class="alert alert-success" role="status">Cadastro atualizado.</div><?php endif; ?>
<p class="text-muted proger-record-count" role="status"><strong><?= (int) $contagem['total'] ?></strong> usuários cadastrados: <strong><?= (int) $contagem['ativos'] ?></strong> ativos e <strong><?= (int) $contagem['arquivados'] ?></strong> arquivados.</p>
<div class="d-flex flex-wrap gap-2 mb-4"><a class="btn <?= !$arquivados ? 'btn-primary' : 'btn-outline-primary' ?>" href="usuarios_proger.php" <?= !$arquivados ? 'aria-current="page"' : '' ?>>Ativos (<?= (int) $contagem['ativos'] ?>)</a><a class="btn <?= $arquivados ? 'btn-secondary' : 'btn-outline-secondary' ?>" href="usuarios_proger.php?arquivo=1" <?= $arquivados ? 'aria-current="page"' : '' ?>>Arquivados (<?= (int) $contagem['arquivados'] ?>)</a></div>
<?php if (!$arquivados): ?>
<form method="POST" class="card card-body mb-4">
<?= csrfField() ?><input type="hidden" name="acao" value="adicionar">
<label class="form-label" for="novo-usuario">Novo servidor</label><div class="d-flex gap-2"><input id="novo-usuario" name="usuario" class="form-control" placeholder="nome.sobrenome" pattern="[a-zA-Z0-9._-]+" maxlength="255" required><button class="btn btn-primary">Adicionar</button></div>
</form><?php endif; ?>
<h2 class="h4"><?= $arquivados ? 'Servidores arquivados' : 'Servidores ativos' ?> <span class="badge text-bg-light"><?= count($usuarios) ?> registros</span></h2>
<div class="table-responsive"><table class="table align-middle proger-users-table"><thead><tr><th>Usuário</th><th>Ações</th></tr></thead><tbody>
<?php if (!$usuarios): ?><tr><td colspan="2">Nenhum servidor nesta lista.</td></tr><?php endif; ?>
<?php foreach ($usuarios as $u): ?><tr><td>
<?php if (!$arquivados): ?><form method="POST" class="d-flex gap-2 proger-edit-form"><?= csrfField() ?><input type="hidden" name="acao" value="editar"><input type="hidden" name="id" value="<?= (int) $u['id'] ?>"><input aria-label="Usuário do servidor" class="form-control" name="usuario" value="<?= e($u['usuario']) ?>" pattern="[a-zA-Z0-9._-]+" maxlength="255" required><button class="btn btn-outline-primary">Salvar edição</button></form>
<?php else: ?><?= e($u['usuario']) ?><small class="d-block">Arquivado em <?= e(date('d/m/Y H:i', strtotime($u['arquivado_em']))) ?></small><?php endif; ?>
</td><td><div class="proger-row-actions"><a class="btn btn-outline-primary" href="pastas.php?usuario_id=<?= (int) $u['id'] ?>">Pastas de acesso</a><a class="btn btn-outline-info" href="historico_proger.php?id=<?= (int) $u['id'] ?>">Histórico</a><form method="POST" <?= !$arquivados ? 'onsubmit="return confirm(\'Arquivar este servidor e desocupar suas mesas? Os históricos serão preservados.\')"' : '' ?>><?= csrfField() ?><input type="hidden" name="id" value="<?= (int) $u['id'] ?>"><input type="hidden" name="acao" value="<?= $arquivados ? 'restaurar' : 'arquivar' ?>"><button class="btn btn-outline-<?= $arquivados ? 'success' : 'danger' ?>"><?= $arquivados ? 'Restaurar' : 'Excluir e arquivar' ?></button></form></div></td></tr><?php endforeach; ?>
</tbody></table></div>
</main>
<?php require 'footer.php'; ?>
