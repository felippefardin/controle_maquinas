<?php
declare(strict_types=1);
if (PHP_SAPI !== 'cli') { http_response_code(404); exit; }
require __DIR__ . '/config.php';
if ($argc !== 3) { fwrite(STDERR, "Uso: php usuario_cli.php usuario senha\n"); exit(1); }
$usuario = trim($argv[1]);
$senha = $argv[2];
if (!preg_match('/^[a-zA-Z0-9._-]{3,80}$/', $usuario) || strlen($senha) < 8) { fwrite(STDERR, "Usuário ou senha inválidos.\n"); exit(1); }
$stmt = $pdo->prepare('INSERT INTO usuarios (usuario, senha_hash) VALUES (?, ?) ON DUPLICATE KEY UPDATE senha_hash = VALUES(senha_hash)');
$stmt->execute([$usuario, password_hash($senha, PASSWORD_DEFAULT)]);
echo "Login criado ou atualizado: {$usuario}\n";
