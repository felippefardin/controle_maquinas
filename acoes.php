<?php
include 'config.php';
$acao = isset($_GET['acao']) ? $_GET['acao'] : '';

// --- MESAS ---
if ($acao == 'criar_mesa') {
    $stmt = $pdo->prepare("INSERT INTO mesas (identificacao) VALUES (?)");
    $stmt->execute([$_POST['identificacao']]);
    header("Location: index.php");
    exit;
}

if ($acao == 'editar_mesa') {
    $stmt = $pdo->prepare("UPDATE mesas SET identificacao = ? WHERE id = ?");
    $stmt->execute([$_POST['identificacao'], $_POST['id']]);
    header("Location: index.php");
    exit;
}

if ($acao == 'deletar_mesa') {
    $stmt = $pdo->prepare("DELETE FROM mesas WHERE id = ?");
    $stmt->execute([$_GET['id']]);
    header("Location: index.php");
    exit;
}

// --- ITENS ---
if ($acao == 'adicionar_item') {
    // Se mesa_id for vazio ou '0', definimos como null para evitar erro de Chave Estrangeira
    $mesa_id = (!empty($_POST['mesa_id']) && $_POST['mesa_id'] != '0') ? $_POST['mesa_id'] : null;
    
    $stmt = $pdo->prepare("INSERT INTO itens (mesa_id, tipo, nome_personalizado, patrimonio_protocolo) VALUES (?, ?, ?, ?)");
    $stmt->execute([$mesa_id, $_POST['tipo'], $_POST['nome_personalizado'], $_POST['patrimonio']]);
    
    // Redireciona para onde o item foi criado
    $url_retorno = ($mesa_id === null) ? "itens_avulsos.php" : "index.php";
    header("Location: $url_retorno");
    exit;
}

if ($acao == 'editar_item') {
    // 1. Atualiza os dados
    $stmt = $pdo->prepare("UPDATE itens SET tipo = ?, nome_personalizado = ?, patrimonio_protocolo = ? WHERE id = ?");
    $stmt->execute([$_POST['tipo'], $_POST['nome_personalizado'], $_POST['patrimonio'], $_POST['id']]);

    // 2. Decide para onde voltar baseado no campo 'origem' vindo do formulário
    $origem = isset($_POST['origem']) ? $_POST['origem'] : 'mesa';
    
    if ($origem == 'avulso') {
        header("Location: itens_avulsos.php");
    } else {
        header("Location: index.php");
    }
    exit;
}

if ($acao == 'remover_item') {
    // Busca o item antes de remover para saber onde redirecionar
    $stmtBusca = $pdo->prepare("SELECT mesa_id FROM itens WHERE id = ?");
    $stmtBusca->execute([$_GET['id']]);
    $item = $stmtBusca->fetch();
    
    $stmt = $pdo->prepare("DELETE FROM itens WHERE id = ?");
    $stmt->execute([$_GET['id']]);
    
    $url_retorno = (empty($item['mesa_id'])) ? "itens_avulsos.php" : "index.php";
    header("Location: $url_retorno");
    exit;
}

// --- MANUTENÇÃO ---
if ($acao == 'iniciar_manutencao') {
    $stmt = $pdo->prepare("UPDATE itens SET status = 'Manutenção' WHERE id = ?");
    $stmt->execute([$_POST['item_id']]);

    $stmtM = $pdo->prepare("INSERT INTO manutencoes (item_id, descricao_problema, status_manutencao) VALUES (?, ?, 'Aberto')");
    $stmtM->execute([$_POST['item_id'], $_POST['problema']]);

    header("Location: index.php");
    exit;
}

if ($acao == 'concluir_manutencao') {
    $stmt = $pdo->prepare("UPDATE itens SET status = 'Ativo' WHERE id = ?");
    $stmt->execute([$_POST['item_id']]);

    $stmtM = $pdo->prepare("UPDATE manutencoes SET status_manutencao = 'Concluído', data_fim = NOW() WHERE id = ?");
    $stmtM->execute([$_POST['manutencao_id']]);

    header("Location: index.php");
    exit;
}

if ($acao == 'registrar_movimento') {
    $novo_movimento = "[" . date('d/m/Y H:i') . "] - " . $_POST['movimento'] . "\n";

    $stmt = $pdo->prepare("SELECT movimentacoes FROM manutencoes WHERE id = ?");
    $stmt->execute([$_POST['manutencao_id']]);
    $atual = $stmt->fetchColumn();
    
    $final = $atual . $novo_movimento;

    $stmtU = $pdo->prepare("UPDATE manutencoes SET movimentacoes = ? WHERE id = ?");
    $stmtU->execute([$final, $_POST['manutencao_id']]);

    header("Location: manutencao.php?item_id=" . $_POST['item_id']);
    exit;
}
?>