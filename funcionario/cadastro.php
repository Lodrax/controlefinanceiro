<?php

session_start();

if (
    !isset($_SESSION["funcionario_autorizado"]) ||
    $_SESSION["funcionario_autorizado"] !== true
) {
    header("Location: acesso.php");
    exit;
}

if (
    !isset($_SESSION["csrf_funcionario"]) ||
    !is_string($_SESSION["csrf_funcionario"])
) {
    $_SESSION["csrf_funcionario"] = bin2hex(random_bytes(32));
}

?>

<!DOCTYPE html>
<html lang="pt-BR">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cadastrar Funcionário</title>
    <link rel="stylesheet" href="../css/cadastro.css?v=<?= filemtime(__DIR__ . "/../css/cadastro.css") ?>">
</head>

<body>

    <main class="container">

        <a href="painel.php" class="voltar">←</a>

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

            <h1>Cadastrar <span>Funcionário</span></h1>

            <p class="descricao">
                Preencha os dados do novo funcionário.
            </p>

            <?php if (isset($_GET["erro"])): ?>
                <div class="mensagem erro">
                    <?php
                    if ($_GET["erro"] === "dados") {
                        echo "Confira o nome e a senha. A senha deve ter pelo menos 8 caracteres e no máximo 72 bytes.";
                    } elseif ($_GET["erro"] === "duplicado") {
                        echo "Este nome já está cadastrado.";
                    } else {
                        echo "Não foi possível cadastrar o funcionário.";
                    }
                    ?>
                </div>
            <?php endif; ?>

            <?php if (isset($_GET["sucesso"])): ?>
                <div class="mensagem sucesso">
                    Funcionário cadastrado com sucesso.
                </div>
            <?php endif; ?>

            <form action="cadastrar.php" method="POST" autocomplete="off">

                <input
                    type="hidden"
                    name="token_csrf"
                    value="<?= htmlspecialchars(
                        $_SESSION["csrf_funcionario"],
                        ENT_QUOTES,
                        "UTF-8"
                    ) ?>"
                >

                <div class="campo">
                    <label for="nome">Nome do funcionário</label>
                    <input
                        type="text"
                        id="nome"
                        name="nome"
                        placeholder="Digite o nome completo"
                        maxlength="30"
                        autocomplete="off"
                        required
                    >
                </div>

                <div class="campo">
                    <label for="senha">Senha do funcionário</label>
                    <input
                        type="password"
                        id="senha"
                        name="senha"
                        placeholder="Crie uma senha"
                        minlength="8"
                        maxlength="72"
                        autocomplete="off"
                        required
                    >
                    <small>A senha deve possuir pelo menos 8 caracteres e no máximo 72 bytes.</small>
                </div>

                <button type="submit" class="botao-cadastrar">
                    Cadastrar funcionário
                </button>

            </form>

        </section>

    </main>

</body>

</html>
