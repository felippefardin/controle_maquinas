<?php
include 'config.php';
$acao = isset($_GET['acao']) ? $_GET['acao'] : '';

// --- MESAS ---
if ($acao == 'criar_mesa') {
    $stmt = $pdo->prepare("INSERT INTO mesas (identificacao, ip_mesa) VALUES (?, ?)");
    $stmt->execute([$_POST['identificacao'], $_POST['ip_mesa']]);
    
    $mesa_id = $pdo->lastInsertId();
    registrarLog($pdo, $mesa_id, "Mesa criada: {$_POST['identificacao']} com IP: {$_POST['ip_mesa']}");
    
    header("Location: index.php");
    exit;
}

// --- MESAS ---
if ($acao == 'editar_mesa') {
    $stmtOld = $pdo->prepare("SELECT identificacao FROM mesas WHERE id = ?");
    $stmtOld->execute([$_POST['id']]);
    $old = $stmtOld->fetch();

    $stmt = $pdo->prepare("UPDATE mesas SET identificacao = ? WHERE id = ?");
    $stmt->execute([$_POST['identificacao'], $_POST['id']]);

    if ($old['identificacao'] != $_POST['identificacao']) {
        registrarLog($pdo, $_POST['id'], "Nome da mesa alterado: '{$old['identificacao']}' -> '{$_POST['identificacao']}'");
    }
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
    
    // Ajustado para incluir o ip_maquina
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
    
    // Verifica mudança de patrimônio
    if ($itemAntigo['patrimonio_protocolo'] != $_POST['patrimonio']) {
        $mudancas[] = "Patrimônio: {$itemAntigo['patrimonio_protocolo']} -> {$_POST['patrimonio']}";
    }
    
    // Verifica mudança de IP
    if ($itemAntigo['ip_maquina'] != $_POST['ip_maquina']) {
        $mudancas[] = "IP: {$itemAntigo['ip_maquina']} -> {$_POST['ip_maquina']}";
    }

    // Verifica mudança de Mesa
    if ($itemAntigo['mesa_id'] != $_POST['mesa_id']) {
        // Busca nomes das mesas para o log ficar legível
        $oldMesaName = "Avulso";
        if ($itemAntigo['mesa_id']) {
            $stmtM = $pdo->prepare("SELECT identificacao FROM mesas WHERE id = ?");
            $stmtM->execute([$itemAntigo['mesa_id']]);
            $m = $stmtM->fetch();
            $oldMesaName = $m['identificacao'];
        }
        
        $newMesaName = "Avulso";
        if (!empty($_POST['mesa_id'])) {
            $stmtM = $pdo->prepare("SELECT identificacao FROM mesas WHERE id = ?");
            $stmtM->execute([$_POST['mesa_id']]);
            $m = $stmtM->fetch();
            $newMesaName = $m['identificacao'];
        }
        
        $mudancas[] = "Mesa: {$oldMesaName} -> {$newMesaName}";
    }

    // Executa a atualização no banco incluindo mesa_id
    $stmt = $pdo->prepare("UPDATE itens SET mesa_id = ?, tipo = ?, nome_personalizado = ?, patrimonio_protocolo = ?, ip_maquina = ? WHERE id = ?");
    $stmt->execute([
        !empty($_POST['mesa_id']) ? $_POST['mesa_id'] : null, 
        $_POST['tipo'], 
        $_POST['nome_personalizado'], 
        $_POST['patrimonio'], 
        $_POST['ip_maquina'], 
        $_POST['id']
    ]);

    // Registra o log no histórico da mesa nova (ou da antiga se for remoção)
    if (!empty($mudancas)) {
        $msg = "Item {$itemAntigo['tipo']} ({$itemAntigo['patrimonio_protocolo']}) alterado: " . implode(", ", $mudancas);
        // Registra no histórico da mesa que recebeu a alteração
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
?>