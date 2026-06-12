🖥️ Controle de Máquinas
Sistema de gestão de inventário de TI, focado em organização de mesas de trabalho, rastreabilidade de patrimônio e monitoramento de manutenções.

🚀 Funcionalidades Principais
Gestão de Mesas: Criação, edição e exclusão de estações de trabalho, incluindo atribuição de Endereço IP diretamente à mesa.

Inventário de Itens: Adição e edição de equipamentos (Telas, CPUs, Periféricos) vinculados a mesas ou como itens avulsos.

Auditoria e Logs: Sistema de histórico automático que registra toda e qualquer movimentação:

Criação e edição de mesas.

Alterações de patrimônio ou tipo de equipamento.

Remoção de itens.

Início, movimentação e conclusão de manutenções.

Módulo de Manutenção: Acompanhamento do status dos itens com histórico detalhado por data e hora.

Busca em Tempo Real: Localize rapidamente equipamentos por nome ou código de patrimônio.

🛠️ Tecnologias Utilizadas
Backend: PHP 8.x com PDO (Segurança contra SQL Injection).

Banco de Dados: MySQL.

Frontend: Bootstrap 5 (Design responsivo e moderno).

Arquitetura: index.php centralizador, acoes.php dedicado ao processamento de lógica de negócio e config.php para persistência e logs.

📋 Como Instalar
Clone este repositório para a pasta htdocs do seu servidor local (XAMPP/WAMP).

Crie um banco de dados chamado controle_maquinas no seu MySQL.

Importe o arquivo database.sql (certifique-se de que a tabela historico_mesas esteja criada para os logs).

Configure as credenciais de acesso ao banco no arquivo config.php.

Acesse no seu navegador: http://localhost/controle_maquinas.

🗄️ Estrutura do Banco de Dados
O sistema utiliza quatro tabelas principais para garantir a integridade dos dados:

mesas: Informações da estação e seu respectivo IP.

itens: Equipamentos e sua vinculação a mesas (ou nulo para itens avulsos).

manutencoes: Registro de defeitos e histórico de reparos.

historico_mesas: Tabela central de auditoria que registra todas as alterações sistêmicas.

💡 Observações de Uso
O IP agora é vinculado à Mesa. Ao editar os dados de uma mesa ou criar uma nova, você deve definir o IP daquela estação.

O histórico de alterações é gerado automaticamente a cada ação de criar, editar, remover ou manter. Você pode consultar esse histórico clicando no botão 🕒 Histórico de cada mesa.