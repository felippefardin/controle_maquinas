<?php
include 'config.php';
include 'header.php';

// Exibe as mesas arquivadas e mantém compatibilidade com remoções antigas.
$stmt = $pdo->query("SELECT * FROM mesas WHERE status IN ('arquivado', 'deletado') ORDER BY id DESC");
$mesas_arquivadas = $stmt->fetchAll();
?>

<div class="container mt-4 archive-page">
    <h2 class="mb-4">📦 Arquivo de Mesas Removidas</h2>
    <a href="index.php" class="btn btn-secondary mb-3">Voltar para o Painel</a>

    <div class="mb-3">
        <input type="text" id="campoBusca" class="form-control" placeholder="Buscar por ID ou nome da mesa..." onkeyup="filtrarArquivos()">
    </div>

    <div class="archive-table-wrap">
    <table class="table table-striped bg-white archive-table">
        <thead>
            <tr>
                <th scope="col" class="archive-id">ID</th>
                <th scope="col" class="archive-name">Nome da Mesa</th>
                <th scope="col" class="archive-action-column">Ações</th>
            </tr>
        </thead>
        <tbody id="tabelaMesas">
            <?php if (empty($mesas_arquivadas)): ?>
                <tr><td colspan="3" class="text-center">Nenhuma mesa arquivada encontrada.</td></tr>
            <?php else: ?>
                <?php foreach ($mesas_arquivadas as $mesa): ?>
                <tr>
                    <td><?= (int) $mesa['id'] ?></td>
                    <td><?= htmlspecialchars($mesa['nome'], ENT_QUOTES, 'UTF-8') ?></td>
                    <td>
                        <div class="archive-actions">
                        <form action="acoes.php?acao=reativar_mesa" method="POST" class="d-inline">
                            <input type="hidden" name="id" value="<?= (int) $mesa['id'] ?>">
                            <button class="btn btn-sm btn-success">Reativar</button>
                        </form>
                        <a href="historico_mesa.php?id=<?= (int) $mesa['id'] ?>" class="btn btn-sm btn-info">Ver Histórico</a>
                        </div>
                    </td>
                </tr>
                <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
    </table>
    </div>
</div>

<script>
function filtrarArquivos() {
    const input = document.getElementById('campoBusca');
    const filtro = input.value.toUpperCase();
    const tabela = document.getElementById('tabelaMesas');
    const linhas = tabela.getElementsByTagName('tr');

    for (let i = 0; i < linhas.length; i++) {
        const colID = linhas[i].getElementsByTagName('td')[0];
        const colNome = linhas[i].getElementsByTagName('td')[1];

        if (colID && colNome) {
            const textoID = colID.textContent || colID.innerText;
            const textoNome = colNome.textContent || colNome.innerText;
            linhas[i].style.display = textoID.toUpperCase().includes(filtro)
                || textoNome.toUpperCase().includes(filtro) ? '' : 'none';
        }
    }
}
</script>

<?php include 'footer.php'; ?>
