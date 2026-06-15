<?php 
include 'config.php'; 
include 'header.php'; 

// Busca apenas as mesas que estão com status 'deletado'
$stmt = $pdo->query("SELECT * FROM mesas WHERE status = 'deletado' ORDER BY id DESC");
$mesas_deletadas = $stmt->fetchAll();
?>

<div class="container mt-4">
    <h2 class="mb-4">📦 Arquivo de Mesas Removidas</h2>
    <a href="index.php" class="btn btn-secondary mb-3">Voltar para o Painel</a>

    <div class="mb-3">
        <input type="text" id="campoBusca" class="form-control" placeholder="Buscar por ID ou nome da mesa..." onkeyup="filtrarArquivos()">
    </div>

    <table class="table table-striped bg-white shadow-sm rounded">
        <thead>
            <tr>
                <th>ID</th>
                <th>Nome da Mesa</th>
                <th>Ação</th>
            </tr>
        </thead>
        <tbody id="tabelaMesas">
            <?php if(empty($mesas_deletadas)): ?>
                <tr><td colspan="3" class="text-center">Nenhuma mesa arquivada encontrada.</td></tr>
            <?php else: ?>
                <?php foreach ($mesas_deletadas as $mesa): ?>
                <tr>
                    <td><?= $mesa['id'] ?></td>
                    <td><?= htmlspecialchars($mesa['identificacao']) ?></td>
                    <td>
                        <a href="acoes.php?acao=reativar_mesa&id=<?= $mesa['id'] ?>" class="btn btn-sm btn-success">Reativar</a>
                        <a href="historico_mesa.php?id=<?= $mesa['id'] ?>" class="btn btn-sm btn-info">Ver Histórico</a>
                    </td>
                </tr>
                <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
    </table>
</div>

<script>
function filtrarArquivos() {
    let input = document.getElementById('campoBusca');
    let filtro = input.value.toUpperCase();
    let tabela = document.getElementById("tabelaMesas");
    let linhas = tabela.getElementsByTagName("tr");

    for (let i = 0; i < linhas.length; i++) {
        let colID = linhas[i].getElementsByTagName("td")[0]; // Coluna ID
        let colNome = linhas[i].getElementsByTagName("td")[1]; // Coluna Nome

        if (colID && colNome) {
            let textoID = colID.textContent || colID.innerText;
            let textoNome = colNome.textContent || colNome.innerText;
            
            // Verifica se o filtro aparece no ID ou no Nome
            if (textoID.toUpperCase().indexOf(filtro) > -1 || textoNome.toUpperCase().indexOf(filtro) > -1) {
                linhas[i].style.display = "";
            } else {
                linhas[i].style.display = "none";
            }
        }
    }
}
</script>

<?php include 'footer.php'; ?>