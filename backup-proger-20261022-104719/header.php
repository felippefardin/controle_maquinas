<?php
function cmIcon(string $name, string $class = ''): string {
    $paths = [
        'monitor' => '<rect x="3" y="4" width="18" height="13" rx="2"/><path d="M8 21h8m-4-4v4"/>',
        'desk' => '<path d="M3 10h18M5 10v10m14-10v10M8 10V4h8v6M9 20h6"/>',
        'box' => '<path d="m12 3 9 5v9l-9 5-9-5V8l9-5Z"/><path d="m3 8 9 5 9-5M12 13v9M7.5 5.5l9 5"/>',
        'tool' => '<path d="M14.7 6.3a5 5 0 0 0-6.3 6.3l-5.7 5.7a2.1 2.1 0 0 0 3 3l5.7-5.7a5 5 0 0 0 6.3-6.3l-3.4 3.4-3-3 3.4-3.4Z"/>',
        'archive' => '<rect x="3" y="3" width="18" height="5" rx="1"/><path d="M5 8v12h14V8m-10 4h6"/>',
        'book' => '<path d="M12 5v16M3 3h5a4 4 0 0 1 4 4 4 4 0 0 1 4-4h5v16h-5a4 4 0 0 0-4 2 4 4 0 0 0-4-2H3V3Z"/>',
        'plus' => '<path d="M12 5v14M5 12h14"/>',
        'search' => '<circle cx="10.5" cy="10.5" r="6.5"/><path d="m16 16 5 5"/>',
        'arrow' => '<path d="m9 5 7 7-7 7"/>',
        'close' => '<path d="m6 6 12 12M6 18 18 6"/>',
        'clock' => '<circle cx="12" cy="12" r="9"/><path d="M12 7v5l3 2"/>',
        'trash' => '<path d="M3 6h18M9 6V3h6v3M5 6l1 15h12l1-15M10 10v7m4-7v7"/>',
        'cpu' => '<rect x="6" y="3" width="12" height="18" rx="2"/><path d="M9 7h6m-6 4h6m-3 6h.01"/>',
        'user' => '<circle cx="12" cy="8" r="4"/><path d="M4 21v-2a8 8 0 0 1 16 0v2"/>',
        'chevron' => '<path d="m6 9 6 6 6-6"/>',
    ];
    return '<svg class="cm-icon ' . htmlspecialchars($class, ENT_QUOTES, 'UTF-8') . '" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" focusable="false">' . ($paths[$name] ?? $paths['box']) . '</svg>';
}
$cmCurrent = basename($_SERVER['SCRIPT_NAME'] ?? '');
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Controle de Máquinas</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="style.css?v=<?= (int) filemtime(__DIR__ . '/style.css') ?>">
    <meta name="referrer" content="same-origin">
</head>
<body class="bg-light cm-app">
<header class="app-header">
    <div class="app-topbar">
        <a href="index.php" class="app-brand"><span class="brand-mark"><?= cmIcon('monitor') ?></span><span>Controle de Máquinas<small>Inventário de equipamentos</small></span></a>
        <details class="account-menu">
            <summary><span class="user-avatar"><?= cmIcon('user') ?></span><span class="account-name"><?= e(usuarioLogado()) ?><small>Minha conta</small></span><?= cmIcon('chevron') ?></summary>
            <div class="account-options">
                <a href="alterar_senha.php">Alterar senha</a>
                <a href="usuarios.php">Gerenciar logins</a>
                <form action="logout.php" method="POST"><?= csrfField() ?><button type="submit">Sair da conta</button></form>
            </div>
        </details>
    </div>
    <nav class="app-nav" aria-label="Navegação principal">
        <?php foreach ([['index.php', 'desk', 'Visão geral'], ['itens_avulsos.php', 'box', 'Itens avulsos'], ['maquinas_manutencao.php', 'tool', 'Manutenção'], ['arquivo_mesas.php', 'archive', 'Arquivo'], ['tutorial.php', 'book', 'Tutorial']] as [$cmHref, $cmSymbol, $cmLabel]): ?>
            <a href="<?= $cmHref ?>" class="app-nav-link<?= $cmCurrent === $cmHref ? ' is-active' : '' ?>"<?= $cmCurrent === $cmHref ? ' aria-current="page"' : '' ?>><?= cmIcon($cmSymbol) ?> <?= $cmLabel ?></a>
        <?php endforeach; ?>
    </nav>
</header>
<div class="container py-4 app-container">
