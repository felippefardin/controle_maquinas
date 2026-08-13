<?php
declare(strict_types=1);

if (PHP_SAPI !== 'cli' && session_status() !== PHP_SESSION_ACTIVE) {
    session_set_cookie_params([
        'httponly' => true,
        'samesite' => 'Lax',
        'secure' => !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off',
    ]);
    session_start();
}

$env = is_file(__DIR__ . '/.env') ? (parse_ini_file(__DIR__ . '/.env', false, INI_SCANNER_RAW) ?: []) : [];
$envValue = static fn(string $key, string $default = ''): string => (string) (getenv($key) ?: ($env[$key] ?? $default));

$host = $envValue('DB_HOST', 'localhost');
$port = $envValue('DB_PORT', '3306');
$db   = $envValue('DB_DATABASE', 'controle_maquinas');
$user = $envValue('DB_USERNAME', 'root');
$pass = $envValue('DB_PASSWORD', '');
date_default_timezone_set('America/Sao_Paulo');

try {
    $pdo = new PDO("mysql:host=$host;port=$port;dbname=$db;charset=utf8mb4", $user, $pass, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
    ]);
} catch (PDOException $e) {
    error_log('Falha na conexao com o banco: ' . $e->getMessage());
    http_response_code(500);
    exit('Não foi possível conectar ao sistema. Tente novamente mais tarde.');
}

function e(mixed $value): string {
    return htmlspecialchars((string) $value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

function csrfToken(): string {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function csrfField(): string {
    return '<input type="hidden" name="csrf_token" value="' . e(csrfToken()) . '">';
}

function exigirPostComCsrf(): void {
    if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'POST') {
        http_response_code(405);
        exit('Método não permitido.');
    }
    $token = (string) ($_POST['csrf_token'] ?? '');
    if ($token === '' || !hash_equals(csrfToken(), $token)) {
        http_response_code(403);
        exit('Sua sessão expirou. Volte à página anterior e tente novamente.');
    }
}

function usuarioLogado(): ?string {
    return isset($_SESSION['usuario_nome']) ? (string) $_SESSION['usuario_nome'] : null;
}

function exigirLogin(): void {
    if (usuarioLogado() !== null) return;
    $destino = basename((string) ($_SERVER['REQUEST_URI'] ?? 'index.php'));
    header('Location: login.php?destino=' . rawurlencode($destino));
    exit;
}

function inteiroPositivo(mixed $value): int {
    $id = filter_var($value, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
    if ($id === false) {
        http_response_code(422);
        exit('Identificador inválido.');
    }
    return (int) $id;
}

function textoObrigatorio(mixed $value, string $campo, int $limite = 255): string {
    $texto = trim((string) $value);
    if ($texto === '' || mb_strlen($texto) > $limite) {
        http_response_code(422);
        exit(e($campo) . ' é obrigatório e deve ter no máximo ' . $limite . ' caracteres.');
    }
    return $texto;
}

function ipOpcional(mixed $value, string $campo = 'IP'): string {
    $ip = trim((string) $value);
    if ($ip !== '' && filter_var($ip, FILTER_VALIDATE_IP) === false) {
        http_response_code(422);
        exit(e($campo) . ' inválido.');
    }
    return $ip;
}

// function registrarLog($pdo, $mesa_id, $mensagem) {
//     try {
//         $stmt = $pdo->prepare("INSERT INTO historico_mesas (mesa_id, descricao_alteracao, data_alteracao) VALUES (?, ?, NOW())");
//         $stmt->execute([$mesa_id, $mensagem]);
//     } catch (PDOException $e) {
//         // Se a tabela não existir, o log não falhará o site todo
//     }
// }
function registrarLog($pdo, $mesa_id, $mensagem) {
    // Corrigido para os nomes das colunas que você criou: 'evento' e 'data'
    // Nota: 'data' é preenchido pelo banco com CURRENT_TIMESTAMP, mas pode ser passado via SQL
    $stmt = $pdo->prepare("INSERT INTO historico_mesas (mesa_id, evento, data_alteracao) VALUES (?, ?, NOW())");
    $usuario = usuarioLogado() ?? 'sistema';
    $stmt->execute([$mesa_id, "[Usuário: {$usuario}] {$mensagem}"]);
}

function garantirEstruturaManutencao($pdo) {
    $pdo->exec("ALTER TABLE manutencoes ADD COLUMN IF NOT EXISTS substituto_item_id INT NULL AFTER item_id");
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS manutencao_anexos (
            id INT NOT NULL AUTO_INCREMENT,
            manutencao_id INT NOT NULL,
            nome_original VARCHAR(255) NOT NULL,
            nome_arquivo VARCHAR(255) NOT NULL,
            tipo_mime VARCHAR(150) DEFAULT NULL,
            tamanho BIGINT NOT NULL DEFAULT 0,
            data_upload DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            INDEX idx_manutencao_anexos_manutencao (manutencao_id),
            CONSTRAINT fk_manutencao_anexos_manutencao
                FOREIGN KEY (manutencao_id) REFERENCES manutencoes(id) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
    ");
}

function salvarAnexosManutencao($pdo, $manutencao_id, $arquivos) {
    if (empty($arquivos['name']) || !is_array($arquivos['name'])) {
        return [];
    }

    $mimesPermitidos = [
        'pdf' => ['application/pdf'],
        'doc' => ['application/msword', 'application/CDFV2'],
        'docx' => ['application/vnd.openxmlformats-officedocument.wordprocessingml.document', 'application/zip'],
        'xls' => ['application/vnd.ms-excel', 'application/CDFV2'],
        'xlsx' => ['application/vnd.openxmlformats-officedocument.spreadsheetml.sheet', 'application/zip'],
        'csv' => ['text/plain', 'text/csv', 'application/csv'],
        'txt' => ['text/plain'],
        'jpg' => ['image/jpeg'],
        'jpeg' => ['image/jpeg'],
        'png' => ['image/png'],
    ];
    $limite = 10 * 1024 * 1024;
    $diretorio = __DIR__ . DIRECTORY_SEPARATOR . 'uploads' . DIRECTORY_SEPARATOR . 'manutencoes';
    $erros = [];

    if (!is_dir($diretorio) && !mkdir($diretorio, 0775, true) && !is_dir($diretorio)) {
        return ['Não foi possível criar a pasta de anexos.'];
    }

    foreach ($arquivos['name'] as $indice => $nome_original) {
        $erro_upload = $arquivos['error'][$indice] ?? UPLOAD_ERR_NO_FILE;
        if ($erro_upload === UPLOAD_ERR_NO_FILE) continue;
        if ($erro_upload !== UPLOAD_ERR_OK) {
            $erros[] = "Falha ao enviar {$nome_original}.";
            continue;
        }

        $tamanho = (int) ($arquivos['size'][$indice] ?? 0);
        $extensao = strtolower(pathinfo($nome_original, PATHINFO_EXTENSION));
        if (!isset($mimesPermitidos[$extensao])) {
            $erros[] = "Formato não permitido: {$nome_original}.";
            continue;
        }
        if ($tamanho > $limite) {
            $erros[] = "O arquivo {$nome_original} ultrapassa 10 MB.";
            continue;
        }

        $finfo = new finfo(FILEINFO_MIME_TYPE);
        $tipo_mime = $finfo->file($arquivos['tmp_name'][$indice]) ?: 'application/octet-stream';
        if (!in_array($tipo_mime, $mimesPermitidos[$extensao], true)) {
            $erros[] = "O conteúdo do arquivo não corresponde ao formato informado: {$nome_original}.";
            continue;
        }

        $nome_arquivo = bin2hex(random_bytes(16)) . '.' . $extensao;
        $destino = $diretorio . DIRECTORY_SEPARATOR . $nome_arquivo;
        if (!move_uploaded_file($arquivos['tmp_name'][$indice], $destino)) {
            $erros[] = "Não foi possível salvar {$nome_original}.";
            continue;
        }

        $stmt = $pdo->prepare("
            INSERT INTO manutencao_anexos
                (manutencao_id, nome_original, nome_arquivo, tipo_mime, tamanho)
            VALUES (?, ?, ?, ?, ?)
        ");
        $stmt->execute([$manutencao_id, basename($nome_original), $nome_arquivo, $tipo_mime, $tamanho]);

        $stmt_manutencao = $pdo->prepare("SELECT mesa_id, item_id FROM manutencoes WHERE id = ?");
        $stmt_manutencao->execute([$manutencao_id]);
        $dados_manutencao = $stmt_manutencao->fetch();
        if ($dados_manutencao && !empty($dados_manutencao['mesa_id'])) {
            $nome_log = mb_strimwidth(basename($nome_original), 0, 150, '...');
            registrarLog(
                $pdo,
                $dados_manutencao['mesa_id'],
                "Documento anexado à manutenção do equipamento #{$dados_manutencao['item_id']}: {$nome_log}"
            );
        }
    }

    return $erros;
}

if (PHP_SAPI !== 'cli' && !defined('PAGINA_PUBLICA')) {
    exigirLogin();
}
?>
