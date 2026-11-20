<?php

session_start();

if (
    !isset($_SESSION["admin_autorizado"]) ||
    $_SESSION["admin_autorizado"] !== true
) {
    header("Location: acesso.php");
    exit;
}

require_once __DIR__ . "/../conexao.php";

$sql = "SELECT nome FROM tb_gerentes ORDER BY nome ASC";

$resultado = mysqli_query($conexao, $sql);

if (!$resultado) {
    error_log("Erro ao listar gerentes.");
    mysqli_close($conexao);
    http_response_code(500);
    exit("Não foi possível carregar os gerentes.");
}

?>

<!DOCTYPE html>
<html lang="pt-BR">

<head>
    <meta charset="UTF-8">

    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Gerentes Cadastrados</title>

    <link
        rel="stylesheet"
        href="../css/cadastro.css?v=<?= filemtime(__DIR__ . "/../css/cadastro.css") ?>"
    >
</head>

<body>

    <main class="container">

        <a href="painel.php" class="voltar">
            ←
        </a>

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

            <h1>Gerentes <span>Cadastrados</span></h1>

            <p class="descricao">
                Gerenciamento dos gerentes cadastrados no sistema.
            </p>

            <div class="lista-gerentes">

                <?php if (mysqli_num_rows($resultado) > 0): ?>

                    <?php while ($gerente = mysqli_fetch_assoc($resultado)): ?>

                        <div class="item-gerente">

                            <p>
                                <?= htmlspecialchars(
                                    $gerente["nome"],
                                    ENT_QUOTES,
                                    "UTF-8"
                                ) ?>
                            </p>

                            <form
                                action="excluir.php"
                                method="POST"
                                autocomplete="off"
                                data-confirmacao-exclusao
                                data-entidade="gerente"
                                data-nome="<?= htmlspecialchars(
                                    $gerente["nome"],
                                    ENT_QUOTES,
                                    "UTF-8"
                                ) ?>"
                            >

                                <input
                                    type="hidden"
                                    name="nome"
                                    value="<?= htmlspecialchars(
                                        $gerente["nome"],
                                        ENT_QUOTES,
                                        "UTF-8"
                                    ) ?>"
                                >

                                <input
                                    type="hidden"
                                    name="token_csrf"
                                    value="<?= htmlspecialchars(
                                        $_SESSION["token_csrf"],
                                        ENT_QUOTES,
                                        "UTF-8"
                                    ) ?>"
                                >

                                <button
                                    type="button"
                                    class="botao-excluir"
                                    data-abrir-confirmacao
                                >
                                    Excluir
                                </button>

                            </form>

                        </div>

                    <?php endwhile; ?>

                <?php else: ?>

                    <p class="sem-gerentes">
                        Nenhum gerente cadastrado.
                    </p>

                <?php endif; ?>

            </div>

        </section>

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

    </main>

    <script src="../js/confirmacao-exclusao.js" defer></script>
</body>

</html>

<?php

mysqli_free_result($resultado);
mysqli_close($conexao);

?>