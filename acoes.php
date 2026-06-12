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
    // 1. Atualiza o status para deletado (Soft Delete)
    $stmt = $pdo->prepare("UPDATE mesas SET status = 'deletado' WHERE id = ?");
    $stmt->execute([$_GET['id']]);
    
    // 2. Tenta registrar o log (verifique se a função registrarLog está no config.php)
    if (function_exists('registrarLog')) {
        registrarLog($pdo, $_GET['id'], "Mesa excluída do sistema.");
    }
    
    header("Location: index.php");
    exit;
}

// --- ITENS ---
if ($acao == 'adicionar_item') {
    // 1. Define o mesa_id corretamente, tratando caso seja nulo
    $mesa_id = (!empty($_POST['mesa_id']) && $_POST['mesa_id'] != '0') ? $_POST['mesa_id'] : null;
    
    // 2. Insere apenas uma vez com todos os campos
    $stmt = $pdo->prepare("INSERT INTO itens (mesa_id, tipo, nome_personalizado, patrimonio_protocolo, ip_maquina) VALUES (?, ?, ?, ?, ?)");
    $stmt->execute([
        $mesa_id, 
        $_POST['tipo'], 
        $_POST['nome_personalizado'], 
        $_POST['patrimonio'], 
        $_POST['ip_maquina'] // Certifique-se de que este campo vem do formulário
    ]);
    
    // 3. Redireciona corretamente
    $url_retorno = ($mesa_id === null) ? "itens_avulsos.php" : "index.php";
    header("Location: $url_retorno");
    exit;
}

if ($acao == 'editar_item') {
    // 1. Busca os dados atuais ANTES da atualização para comparar
    $stmtBusca = $pdo->prepare("SELECT * FROM itens WHERE id = ?");
    $stmtBusca->execute([$_POST['id']]);
    $itemAntigo = $stmtBusca->fetch();

    // 2. Monta as mudanças
    $mudancas = [];
    if ($itemAntigo['ip_maquina'] != $_POST['ip_maquina']) {
        $mudancas[] = "IP: {$itemAntigo['ip_maquina']} -> {$_POST['ip_maquina']}";
    }
    if ($itemAntigo['patrimonio_protocolo'] != $_POST['patrimonio']) {
        $mudancas[] = "Patrimônio: {$itemAntigo['patrimonio_protocolo']} -> {$_POST['patrimonio']}";
    }

    // 3. Executa a atualização
    $stmt = $pdo->prepare("UPDATE itens SET tipo = ?, nome_personalizado = ?, patrimonio_protocolo = ?, ip_maquina = ? WHERE id = ?");
    $stmt->execute([$_POST['tipo'], $_POST['nome_personalizado'], $_POST['patrimonio'], $_POST['ip_maquina'], $_POST['id']]);

    // 4. Se houve mudança, registra no log da mesa
    if (!empty($mudancas)) {
        $msg = "Item " . $itemAntigo['tipo'] . " alterado: " . implode(", ", $mudancas);
        registrarLog($pdo, $itemAntigo['mesa_id'], $msg);
    }

    header("Location: " . ($_POST['origem'] == 'avulso' ? "itens_avulsos.php" : "index.php"));
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