<?php
declare(strict_types=1);

if (PHP_SAPI !== 'cli') {
    http_response_code(404);
    exit;
}

require __DIR__ . '/config.php';

garantirEstruturaManutencao($pdo);

$pdo->exec("
    CREATE TABLE IF NOT EXISTS usuarios (
        id INT NOT NULL AUTO_INCREMENT,
        usuario VARCHAR(80) NOT NULL,
        senha_hash VARCHAR(255) NOT NULL,
        criado_em DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY (id),
        UNIQUE KEY uq_usuarios_usuario (usuario)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
");

$stmt = $pdo->prepare("SELECT COUNT(*) FROM information_schema.columns WHERE table_schema = ? AND table_name = 'usuarios' AND column_name = 'codigo_recuperacao_hash'");
$stmt->execute([$db]);
if (!(int) $stmt->fetchColumn()) {
    $pdo->exec('ALTER TABLE usuarios ADD COLUMN codigo_recuperacao_hash VARCHAR(255) NULL AFTER senha_hash');
    echo 'Coluna de recuperação criada.' . PHP_EOL;
}

$novasColunas = [
    'email' => 'ALTER TABLE usuarios ADD COLUMN email VARCHAR(255) NULL AFTER usuario',
    'codigo_recuperacao_expira_em' => 'ALTER TABLE usuarios ADD COLUMN codigo_recuperacao_expira_em DATETIME NULL AFTER codigo_recuperacao_hash',
    'codigo_recuperacao_tentativas' => 'ALTER TABLE usuarios ADD COLUMN codigo_recuperacao_tentativas TINYINT UNSIGNED NOT NULL DEFAULT 0 AFTER codigo_recuperacao_expira_em',
];
foreach ($novasColunas as $coluna => $sql) {
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM information_schema.columns WHERE table_schema = ? AND table_name = 'usuarios' AND column_name = ?");
    $stmt->execute([$db, $coluna]);
    if (!(int) $stmt->fetchColumn()) {
        $pdo->exec($sql);
        echo "Coluna criada: {$coluna}" . PHP_EOL;
    }
}
$stmt = $pdo->prepare("SELECT COUNT(*) FROM information_schema.statistics WHERE table_schema = ? AND table_name = 'usuarios' AND index_name = 'uq_usuarios_email'");
$stmt->execute([$db]);
if (!(int) $stmt->fetchColumn()) {
    $pdo->exec('CREATE UNIQUE INDEX uq_usuarios_email ON usuarios (email)');
}

$indices = [
    'idx_itens_patrimonio' => 'CREATE INDEX idx_itens_patrimonio ON itens (patrimonio_protocolo)',
    'idx_itens_status_mesa' => 'CREATE INDEX idx_itens_status_mesa ON itens (status, mesa_id)',
    'idx_manutencoes_status' => 'CREATE INDEX idx_manutencoes_status ON manutencoes (status_manutencao, data)',
];

foreach ($indices as $nome => $sql) {
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM information_schema.statistics WHERE table_schema = ? AND index_name = ?");
    $stmt->execute([$db, $nome]);
    if (!(int) $stmt->fetchColumn()) {
        $pdo->exec($sql);
        echo "Índice criado: {$nome}" . PHP_EOL;
    }
}

echo 'Migrações concluídas.' . PHP_EOL;
