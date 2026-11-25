<?php

require_once __DIR__ . "/proteger.php";
require_once __DIR__ . "/../conexao.php";

$resultado = mysqli_query(
    $conexao,
    "SELECT codigo, nome, cidade, estado FROM tb_fornecedor ORDER BY nome ASC"
);

if (!$resultado) {
    error_log("Erro ao listar fornecedores.");
    mysqli_close($conexao);
    http_response_code(500);
    exit("Não foi possível carregar os fornecedores.");
}

?>
<!DOCTYPE html>
<html lang="pt-BR">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Fornecedores Cadastrados</title>
    <link
        rel="stylesheet"
        href="../css/cadastro.css?v=<?= filemtime(__DIR__ . "/../css/cadastro.css") ?>"
    >
</head>

<body>
    <main class="container">
        <a href="painel.php" class="voltar" aria-label="Voltar ao painel">←</a>
        <a href="../index.html" class="inicio" aria-label="Ir para a página inicial" title="Página inicial">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                <path d="m3 10 9-7 9 7"></path>
                <path d="M5 9v12h14V9"></path>
                <path d="M9 21v-7h6v7"></path>
            </svg>
        </a>

        <section class="cartao">
            <div class="identificacao">
                <span class="linha-dourada"></span>
                <p>SISTEMA FINANCEIRO</p>
            </div>

            <h1>Fornecedores <span>Cadastrados</span></h1>
            <p class="descricao">Consulte, altere ou exclua os fornecedores registrados.</p>

            <?php if (isset($_GET["excluido"])): ?>
                <div class="mensagem sucesso">Fornecedor excluído com sucesso.</div>
            <?php elseif (isset($_GET["alterado"])): ?>
                <div class="mensagem sucesso">Fornecedor alterado com sucesso.</div>
            <?php elseif (isset($_GET["erro"]) && $_GET["erro"] === "conta-ativa"): ?>
                <div class="mensagem erro" role="alert">Não é possível excluir este fornecedor porque ele possui contas em aberto.</div>
            <?php elseif (isset($_GET["erro"])): ?>
                <div class="mensagem erro" role="alert">Não foi possível excluir o fornecedor solicitado.</div>
            <?php endif; ?>

            <div class="lista-gerentes">
                <?php if (mysqli_num_rows($resultado) > 0): ?>
                    <?php while ($fornecedor = mysqli_fetch_assoc($resultado)): ?>
                        <div class="item-gerente">
                            <p><?= htmlspecialchars($fornecedor["nome"], ENT_QUOTES, "UTF-8") ?></p>
                            <p>Código: <?= (int) $fornecedor["codigo"] ?></p>
                            <p><?= htmlspecialchars($fornecedor["cidade"], ENT_QUOTES, "UTF-8") ?> - <?= htmlspecialchars($fornecedor["estado"], ENT_QUOTES, "UTF-8") ?></p>

                            <div class="acoes-fornecedor">
                                <a class="botao-acao" href="consultar.php?codigo=<?= (int) $fornecedor["codigo"] ?>">Consultar</a>
                                <a class="botao-acao" href="alterar.php?codigo=<?= (int) $fornecedor["codigo"] ?>">Alterar</a>
                                <form
                                    action="excluir.php"
                                    method="POST"
                                    autocomplete="off"
                                    data-confirmacao-exclusao
                                    data-entidade="fornecedor"
                                    data-nome="<?= htmlspecialchars($fornecedor["nome"], ENT_QUOTES, "UTF-8") ?>"
                                >
                                    <input type="hidden" name="codigo" value="<?= (int) $fornecedor["codigo"] ?>">
                                    <input type="hidden" name="token_csrf" value="<?= htmlspecialchars($_SESSION["csrf_fornecedor"], ENT_QUOTES, "UTF-8") ?>">
                                    <button type="button" class="botao-excluir" data-abrir-confirmacao>Excluir</button>
                                </form>
                            </div>
                        </div>
                    <?php endwhile; ?>
                <?php else: ?>
                    <p class="sem-gerentes">Nenhum fornecedor cadastrado.</p>
                <?php endif; ?>
            </div>
        </section>
            <footer>Sistema Financeiro © 2026</footer>
</main>
    <dialog class="modal-exclusao-conta" id="modalConfirmacaoExclusao" aria-labelledby="tituloConfirmacaoExclusao">
        <div class="modal-exclusao-conteudo">
            <span class="modal-exclusao-icone" aria-hidden="true">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M3 6h18"></path>
                    <path d="M8 6V4h8v2"></path>
                    <path d="m19 6-1 14H6L5 6"></path>
                    <path d="M10 11v5M14 11v5"></path>
                </svg>
            </span>
            <p class="modal-exclusao-etiqueta">CONFIRMAÇÃO DE EXCLUSÃO</p>
            <h2 id="tituloConfirmacaoExclusao">Excluir registro?</h2>
            <p class="modal-exclusao-descricao" id="descricaoConfirmacaoExclusao"></p>
            <div class="modal-exclusao-acoes">
                <button type="button" class="modal-exclusao-cancelar" data-cancelar-confirmacao>
                    Cancelar
                </button>
                <button type="button" class="modal-exclusao-confirmar" data-confirmar-exclusao>
                    Sim, excluir
                </button>
            </div>
        </div>
    </dialog>
    <script src="../js/confirmacao-exclusao.js" defer></script>
</body>

</html>
<?php

mysqli_free_result($resultado);
mysqli_close($conexao);
