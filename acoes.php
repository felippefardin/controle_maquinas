<?php
include 'config.php';
$acao = isset($_GET['acao']) ? $_GET['acao'] : '';
exigirPostComCsrf();

// --- CORREÇÃO NA CRIAÇÃO DE MESA ---
if ($acao == 'criar_mesa') {
    $nome = textoObrigatorio($_POST['identificacao'] ?? '', 'Identificação');
    
    $ip_mesa = ipOpcional($_POST['ip_mesa'] ?? '') ?: '0.0.0.0';

    $stmt = $pdo->prepare("INSERT INTO mesas (nome, ip_mesa, status) VALUES (?, ?, 'ativo')");
    $stmt->execute([$nome, $ip_mesa]);
    
    $mesa_id = $pdo->lastInsertId();
    registrarLog($pdo, $mesa_id, "Mesa criada: {$nome} com IP: {$ip_mesa}");
    
    header("Location: index.php");
    exit;
}

// --- MESAS ---
if ($acao == 'editar_mesa') {
    $id = inteiroPositivo($_POST['id'] ?? null);
    $novo_nome = textoObrigatorio($_POST['nome'] ?? '', 'Nome');

    // 1. Atualiza o nome da mesa no banco
    $stmt = $pdo->prepare("UPDATE mesas SET nome = ? WHERE id = ?");
    $stmt->execute([$novo_nome, $id]);

    // 2. Registra a alteração no histórico usando a função correta do sistema
    registrarLog($pdo, $id, "Nome da mesa alterado para: " . $novo_nome);

    header("Location: index.php");
    exit;
}

if ($acao == 'deletar_mesa') {
    $id = inteiroPositivo($_POST['id'] ?? null);
    $stmt = $pdo->prepare("UPDATE mesas SET status = 'deletado' WHERE id = ?");
    $stmt->execute([$id]);
    
    registrarLog($pdo, $id, "Mesa excluída do sistema.");
    
    header("Location: index.php");
    exit;
}

// --- ITENS ---
if ($acao == 'adicionar_item') {
    $mesa_id = (!empty($_POST['mesa_id']) && $_POST['mesa_id'] != '0') ? inteiroPositivo($_POST['mesa_id']) : null;
    $origemItem = (string) ($_POST['origem_item'] ?? 'novo');

    if ($origemItem === 'avulso') {
        if ($mesa_id === null) exit('Selecione uma mesa para vincular o equipamento.');
        $itemAvulsoId = inteiroPositivo($_POST['item_avulso_id'] ?? null);

        try {
            $pdo->beginTransaction();
            $mesa = buscarMesaAtiva($pdo, $mesa_id, true);

            $stmtItem = $pdo->prepare("SELECT id, tipo, patrimonio_protocolo FROM itens WHERE id = ? AND mesa_id IS NULL AND status = 'Ativo' FOR UPDATE");
            $stmtItem->execute([$itemAvulsoId]);
            $itemAvulso = $stmtItem->fetch();
            if (!$itemAvulso) {
                throw new RuntimeException('O equipamento avulso não está mais disponível.');
            }

            $stmt = $pdo->prepare('UPDATE itens SET mesa_id = ? WHERE id = ?');
            $stmt->execute([$mesa_id, $itemAvulsoId]);
            registrarLog($pdo, $mesa_id, "Item avulso vinculado: {$itemAvulso['tipo']} (Patrimônio: {$itemAvulso['patrimonio_protocolo']})");
            $pdo->commit();
        } catch (Throwable $e) {
            if ($pdo->inTransaction()) $pdo->rollBack();
            http_response_code(422);
            exit('Não foi possível vincular o equipamento: ' . e($e->getMessage()));
        }

        header('Location: index.php');
        exit;
    }

    if ($origemItem !== 'novo') exit('Opção de inclusão inválida.');
    $tipo = (string) ($_POST['tipo'] ?? '');
    if (!in_array($tipo, ['Tela', 'CPU', 'Outros'], true)) exit('Tipo de equipamento inválido.');
    $patrimonio = textoObrigatorio($_POST['patrimonio'] ?? '', 'Patrimônio');
    if (!patrimonioDisponivel($pdo, $patrimonio)) {
        http_response_code(409);
        exit('Já existe um equipamento com este patrimônio/protocolo.');
    }
    $ip = ipOpcional($_POST['ip_maquina'] ?? '');

    $stmt = $pdo->prepare("INSERT INTO itens (mesa_id, tipo, nome_personalizado, patrimonio_protocolo, ip_maquina) VALUES (?, ?, ?, ?, ?)");
    $stmt->execute([
        $mesa_id, 
        $tipo,
        $_POST['nome_personalizado'], 
        $patrimonio,
        $ip
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
    $id = inteiroPositivo($_POST['id'] ?? null);
    $stmtBusca = $pdo->prepare("SELECT * FROM itens WHERE id = ?");
    $stmtBusca->execute([$id]);
    $itemAntigo = $stmtBusca->fetch();
    if (!$itemAntigo) exit('Equipamento não encontrado.');
    $patrimonio = textoObrigatorio($_POST['patrimonio'] ?? '', 'Patrimônio');
    if (!patrimonioDisponivel($pdo, $patrimonio, $id)) {
        http_response_code(409);
        exit('Já existe outro equipamento com este patrimônio/protocolo.');
    }

    $mudancas = [];
    
    if ($itemAntigo['patrimonio_protocolo'] != $_POST['patrimonio']) {
        $mudancas[] = "Patrimônio: {$itemAntigo['patrimonio_protocolo']} -> {$_POST['patrimonio']}";
    }
    
    if ($itemAntigo['ip_maquina'] != $_POST['ip_maquina']) {
        $mudancas[] = "IP: {$itemAntigo['ip_maquina']} -> {$_POST['ip_maquina']}";
    }

    $mesaAnteriorId = !empty($itemAntigo['mesa_id']) ? (int) $itemAntigo['mesa_id'] : null;
    $novaMesaId = !empty($_POST['mesa_id']) ? inteiroPositivo($_POST['mesa_id']) : null;
    $mesaFoiAlterada = $mesaAnteriorId !== $novaMesaId;
    $oldMesaName = 'Itens avulsos';
    $newMesaName = 'Itens avulsos';

    if ($mesaFoiAlterada) {
        if ($mesaAnteriorId) {
            $stmtM = $pdo->prepare("SELECT nome FROM mesas WHERE id = ?");
            $stmtM->execute([$mesaAnteriorId]);
            $m = $stmtM->fetch();
            $oldMesaName = $m['nome'] ?? 'Mesa';
        }

        if ($novaMesaId) {
            $stmtM = $pdo->prepare("SELECT nome FROM mesas WHERE id = ?");
            $stmtM->execute([$novaMesaId]);
            $m = $stmtM->fetch();
            $newMesaName = $m['nome'] ?? 'Mesa';
        }
    }

    $stmt = $pdo->prepare("UPDATE itens SET mesa_id = ?, tipo = ?, nome_personalizado = ?, patrimonio_protocolo = ?, ip_maquina = ? WHERE id = ?");
    $stmt->execute([
        $novaMesaId,
        $_POST['tipo'], 
        $_POST['nome_personalizado'], 
        $patrimonio, 
        $_POST['ip_maquina'], 
        $id
    ]);

    if ($mesaFoiAlterada) {
        $descricaoItem = "{$itemAntigo['tipo']} (patrimônio {$itemAntigo['patrimonio_protocolo']})";
        if ($mesaAnteriorId) {
            registrarLog($pdo, $mesaAnteriorId, "Saída por transferência: {$descricaoItem} foi enviado para {$newMesaName}.");
        }
        if ($novaMesaId) {
            registrarLog($pdo, $novaMesaId, "Entrada por transferência: {$descricaoItem} foi recebido de {$oldMesaName}.");
        }
    }

    if (!empty($mudancas)) {
        $msg = "Item {$itemAntigo['tipo']} ({$itemAntigo['patrimonio_protocolo']}) alterado: " . implode(", ", $mudancas);
        registrarLog($pdo, $novaMesaId ?? $mesaAnteriorId, $msg);
    }

    header("Location: " . ($_POST['origem'] == 'avulso' ? "itens_avulsos.php" : "index.php"));
    exit;
}

if ($acao == 'remover_item') {
    $id = inteiroPositivo($_POST['id'] ?? null);
    $stmtBusca = $pdo->prepare("SELECT mesa_id, tipo, patrimonio_protocolo FROM itens WHERE id = ?");
    $stmtBusca->execute([$id]);
    $item = $stmtBusca->fetch();
    
    $stmt = $pdo->prepare("DELETE FROM itens WHERE id = ?");
    $stmt->execute([$id]);
    
    if ($item['mesa_id']) {
        registrarLog($pdo, $item['mesa_id'], "Item removido: {$item['tipo']} (Patrimônio: {$item['patrimonio_protocolo']})");
    }
    
    $url_retorno = (empty($item['mesa_id'])) ? "itens_avulsos.php" : "index.php";
    header("Location: $url_retorno");
    exit;
}

// --- MANUTENÇÃO ---
if ($acao == 'iniciar_manutencao') {
    $item_id = (int) $_POST['item_id'];
    $substituto_id = !empty($_POST['substituto_item_id']) ? (int) $_POST['substituto_item_id'] : null;
    $substituto_ip = trim($_POST['substituto_ip_maquina'] ?? '');

    if ($substituto_ip !== '' && filter_var($substituto_ip, FILTER_VALIDATE_IP) === false) {
        die('O IP informado para o equipamento substituto é inválido.');
    }

    $pdo->beginTransaction();
    try {
        $stmtI = $pdo->prepare("SELECT mesa_id, status FROM itens WHERE id = ? FOR UPDATE");
        $stmtI->execute([$item_id]);
        $item = $stmtI->fetch();
        if (!$item) {
            throw new RuntimeException('Equipamento não encontrado.');
        }
        if ($item['status'] !== 'Ativo') {
            throw new RuntimeException('O equipamento já está em manutenção.');
        }

        if ($substituto_id) {
            $stmtS = $pdo->prepare("
                SELECT i.id, i.tipo, i.nome_personalizado, i.patrimonio_protocolo,
                       i.mesa_id, m.nome AS mesa_nome
                FROM itens i
                LEFT JOIN mesas m ON m.id = i.mesa_id AND m.status = 'ativo'
                WHERE i.id = ? AND i.status = 'Ativo'
                  AND (i.mesa_id IS NULL OR m.id IS NOT NULL)
                  AND NOT EXISTS (
                      SELECT 1 FROM manutencoes mt
                      WHERE mt.substituto_item_id = i.id
                        AND mt.status_manutencao = 'Aberto'
                  )
                FOR UPDATE
            ");
            $stmtS->execute([$substituto_id]);
            $substituto = $stmtS->fetch();
            if (!$substituto) {
                throw new RuntimeException('O equipamento substituto não está mais disponível.');
            }
            if (empty($item['mesa_id'])) {
                throw new RuntimeException('Só é possível vincular um substituto quando o equipamento pertence a uma mesa.');
            }
            if (!empty($substituto['mesa_id']) && (int) $substituto['mesa_id'] === (int) $item['mesa_id']) {
                throw new RuntimeException('Escolha um substituto avulso ou vinculado a outra mesa.');
            }
        }

        $stmt = $pdo->prepare("UPDATE itens SET status = 'Manutenção' WHERE id = ?");
        $stmt->execute([$item_id]);

        $stmtM = $pdo->prepare("
            INSERT INTO manutencoes (item_id, mesa_id, substituto_item_id, substituto_mesa_origem_id, descricao_problema, status_manutencao)
            VALUES (?, ?, ?, ?, ?, 'Aberto')
        ");
        $stmtM->execute([$item_id, $item['mesa_id'], $substituto_id, $substituto['mesa_id'] ?? null, $_POST['problema']]);
        $manutencao_id = (int) $pdo->lastInsertId();

        if ($substituto_id) {
            if ($substituto_ip !== '') {
                $stmt = $pdo->prepare("UPDATE itens SET mesa_id = ?, ip_maquina = ? WHERE id = ?");
                $stmt->execute([$item['mesa_id'], $substituto_ip, $substituto_id]);
            } else {
                $stmt = $pdo->prepare("UPDATE itens SET mesa_id = ? WHERE id = ?");
                $stmt->execute([$item['mesa_id'], $substituto_id]);
            }
        }

        registrarLog($pdo, $item['mesa_id'], "Manutenção iniciada. Problema: {$_POST['problema']}");
        if ($substituto_id) {
            $detalhe_ip = $substituto_ip !== '' ? " com IP {$substituto_ip}" : '';
            $nome_substituto = $substituto['tipo'] === 'Outros'
                ? $substituto['nome_personalizado']
                : $substituto['tipo'];
            $origem_substituto = empty($substituto['mesa_id']) ? 'dos itens avulsos' : "da mesa {$substituto['mesa_nome']}";
            registrarLog(
                $pdo,
                $item['mesa_id'],
                "Equipamento substituto recebido {$origem_substituto}: {$nome_substituto}, patrimônio {$substituto['patrimonio_protocolo']}{$detalhe_ip}."
            );
            if (!empty($substituto['mesa_id'])) {
                registrarLog(
                    $pdo,
                    $substituto['mesa_id'],
                    "Equipamento {$nome_substituto}, patrimônio {$substituto['patrimonio_protocolo']}, transferido temporariamente para a mesa #{$item['mesa_id']} durante manutenção."
                );
            }
        }
        $pdo->commit();
        salvarAnexosManutencao($pdo, $manutencao_id, $_FILES['documentos'] ?? []);
    } catch (Throwable $e) {
        if ($pdo->inTransaction()) $pdo->rollBack();
        die("Não foi possível abrir a manutenção: " . htmlspecialchars($e->getMessage()));
    }

    header("Location: index.php");
    exit;
}

if ($acao == 'trocar_itens') {
    $itemOrigemId = inteiroPositivo($_POST['item_origem_id'] ?? null);
    $itemDestinoId = inteiroPositivo($_POST['item_destino_id'] ?? null);
    if ($itemOrigemId === $itemDestinoId) exit('Selecione dois equipamentos diferentes.');
    $pdo->beginTransaction();
    try {
        $ids = [$itemOrigemId, $itemDestinoId]; sort($ids);
        $stmt = $pdo->prepare("SELECT id, mesa_id, tipo, patrimonio_protocolo, status FROM itens WHERE id IN (?, ?) FOR UPDATE");
        $stmt->execute($ids);
        $porId = [];
        foreach ($stmt->fetchAll() as $registro) $porId[(int) $registro['id']] = $registro;
        if (count($porId) !== 2) throw new RuntimeException('Um dos equipamentos não foi encontrado.');
        $origem = $porId[$itemOrigemId]; $destino = $porId[$itemDestinoId];
        if ($origem['status'] !== 'Ativo' || $destino['status'] !== 'Ativo') throw new RuntimeException('Não é possível trocar equipamento em manutenção.');
        if ($origem['tipo'] !== $destino['tipo']) throw new RuntimeException('A troca deve ser feita entre equipamentos do mesmo tipo.');
        if (empty($origem['mesa_id']) && empty($destino['mesa_id'])) throw new RuntimeException('Selecione pelo menos um equipamento vinculado a uma mesa.');
        if (!empty($origem['mesa_id']) && !empty($destino['mesa_id']) && (int) $origem['mesa_id'] === (int) $destino['mesa_id']) {
            throw new RuntimeException('Os equipamentos já pertencem à mesma mesa.');
        }
        $mesaOrigem = empty($origem['mesa_id']) ? null : buscarMesaAtiva($pdo, $origem['mesa_id'], true);
        $mesaDestino = empty($destino['mesa_id']) ? null : buscarMesaAtiva($pdo, $destino['mesa_id'], true);
        $stmt = $pdo->prepare('UPDATE itens SET mesa_id = ? WHERE id = ?');
        $stmt->execute([$mesaDestino['id'] ?? null, $itemOrigemId]);
        $stmt->execute([$mesaOrigem['id'] ?? null, $itemDestinoId]);
        if ($mesaOrigem !== null) {
            $procedencia = $mesaDestino !== null ? "da mesa {$mesaDestino['nome']}" : 'dos itens avulsos';
            registrarLog($pdo, $mesaOrigem['id'], "Troca: saiu {$origem['tipo']} patrimônio {$origem['patrimonio_protocolo']} e entrou patrimônio {$destino['patrimonio_protocolo']} {$procedencia}.");
        }
        if ($mesaDestino !== null) {
            $procedencia = $mesaOrigem !== null ? "da mesa {$mesaOrigem['nome']}" : 'dos itens avulsos';
            registrarLog($pdo, $mesaDestino['id'], "Troca: saiu {$destino['tipo']} patrimônio {$destino['patrimonio_protocolo']} e entrou patrimônio {$origem['patrimonio_protocolo']} {$procedencia}.");
        }
        $pdo->commit();
    } catch (Throwable $e) {
        if ($pdo->inTransaction()) $pdo->rollBack();
        http_response_code(422);
        exit('Não foi possível realizar a troca: ' . e($e->getMessage()));
    }
    header('Location: index.php'); exit;
}

if ($acao == 'concluir_manutencao') {
    $manutencao_id = (int) $_POST['manutencao_id'];
    $decisao = $_POST['decisao'] ?? 'retornar_anterior';
    $pdo->beginTransaction();
    try {
        $stmtM = $pdo->prepare("SELECT item_id, mesa_id, substituto_item_id, substituto_mesa_origem_id FROM manutencoes WHERE id = ? AND status_manutencao = 'Aberto' FOR UPDATE");
        $stmtM->execute([$manutencao_id]);
        $registro = $stmtM->fetch();
        if (!$registro) {
            throw new RuntimeException('Manutenção não encontrada ou já concluída.');
        }

        $idsBloqueio = [(int) $registro['item_id']];
        if (!empty($registro['substituto_item_id'])) $idsBloqueio[] = (int) $registro['substituto_item_id'];
        sort($idsBloqueio);
        $marcadores = implode(',', array_fill(0, count($idsBloqueio), '?'));
        $stmtItens = $pdo->prepare("SELECT id FROM itens WHERE id IN ({$marcadores}) FOR UPDATE");
        $stmtItens->execute($idsBloqueio);

        if (!empty($registro['substituto_item_id'])) {
            if ($decisao === 'manter_atual') {
                $stmt = $pdo->prepare("UPDATE itens SET status = 'Ativo', mesa_id = NULL WHERE id = ?");
                $stmt->execute([$registro['item_id']]);
                registrarLog(
                    $pdo,
                    $registro['mesa_id'],
                    "Manutenção concluída: equipamento atual #{$registro['substituto_item_id']} permaneceu na mesa e o equipamento anterior #{$registro['item_id']} foi enviado aos itens avulsos."
                );
            } else {
                $stmt = $pdo->prepare("UPDATE itens SET status = 'Ativo', mesa_id = ? WHERE id = ?");
                $stmt->execute([$registro['mesa_id'], $registro['item_id']]);

                $stmt = $pdo->prepare("UPDATE itens SET mesa_id = ? WHERE id = ?");
                $stmt->execute([$registro['substituto_mesa_origem_id'], $registro['substituto_item_id']]);
                $destino_substituto = empty($registro['substituto_mesa_origem_id'])
                    ? 'voltou aos itens avulsos'
                    : "retornou à mesa #{$registro['substituto_mesa_origem_id']}";
                registrarLog(
                    $pdo,
                    $registro['mesa_id'],
                    "Manutenção concluída: equipamento anterior #{$registro['item_id']} retornou à mesa e o equipamento temporário #{$registro['substituto_item_id']} {$destino_substituto}."
                );
                if (!empty($registro['substituto_mesa_origem_id'])) {
                    registrarLog(
                        $pdo,
                        $registro['substituto_mesa_origem_id'],
                        "Equipamento #{$registro['substituto_item_id']} retornou após uso temporário em outra mesa."
                    );
                }
            }
        } else {
            $stmt = $pdo->prepare("UPDATE itens SET status = 'Ativo' WHERE id = ?");
            $stmt->execute([$registro['item_id']]);
            registrarLog($pdo, $registro['mesa_id'], "Manutenção concluída.");
        }

        $stmtM = $pdo->prepare("UPDATE manutencoes SET status_manutencao = 'Concluído', data_fim = NOW() WHERE id = ?");
        $stmtM->execute([$manutencao_id]);
        $pdo->commit();
    } catch (Throwable $e) {
        $pdo->rollBack();
        die("Não foi possível concluir a manutenção: " . htmlspecialchars($e->getMessage()));
    }

    header("Location: index.php");
    exit;
}

if ($acao == 'anexar_documentos') {
    $manutencao_id = (int) $_POST['manutencao_id'];
    $item_id = (int) $_POST['item_id'];
    $stmt = $pdo->prepare("SELECT id FROM manutencoes WHERE id = ? AND item_id = ?");
    $stmt->execute([$manutencao_id, $item_id]);
    if (!$stmt->fetch()) {
        die('Manutenção inválida.');
    }

    salvarAnexosManutencao($pdo, $manutencao_id, $_FILES['documentos'] ?? []);
    header("Location: manutencao.php?item_id=" . $item_id);
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
    $ip = ipOpcional($_POST['ip_maquina'] ?? '');
    $stmtBusca = $pdo->prepare("SELECT mesa_id, tipo, patrimonio_protocolo, ip_maquina FROM itens WHERE id = ?");
    $stmtBusca->execute([$_POST['item_id']]);
    $item = $stmtBusca->fetch();

    $stmt = $pdo->prepare("UPDATE itens SET ip_maquina = ? WHERE id = ?");
    $stmt->execute([$ip, $_POST['item_id']]);
    
    registrarLog($pdo, $item['mesa_id'], "IP do item {$item['tipo']} ({$item['patrimonio_protocolo']}) alterado: '{$item['ip_maquina']}' -> '{$_POST['ip_maquina']}'");
    
    header("Location: index.php");
    exit;
}

// Arquivar mesa (muda status para 'arquivado')
if ($acao == 'arquivar_mesa') {
    $id = inteiroPositivo($_POST['id'] ?? null);
    $stmt = $pdo->prepare("UPDATE mesas SET status = 'arquivado' WHERE id = ?");
    $stmt->execute([$id]);
    registrarLog($pdo, $id, "Mesa arquivada.");
    header("Location: index.php");
    exit;
}

// Reativar mesa (muda status para 'ativo')
if ($acao == 'reativar_mesa') {
    $id = inteiroPositivo($_POST['id'] ?? null);
    $stmt = $pdo->prepare("UPDATE mesas SET status = 'ativo' WHERE id = ?");
    $stmt->execute([$id]);
    registrarLog($pdo, $id, "Mesa reativada.");
    header("Location: arquivo_mesas.php");
    exit;
}
?>
