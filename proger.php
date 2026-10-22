<?php
const PROGER_INICIAIS = ['alessandra.nunes', 'andreia.costa', 'angela.domingos', 'ayla.pinto', 'camila.silveira', 'carolina.leite', 'elaine.giacomin', 'gabrielly.silva', 'graziella.monjardim', 'igor.bastos', 'julia.ramos', 'lorena.faria', 'marcelly.assis', 'marta.antunes', 'paula.fazolo', 'proger.geral', 'renata.lucas', 'roberto.moraes', 'victor.vieira', 'wilkerson.santos', 'marcos.siravegna', 'marine.simoes', 'debora.pereira', 'felippe.andreata', 'meiriely.souza', 'chirlei.souza', 'gabriela.ribeiro', 'anap.oliveira', 'pamela.muchilin', 'lorenzo.miranda'];
function garantirUsuariosProger(PDO $pdo): void {
    $pdo->exec("CREATE TABLE IF NOT EXISTS usuarios_proger (id INT AUTO_INCREMENT PRIMARY KEY, usuario VARCHAR(255) NOT NULL UNIQUE, arquivado_em DATETIME NULL, criado_em DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
    $pdo->exec("CREATE TABLE IF NOT EXISTS proger_migracoes (versao INT PRIMARY KEY) ENGINE=InnoDB");
    if ($pdo->query('SELECT versao FROM proger_migracoes WHERE versao = 1')->fetchColumn()) return;
    garantirEstruturaOcupacao($pdo);
    foreach (['mesas', 'mesa_ocupacoes', 'historico_mesas'] as $tabela) {
        $q = $pdo->prepare("SELECT COUNT(*) FROM information_schema.columns WHERE table_schema = DATABASE() AND table_name = ? AND column_name = 'usuario_proger_id'");
        $q->execute([$tabela]);
        if (!$q->fetchColumn()) $pdo->exec("ALTER TABLE {$tabela} ADD COLUMN usuario_proger_id INT NULL, ADD INDEX idx_proger (usuario_proger_id)");
    }
    $pdo->beginTransaction();
    try {
        $q = $pdo->prepare('INSERT IGNORE INTO usuarios_proger (usuario) VALUES (?)');
        foreach (PROGER_INICIAIS as $nome) $q->execute([$nome]);
        // Vincula apenas nomes conhecidos; não presume a identidade dos demais registros antigos.
        $pdo->exec("UPDATE mesas m JOIN usuarios_proger u ON u.usuario = CONVERT(m.nome USING utf8mb4) COLLATE utf8mb4_unicode_ci SET m.usuario_proger_id = u.id WHERE m.usuario_proger_id IS NULL");
        $pdo->exec("UPDATE mesa_ocupacoes o JOIN usuarios_proger u ON u.usuario = CONVERT(o.servidor USING utf8mb4) COLLATE utf8mb4_unicode_ci SET o.usuario_proger_id = u.id WHERE o.usuario_proger_id IS NULL");
        $pdo->exec('INSERT IGNORE INTO proger_migracoes (versao) VALUES (1)');
        $pdo->commit();
    } catch (Throwable $e) { if ($pdo->inTransaction()) $pdo->rollBack(); throw $e; }
}
function nomeProger(mixed $valor): string {
    $nome = trim((string) $valor);
    if (!preg_match('/^[a-zA-Z0-9._-]{1,255}$/D', $nome)) throw new RuntimeException('Informe um nome de usuário válido, sem espaços (letras, números, ponto, hífen ou sublinhado).');
    return strtolower($nome);
}
function selecionarProger(PDO $pdo): array {
    if (trim((string) ($_POST['novo_servidor'] ?? '')) !== '') {
        $nome = nomeProger($_POST['novo_servidor']);
        $q = $pdo->prepare('INSERT INTO usuarios_proger (usuario) VALUES (?)');
        $q->execute([$nome]);
        return ['id' => (int) $pdo->lastInsertId(), 'usuario' => $nome];
    }
    $id = inteiroPositivo($_POST['usuario_proger_id'] ?? null);
    $q = $pdo->prepare('SELECT id, usuario FROM usuarios_proger WHERE id = ? AND arquivado_em IS NULL FOR UPDATE');
    $q->execute([$id]);
    $usuario = $q->fetch();
    if (!$usuario) throw new RuntimeException('Selecione um servidor ativo.');
    return $usuario;
}
function campoProger(array $usuarios, string $sufixo, ?int $atual = null): void { ?>
<div class="d-flex flex-wrap gap-2 align-items-end my-2">
<div><label class="form-label" for="servidor-<?= e($sufixo) ?>">Usuário Proger</label>
<select id="servidor-<?= e($sufixo) ?>" name="usuario_proger_id" class="form-select"><option value="">Selecione um servidor existente</option>
<?php foreach ($usuarios as $u): ?><option value="<?= (int) $u['id'] ?>" <?= $atual === (int) $u['id'] ? 'selected' : '' ?>><?= e($u['usuario']) ?></option><?php endforeach; ?></select></div>
<div><label class="form-label" for="novo-<?= e($sufixo) ?>">Ou cadastrar novo servidor</label><input id="novo-<?= e($sufixo) ?>" class="form-control" name="novo_servidor" maxlength="255" pattern="[a-zA-Z0-9._-]+" placeholder="nome.sobrenome"></div>
</div>
<?php }

