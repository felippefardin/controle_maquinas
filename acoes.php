<?php
include 'config.php';
$acao = isset($_GET['acao']) ? $_GET['acao'] : '';

// --- CORREÇÃO NA CRIAÇÃO DE MESA ---
if ($acao == 'criar_mesa') {
    $nome = $_POST['identificacao']; 
    
    $ip_mesa = isset($_POST['ip_mesa']) ? $_POST['ip_mesa'] : '0.0.0.0';

    $stmt = $pdo->prepare("INSERT INTO mesas (nome, ip_mesa, status) VALUES (?, ?, 'ativo')");
    $stmt->execute([$nome, $ip_mesa]);
    
    $mesa_id = $pdo->lastInsertId();
    registrarLog($pdo, $mesa_id, "Mesa criada: {$nome} com IP: {$ip_mesa}");
    
    header("Location: index.php");
    exit;
}

// --- MESAS ---
if ($_GET['acao'] == 'editar_mesa') {
    $id = $_POST['id'];
    $novo_nome = $_POST['nome']; 

    // 1. Atualiza o nome da mesa no banco
    $stmt = $pdo->prepare("UPDATE mesas SET nome = ? WHERE id = ?");
    $stmt->execute([$novo_nome, $id]);

    // 2. Registra a alteração no histórico usando a função correta do sistema
    registrarLog($pdo, $id, "Nome da mesa alterado para: " . $novo_nome);

    header("Location: index.php");
    exit;
}

if ($acao == 'deletar_mesa') {
    $stmt = $pdo->prepare("UPDATE mesas SET status = 'deletado' WHERE id = ?");
    $stmt->execute([$_GET['id']]);
    
    registrarLog($pdo, $_GET['id'], "Mesa excluída do sistema.");
    
    header("Location: index.php");
    exit;
}

// --- ITENS ---
if ($acao == 'adicionar_item') {
    $mesa_id = (!empty($_POST['mesa_id']) && $_POST['mesa_id'] != '0') ? $_POST['mesa_id'] : null;
    
    $stmt = $pdo->prepare("INSERT INTO itens (mesa_id, tipo, nome_personalizado, patrimonio_protocolo, ip_maquina) VALUES (?, ?, ?, ?, ?)");
    $stmt->execute([
        $mesa_id, 
        $_POST['tipo'], 
        $_POST['nome_personalizado'], 
        $_POST['patrimonio'],
        $_POST['ip_maquina'] ?? ''
    ]);
    
    if ($mesa_id) {
        registrarLog($pdo, $mesa_id, "Item adicionado: {$_POST['tipo']} (Patrimônio: {$_POST['patrimonio']})");
    }
    
    $url_retorno = ($mesa_id === null) ? "itens_avulsos.php" : "index.php";
    header("Location: $url_retorno");
    exit;
}

// --- ITENS ---
if ($acao == 'editar_item') {
    $stmtBusca = $pdo->prepare("SELECT * FROM itens WHERE id = ?");
    $stmtBusca->execute([$_POST['id']]);
    $itemAntigo = $stmtBusca->fetch();

    $mudancas = [];
    
    if ($itemAntigo['patrimonio_protocolo'] != $_POST['patrimonio']) {
        $mudancas[] = "Patrimônio: {$itemAntigo['patrimonio_protocolo']} -> {$_POST['patrimonio']}";
    }
    
    if ($itemAntigo['ip_maquina'] != $_POST['ip_maquina']) {
        $mudancas[] = "IP: {$itemAntigo['ip_maquina']} -> {$_POST['ip_maquina']}";
    }

    if ($itemAntigo['mesa_id'] != $_POST['mesa_id']) {
        $oldMesaName = "Avulso";
        if ($itemAntigo['mesa_id']) {
            $stmtM = $pdo->prepare("SELECT identificacao FROM mesas WHERE id = ?");
            $stmtM->execute([$itemAntigo['mesa_id']]);
            $m = $stmtM->fetch();
            $oldMesaName = $m['identificacao'] ?? $m['nome'] ?? 'Mesa';
        }
        
        $newMesaName = "Avulso";
        if (!empty($_POST['mesa_id'])) {
            $stmtM = $pdo->prepare("SELECT identificacao FROM mesas WHERE id = ?");
            $stmtM->execute([$_POST['mesa_id']]);
            $m = $stmtM->fetch();
            $newMesaName = $m['identificacao'] ?? $m['nome'] ?? 'Mesa';
        }
        
        $mudancas[] = "Mesa: {$oldMesaName} -> {$newMesaName}";
    }

    $stmt = $pdo->prepare("UPDATE itens SET mesa_id = ?, tipo = ?, nome_personalizado = ?, patrimonio_protocolo = ?, ip_maquina = ? WHERE id = ?");
    $stmt->execute([
        !empty($_POST['mesa_id']) ? $_POST['mesa_id'] : null, 
        $_POST['tipo'], 
        $_POST['nome_personalizado'], 
        $_POST['patrimonio'], 
        $_POST['ip_maquina'], 
        $_POST['id']
    ]);

    if (!empty($mudancas)) {
        $msg = "Item {$itemAntigo['tipo']} ({$itemAntigo['patrimonio_protocolo']}) alterado: " . implode(", ", $mudancas);
        registrarLog($pdo, !empty($_POST['mesa_id']) ? $_POST['mesa_id'] : $itemAntigo['mesa_id'], $msg);
    }

    header("Location: " . ($_POST['origem'] == 'avulso' ? "itens_avulsos.php" : "index.php"));
    exit;
}

if ($acao == 'remover_item') {
    $stmtBusca = $pdo->prepare("SELECT mesa_id, tipo, patrimonio_protocolo FROM itens WHERE id = ?");
    $stmtBusca->execute([$_GET['id']]);
    $item = $stmtBusca->fetch();
    
    $stmt = $pdo->prepare("DELETE FROM itens WHERE id = ?");
    $stmt->execute([$_GET['id']]);
    
    if ($item['mesa_id']) {
        registrarLog($pdo, $item['mesa_id'], "Item removido: {$item['tipo']} (Patrimônio: {$item['patrimonio_protocolo']})");
    }
    
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

    $stmtI = $pdo->prepare("SELECT mesa_id FROM itens WHERE id = ?");
    $stmtI->execute([$_POST['item_id']]);
    $item = $stmtI->fetch();
    
    registrarLog($pdo, $item['mesa_id'], "Manutenção iniciada. Problema: {$_POST['problema']}");

    header("Location: index.php");
    exit;
}

if ($acao == 'concluir_manutencao') {
    $stmt = $pdo->prepare("UPDATE itens SET status = 'Ativo' WHERE id = ?");
    $stmt->execute([$_POST['item_id']]);

    $stmtM = $pdo->prepare("UPDATE manutencoes SET status_manutencao = 'Concluído', data_fim = NOW() WHERE id = ?");
    $stmtM->execute([$_POST['manutencao_id']]);

    $stmtI = $pdo->prepare("SELECT mesa_id FROM itens WHERE id = ?");
    $stmtI->execute([$_POST['item_id']]);
    $item = $stmtI->fetch();

    registrarLog($pdo, $item['mesa_id'], "Manutenção concluída.");

    header("Location: index.php");
    exit;
}

if ($acao == 'registrar_movimento') {
    $novo_movimento = "[" . date('d/m/Y H:i') . "] - " . $_POST['movimento'] . "\n";

    $stmt = $pdo->prepare("SELECT movimentacoes, item_id FROM manutencoes WHERE id = ?");
    $stmt->execute([$_POST['manutencao_id']]);
    $manutencao = $stmt->fetch();
    
    $final = $manutencao['movimentacoes'] . $novo_movimento;

    $stmtU = $pdo->prepare("UPDATE manutencoes SET movimentacoes = ? WHERE id = ?");
    $stmtU->execute([$final, $_POST['manutencao_id']]);
    
    $stmtI = $pdo->prepare("SELECT mesa_id FROM itens WHERE id = ?");
    $stmtI->execute([$manutencao['item_id']]);
    $mesa_id = $stmtI->fetchColumn();
    
    registrarLog($pdo, $mesa_id, "Nova movimentação de manutenção: {$_POST['movimento']}");

    header("Location: manutencao.php?item_id=" . $_POST['item_id']);
    exit;
}

if ($acao == 'salvar_ip_item') {
    $stmtBusca = $pdo->prepare("SELECT mesa_id, tipo, patrimonio_protocolo, ip_maquina FROM itens WHERE id = ?");
    $stmtBusca->execute([$_POST['item_id']]);
    $item = $stmtBusca->fetch();

    $stmt = $pdo->prepare("UPDATE itens SET ip_maquina = ? WHERE id = ?");
    $stmt->execute([$_POST['ip_maquina'], $_POST['item_id']]);
    
    registrarLog($pdo, $item['mesa_id'], "IP do item {$item['tipo']} ({$item['patrimonio_protocolo']}) alterado: '{$item['ip_maquina']}' -> '{$_POST['ip_maquina']}'");
    
    header("Location: index.php");
    exit;
}

// Arquivar mesa (muda status para 'arquivado')
if ($acao == 'arquivar_mesa') {
    $stmt = $pdo->prepare("UPDATE mesas SET status = 'arquivado' WHERE id = ?");
    $stmt->execute([$_GET['id']]);
    registrarLog($pdo, $_GET['id'], "Mesa arquivada.");
    header("Location: index.php");
    exit;
}

// Reativar mesa (muda status para 'ativo')
if ($acao == 'reativar_mesa') {
    $stmt = $pdo->prepare("UPDATE mesas SET status = 'ativo' WHERE id = ?");
    $stmt->execute([$_GET['id']]);
    registrarLog($pdo, $_GET['id'], "Mesa reativada.");
    header("Location: arquivo_mesas.php");
    exit;
}
?>