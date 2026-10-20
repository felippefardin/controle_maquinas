<?php 
include 'config.php'; 
include 'header.php'; 
?>

<div class="container py-5">
    <h1 class="fw-bolder mb-4">📖 Tutorial do Sistema</h1>
    
    <div class="card shadow-sm border-0 p-4 mb-4">
        <h4 class="fw-bold text-primary">1. Estrutura do Painel</h4>
        <p>O painel principal exibe todas as suas mesas ativas. Cada mesa é um agrupador de equipamentos.</p>
        <ul>
            <li><strong>Criar Mesa:</strong> Utilize o formulário no topo para dar nome a uma nova estação de trabalho.</li>
            <li><strong>Editar Nome:</strong> Clique diretamente no nome da mesa, altere-o e clique em "Salvar" para registrar a mudança no histórico.</li>
            <li><strong>Arquivar/Deletar:</strong> Use o botão 📁 para remover a mesa da tela principal (enviando-a para o arquivo) ou o botão de exclusão para remover permanentemente.</li>
        </ul>
    </div>

    <div class="card shadow-sm border-0 p-4 mb-4">
        <h4 class="fw-bold text-primary">2. Gestão de Equipamentos</h4>
        <p>Dentro de cada mesa, você pode gerenciar os itens associados:</p>
        <ul>
            <li><strong>Adicionar Equipamento:</strong> Clique no botão "+ Adicionar Equipamento" para vincular um novo item à mesa.</li>
            <li><strong>Configurar IP (Somente CPU):</strong> Ao adicionar uma CPU, um botão ➕ IP aparecerá. Clique nele para abrir o modal e definir o endereço IP da máquina.</li>
            <li><strong>Acesso Remoto:</strong> Após definir o IP, o ícone 🖥️ aparecerá. Clique nele para disparar a Conexão de Área de Trabalho Remota do Windows automaticamente.</li>
        </ul>
    </div>

    <div class="card shadow-sm border-0 p-4 mb-4">
        <h4 class="fw-bold text-primary">3. Manutenção e Auditoria</h4>
        <ul>
            <li><strong>🛠️ Manutenção:</strong> Clique no ícone de ferramentas para iniciar uma ordem de serviço. Você pode adicionar movimentações (ex: "Peça trocada") e concluir o processo, o que reativará o item.</li>
            <li><strong>🕒 Histórico:</strong> Cada alteração de nome de mesa, mudança de IP ou movimentação de item é registrada automaticamente na tabela de histórico de cada mesa.</li>
        </ul>
    </div>
</div>

<?php include 'footer.php'; ?>