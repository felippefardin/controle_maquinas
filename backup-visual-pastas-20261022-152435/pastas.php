<?php
declare(strict_types=1);
require __DIR__ . '/config.php';
require_once __DIR__ . '/pastas_proger.php';
garantirPastasProger($pdo);
$erro = '';
$usuarioId = filter_var($_GET['usuario_id'] ?? 0, FILTER_VALIDATE_INT) ?: 0;
if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
    exigirPostComCsrf();
    try {
        $pdo->beginTransaction();
        $acao = (string) ($_POST['acao'] ?? '');
        $pastaId = $acao === 'criar' ? 0 : inteiroPositivo($_POST['pasta_id'] ?? 0);
        if (in_array($acao, ['criar','renomear'], true)) {
            $nome = trim((string) ($_POST['nome'] ?? ''));
            if ($nome === '' || mb_strlen($nome) > 255 || preg_match('/[\x00-\x1F\x7F]/u', $nome)) throw new RuntimeException('Informe um nome de pasta com até 255 caracteres.');
            if ($acao === 'criar') {
                $q=$pdo->prepare('INSERT INTO proger_pastas (nome) VALUES (?)'); $q->execute([$nome]);
            } else {
                $q=$pdo->prepare('UPDATE proger_pastas SET nome=? WHERE id=?'); $q->execute([$nome,$pastaId]);
            }
        } elseif ($acao === 'excluir') {
            $q=$pdo->prepare('DELETE FROM proger_pastas WHERE id=?'); $q->execute([$pastaId]);
        } elseif (in_array($acao,['vincular','remover'],true)) {
            $id=inteiroPositivo($_POST['usuario_id'] ?? 0);
            $q=$pdo->prepare('SELECT id FROM usuarios_proger WHERE id=? AND arquivado_em IS NULL FOR UPDATE'); $q->execute([$id]);
            if (!$q->fetchColumn()) throw new RuntimeException('Selecione um servidor ativo.');
            $q=$pdo->prepare('SELECT id FROM proger_pastas WHERE id=? FOR UPDATE'); $q->execute([$pastaId]);
            if (!$q->fetchColumn()) throw new RuntimeException('Pasta não encontrada.');
            $sql=$acao==='vincular' ? 'INSERT IGNORE INTO proger_usuario_pastas (usuario_id,pasta_id) VALUES (?,?)' : 'DELETE FROM proger_usuario_pastas WHERE usuario_id=? AND pasta_id=?';
            $q=$pdo->prepare($sql); $q->execute([$id,$pastaId]); $usuarioId=$id;
        } else throw new RuntimeException('Ação inválida.');
        $pdo->commit();
        header('Location: pastas.php?usuario_id=' . $usuarioId . '&salvo=1'); exit;
    } catch(Throwable $e) {
        if($pdo->inTransaction()) $pdo->rollBack();
        $erro=$e instanceof PDOException ? 'Não foi possível salvar. Verifique se o nome já existe e tente novamente.' : $e->getMessage();
    }
}
$usuarios=$pdo->query('SELECT id,usuario,arquivado_em FROM usuarios_proger ORDER BY usuario')->fetchAll();
$pastas=$pdo->query('SELECT p.*,COUNT(v.usuario_id) AS total FROM proger_pastas p LEFT JOIN proger_usuario_pastas v ON v.pasta_id=p.id GROUP BY p.id,p.nome ORDER BY p.nome')->fetchAll();
$selecionado=null; foreach($usuarios as $u) if((int)$u['id']===$usuarioId) $selecionado=$u;
$q=$pdo->prepare('SELECT pasta_id FROM proger_usuario_pastas WHERE usuario_id=?'); $q->execute([$usuarioId]);
$vinculos=array_map('intval',$q->fetchAll(PDO::FETCH_COLUMN));
require __DIR__ . '/header.php';
?>
<h1 class="h3">Pastas compartilhadas</h1>
<p class="text-muted">Controle dos acessos dos servidores. As alterações são registradas neste cadastro; as permissões da rede devem ser aplicadas pela TI.</p>
<p><a class="btn btn-outline-primary" href="usuarios_proger.php">Voltar aos usuários Proger</a></p>
<?php if($erro): ?><div class="alert alert-danger" role="alert"><?= e($erro) ?></div><?php endif; ?>
<?php if(isset($_GET['salvo'])): ?><div class="alert alert-success" role="status">Alteração salva.</div><?php endif; ?>
<div class="card card-body mb-4">
<h2 class="h5">Pastas por usuário</h2>
<form method="GET" class="d-flex gap-2 mb-3"><label for="servidor" class="visually-hidden">Servidor</label><select id="servidor" name="usuario_id" class="form-select" required><option value="">Selecione um servidor</option>
<?php foreach($usuarios as $u): ?><option value="<?= (int)$u['id'] ?>" <?= $usuarioId===(int)$u['id']?'selected':'' ?>><?= e($u['usuario']) ?><?= $u['arquivado_em']?' (arquivado)':'' ?></option><?php endforeach; ?>
</select><button class="btn btn-primary">Consultar</button></form>
<?php if($selecionado): ?>
<h3 class="h6"><?= e($selecionado['usuario']) ?> · <?= count($vinculos) ?> pasta(s)</h3>
<?php if($selecionado['arquivado_em']): ?><p>Servidor arquivado. Restaure o cadastro para alterar os acessos.</p><?php endif; ?>
<ul class="list-group mb-3">
<?php foreach($pastas as $p): if(!in_array((int)$p['id'],$vinculos,true)) continue; ?>
<li class="list-group-item d-flex flex-wrap justify-content-between gap-2"><span class="text-break"><?= e($p['nome']) ?></span>
<?php if(!$selecionado['arquivado_em']): ?><form method="POST"><?= csrfField() ?><input type="hidden" name="acao" value="remover"><input type="hidden" name="usuario_id" value="<?= $usuarioId ?>"><input type="hidden" name="pasta_id" value="<?= (int)$p['id'] ?>"><button class="btn btn-sm btn-outline-danger">Remover deste usuário</button></form><?php endif; ?></li>
<?php endforeach; ?>
</ul>
<?php if(!$vinculos): ?><p>Nenhuma pasta vinculada.</p><?php endif; ?>
<?php if(!$selecionado['arquivado_em']): ?>
<form method="POST" class="d-flex gap-2"><?= csrfField() ?><input type="hidden" name="acao" value="vincular"><input type="hidden" name="usuario_id" value="<?= $usuarioId ?>"><select name="pasta_id" class="form-select" aria-label="Pasta para adicionar" required><option value="">Selecione uma pasta para adicionar</option><?php foreach($pastas as $p): if(in_array((int)$p['id'],$vinculos,true)) continue; ?><option value="<?= (int)$p['id'] ?>"><?= e($p['nome']) ?></option><?php endforeach; ?></select><button class="btn btn-success">Adicionar ao usuário</button></form>
<?php endif; endif; ?>
</div>
<div class="card card-body">
<h2 class="h5">Cadastro de pastas · <?= count($pastas) ?></h2>
<p>Renomear atualiza o nome para todos os usuários vinculados.</p>
<form method="POST" class="d-flex gap-2 mb-4"><?= csrfField() ?><input type="hidden" name="acao" value="criar"><input name="nome" class="form-control" maxlength="255" placeholder="Nome da pasta ou caminho de rede" aria-label="Nova pasta" required><button class="btn btn-success">Cadastrar pasta</button></form>
<?php foreach($pastas as $p): ?><div class="border-bottom pb-3 mb-3">
<form method="POST" class="d-flex flex-wrap gap-2"><?= csrfField() ?><input type="hidden" name="acao" value="renomear"><input type="hidden" name="pasta_id" value="<?= (int)$p['id'] ?>"><input name="nome" class="form-control" maxlength="255" value="<?= e($p['nome']) ?>" aria-label="Nome da pasta" required><button class="btn btn-outline-primary">Salvar nome</button><span class="align-self-center"><?= (int)$p['total'] ?> usuário(s)</span></form>
<form method="POST" class="mt-2" onsubmit="return confirm('Excluir esta pasta do cadastro e remover seus vínculos de todos os usuários?')"><?= csrfField() ?><input type="hidden" name="acao" value="excluir"><input type="hidden" name="pasta_id" value="<?= (int)$p['id'] ?>"><button class="btn btn-sm btn-outline-danger">Excluir pasta do cadastro</button></form>
</div><?php endforeach; ?>
</div>
<?php require __DIR__ . '/footer.php'; ?>
