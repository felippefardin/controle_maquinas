<?php
include 'config.php';
$acao = isset($_GET['acao']) ? $_GET['acao'] : '';


if ($acao == 'criar_mesa') {
    $stmt = $pdo->prepare("INSERT INTO mesas (identificacao) VALUES (?)");
    $stmt->execute([$_POST['identificacao']]);
    header("Location: index.php");
}

if ($acao == 'editar_mesa') {
    $stmt = $pdo->prepare("UPDATE mesas SET identificacao = ? WHERE id = ?");
    $stmt->execute([$_POST['identificacao'], $_POST['id']]);
    header("Location: index.php");
}

if ($acao == 'deletar_mesa') {
    $stmt = $pdo->prepare("DELETE FROM mesas WHERE id = ?");
    $stmt->execute([$_GET['id']]);
    header("Location: index.php");
}


if ($acao == 'adicionar_item') {
    $stmt = $pdo->prepare("INSERT INTO itens (mesa_id, tipo, nome_personalizado, patrimonio_protocolo) VALUES (?, ?, ?, ?)");
    $stmt->execute([$_POST['mesa_id'], $_POST['tipo'], $_POST['nome_personalizado'], $_POST['patrimonio']]);
    header("Location: index.php");
}

if ($acao == 'editar_item') {
    $stmt = $pdo->prepare("UPDATE itens SET tipo = ?, nome_personalizado = ?, patrimonio_protocolo = ? WHERE id = ?");
    $stmt->execute([$_POST['tipo'], $_POST['nome_personalizado'], $_POST['patrimonio'], $_POST['id']]);
    header("Location: index.php");
}

if ($acao == 'remover_item') {
    $stmt = $pdo->prepare("DELETE FROM itens WHERE id = ?");
    $stmt->execute([$_GET['id']]);
    header("Location: index.php");
}


if ($acao == 'iniciar_manutencao') {
    $item_id = $_POST['item_id'];
    $problema = $_POST['problema'];


    $stmt = $pdo->prepare("UPDATE itens SET status = 'Manutenção' WHERE id = ?");
    $stmt->execute([$item_id]);

    // Cria registro na tabela de manutenção (certifique-se que a tabela existe)
    $stmtM = $pdo->prepare("INSERT INTO manutencoes (item_id, descricao_problema, status_manutencao) VALUES (?, ?, 'Aberto')");
    $stmtM->execute([$item_id, $problema]);

    header("Location: index.php");
}

if ($acao == 'concluir_manutencao') {
    $item_id = $_POST['item_id'];
    $manutencao_id = $_POST['manutencao_id'];

   
    $stmt = $pdo->prepare("UPDATE itens SET status = 'Ativo' WHERE id = ?");
    $stmt->execute([$item_id]);

    
    $stmtM = $pdo->prepare("UPDATE manutencoes SET status_manutencao = 'Concluído', data_fim = NOW() WHERE id = ?");
    $stmtM->execute([$manutencao_id]);

    header("Location: index.php");
}
if ($acao == 'registrar_movimento') {
    $manutencao_id = $_POST['manutencao_id'];
    $item_id = $_POST['item_id'];
    $novo_movimento = "[" . date('d/m/Y H:i') . "] - " . $_POST['movimento'] . "\n";

    // Busca o histórico atual e concatena o novo
    $stmt = $pdo->prepare("SELECT movimentacoes FROM manutencoes WHERE id = ?");
    $stmt->execute([$manutencao_id]);
    $atual = $stmt->fetchColumn();
    
    $final = $atual . $novo_movimento;

    $stmtU = $pdo->prepare("UPDATE manutencoes SET movimentacoes = ? WHERE id = ?");
    $stmtU->execute([$final, $manutencao_id]);

    header("Location: manutencao.php?item_id=" . $item_id);
}
?>