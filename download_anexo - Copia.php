<?php
include 'config.php';

$id = isset($_GET['id']) ? (int) $_GET['id'] : 0;
$stmt = $pdo->prepare("
    SELECT a.*, m.mesa_id, m.item_id
    FROM manutencao_anexos a
    INNER JOIN manutencoes m ON m.id = a.manutencao_id
    WHERE a.id = ?
");
$stmt->execute([$id]);
$anexo = $stmt->fetch();

if (!$anexo) {
    http_response_code(404);
    exit('Anexo não encontrado.');
}

$base = realpath(__DIR__ . DIRECTORY_SEPARATOR . 'uploads' . DIRECTORY_SEPARATOR . 'manutencoes');
$arquivo = $base ? realpath($base . DIRECTORY_SEPARATOR . $anexo['nome_arquivo']) : false;
if (!$base || !$arquivo || strncmp($arquivo, $base, strlen($base)) !== 0 || !is_file($arquivo)) {
    http_response_code(404);
    exit('Arquivo não encontrado.');
}

$nome = str_replace(["\r", "\n", '"'], '', $anexo['nome_original']);
if (!empty($anexo['mesa_id'])) {
    try {
        $nome_log = mb_strimwidth($nome, 0, 150, '...');
        registrarLog(
            $pdo,
            $anexo['mesa_id'],
            "Documento visualizado na manutenção do equipamento #{$anexo['item_id']}: {$nome_log}"
        );
    } catch (Throwable $e) {
        // A visualização do documento não deve falhar caso o histórico esteja indisponível.
    }
}

header('Content-Type: ' . ($anexo['tipo_mime'] ?: 'application/octet-stream'));
header('Content-Length: ' . filesize($arquivo));
header("Content-Disposition: inline; filename=\"{$nome}\"; filename*=UTF-8''" . rawurlencode($nome));
header('X-Content-Type-Options: nosniff');
readfile($arquivo);
exit;
