# 🖥️ Controle de Máquinas

Sistema simplificado para gestão de patrimônio, organização de mesas e controle de manutenção de equipamentos de TI.

## 🚀 Funcionalidades
- **Gestão de Mesas:** Criação, edição e exclusão de estações de trabalho.
- **Inventário:** Adição de itens (Telas, CPUs, Periféricos) com número de patrimônio.
- **Sistema de Busca:** Localize equipamentos por nome ou protocolo em tempo real.
- **Módulo de Manutenção:** Registro de entrada para reparo com histórico de movimentações e status visual no painel principal.
- **Sem Login:** Acesso rápido e direto para redes internas.

## 🛠️ Tecnologias Utilizadas
- **PHP 8.x**
- **MySQL**
- **Bootstrap 5** (Interface Responsiva)
- **PDO** (Conexão Segura)

## 📋 Como Instalar
1. Clone este repositório na pasta `htdocs` do seu XAMPP.
2. Importe o banco de dados utilizando o arquivo `database.sql` (ou os comandos SQL fornecidos).
3. Ajuste as credenciais no arquivo `config.php`.
4. Certifique-se de que os módulos Apache e MySQL estão ativos no XAMPP.
5. Acesse `http://localhost/controle_maquinas`.

## 🗄️ Estrutura do Banco de Dados
O sistema utiliza três tabelas principais:
- `mesas`: Armazena a identificação das estações.
- `itens`: Contém os equipamentos vinculados às mesas.
- `manutencoes`: Registra o histórico e progresso dos reparos.
