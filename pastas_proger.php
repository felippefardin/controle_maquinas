<?php
declare(strict_types=1);
function garantirPastasProger(PDO $pdo): void {
    $pdo->exec("CREATE TABLE IF NOT EXISTS proger_pastas (id INT AUTO_INCREMENT PRIMARY KEY, nome VARCHAR(255) NOT NULL UNIQUE) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
    $pdo->exec("CREATE TABLE IF NOT EXISTS proger_usuario_pastas (usuario_id INT NOT NULL, pasta_id INT NOT NULL, PRIMARY KEY(usuario_id,pasta_id), FOREIGN KEY(usuario_id) REFERENCES usuarios_proger(id) ON DELETE CASCADE, FOREIGN KEY(pasta_id) REFERENCES proger_pastas(id) ON DELETE CASCADE) ENGINE=InnoDB");
    $pdo->exec("CREATE TABLE IF NOT EXISTS proger_pastas_importacoes (versao INT PRIMARY KEY) ENGINE=InnoDB");
    if ($pdo->query('SELECT versao FROM proger_pastas_importacoes WHERE versao=1')->fetchColumn()) return;
    $conteudo = file_get_contents(__DIR__ . '/pastas_iniciais.json');
    $dados = json_decode(ltrim($conteudo, "\xEF\xBB\xBF"), true, 512, JSON_THROW_ON_ERROR);
    $pdo->beginTransaction();
    try {
        // A chave única serializa a importação entre requisições simultâneas.
        $q = $pdo->prepare('INSERT IGNORE INTO proger_pastas_importacoes VALUES (1)'); $q->execute();
        if ($q->rowCount() === 0) { $pdo->commit(); return; }
        foreach ($dados as $pasta => $nomes) {
            $q = $pdo->prepare('INSERT IGNORE INTO proger_pastas (nome) VALUES (?)'); $q->execute([$pasta]);
            $q = $pdo->prepare('SELECT id FROM proger_pastas WHERE nome=?'); $q->execute([$pasta]); $pastaId = $q->fetchColumn();
            foreach ($nomes as $nome) {
                $q = $pdo->prepare('INSERT IGNORE INTO usuarios_proger (usuario) VALUES (?)'); $q->execute([$nome]);
                $q = $pdo->prepare('INSERT IGNORE INTO proger_usuario_pastas (usuario_id,pasta_id) SELECT id,? FROM usuarios_proger WHERE usuario=?'); $q->execute([$pastaId,$nome]);
            }
        }
        $pdo->commit();
    } catch (Throwable $e) { if ($pdo->inTransaction()) $pdo->rollBack(); throw $e; }
}
