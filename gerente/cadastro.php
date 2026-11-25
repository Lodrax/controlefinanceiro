<?php

session_start();

if (
    !isset($_SESSION["admin_autorizado"]) ||
    $_SESSION["admin_autorizado"] !== true
) {
    header("Location: acesso.php");
    exit;
}

?>

<!DOCTYPE html>
<html lang="pt-BR">

<head>
    <meta charset="UTF-8">

    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Cadastrar Gerente</title>

    <link rel="stylesheet" href="../css/cadastro.css?v=<?= filemtime(__DIR__ . "/../css/cadastro.css") ?>">
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

            <h1>Cadastrar <span>Gerente</span></h1>

            <p class="descricao">
                Preencha os dados do novo gerente.
            </p>

            <?php if (isset($_GET["erro"])): ?>

                <div class="mensagem erro">

                    <?php

                    if ($_GET["erro"] === "dados") {

                        echo "Preencha os campos corretamente.";

                    } elseif ($_GET["erro"] === "duplicado") {

                        echo "Este nome já está cadastrado.";

                    } else {

                        echo "Não foi possível cadastrar o gerente.";

                    }

                    ?>

                </div>

            <?php endif; ?>

            <?php if (isset($_GET["sucesso"])): ?>

                <div class="mensagem sucesso">
                    Gerente cadastrado com sucesso!
                </div>

            <?php endif; ?>

            <form action="cadastrar.php" method="POST" autocomplete="off">

                <input
                    type="hidden"
                    name="token_csrf"
                    value="<?= htmlspecialchars(
                        $_SESSION["token_csrf"],
                        ENT_QUOTES,
                        "UTF-8"
                    ) ?>"
                >

                <div class="campo">

                    <label for="nome">
                        Nome do gerente
                    </label>

                    <input autocomplete="off"
                        type="text"
                        id="nome"
                        name="nome"
                        placeholder="Digite o nome completo"
                        maxlength="100"
                        autocomplete="off"
                        required
                    >

                </div>

                <div class="campo">

                    <label for="senha">
                        Senha do gerente
                    </label>

                    <input autocomplete="off"
                        type="password"
                        id="senha"
                        name="senha"
                        placeholder="Crie uma senha"
                        minlength="8"
                        autocomplete="off"
                        required
                    >

                    <small>
                        A senha deve possuir pelo menos 8 caracteres.
                    </small>

                </div>

                <button
                    type="submit"
                    class="botao-cadastrar"
                >
                    Cadastrar gerente
                </button>

            </form>

        </section>

            <footer>Sistema Financeiro © 2026</footer>
</main>

</body>

</html>