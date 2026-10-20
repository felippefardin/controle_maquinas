<?php
declare(strict_types=1);
if (PHP_SAPI !== 'cli') { http_response_code(404); exit; }
require __DIR__ . '/config.php';
if ($argc !== 2) { fwrite(STDERR, "Uso: php recuperacao_cli.php usuario\n"); exit(1); }
$codigo = strtoupper(bin2hex(random_bytes(4)));
$stmt = $pdo->prepare('UPDATE usuarios SET codigo_recuperacao_hash = ? WHERE usuario = ?');
$stmt->execute([password_hash($codigo, PASSWORD_DEFAULT), $argv[1]]);
if (!$stmt->rowCount()) { fwrite(STDERR, "Usuário não encontrado.\n"); exit(1); }
echo $codigo . PHP_EOL;
