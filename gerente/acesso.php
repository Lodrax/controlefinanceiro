<?php

session_start();

require_once __DIR__ . "/segredo.php";

if (
    isset($_SESSION["admin_autorizado"]) &&
    $_SESSION["admin_autorizado"] === true
) {
    header("Location: painel.php");
    exit;
}

$erro = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $senha = $_POST["senha"] ?? "";

if (hash_equals(SENHA_ADMINISTRADOR, $senha)) {        session_regenerate_id(true);

        $_SESSION["admin_autorizado"] = true;

        $_SESSION["token_csrf"] = bin2hex(random_bytes(32));

        header("Location: painel.php");
        exit;

    } else {

        $erro = "Senha administrativa incorreta.";

    }
}

?>

<!DOCTYPE html>
<html lang="pt-BR">

<head>
    <meta charset="UTF-8">

    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Acesso Administrativo</title>

    <link rel="stylesheet" href="../css/cadastro.css?v=<?= filemtime(__DIR__ . "/../css/cadastro.css") ?>">
</head>

<body>

    <main class="container">

        <a href="../index.html" class="voltar">←</a>

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

            <h1>Acesso <span>Administrativo</span></h1>

            <p class="descricao">
                Digite a senha administrativa para continuar.
            </p>

            <?php if ($erro !== ""): ?>

                <div class="mensagem erro">
                    <?= htmlspecialchars(
                        $erro,
                        ENT_QUOTES,
                        "UTF-8"
                    ) ?>
                </div>

            <?php endif; ?>

            <form method="POST" autocomplete="off">

                <div class="campo">

                    <label for="senha">
                        Senha do administrador
                    </label>

                    <input
                        type="password"
                        id="senha"
                        name="senha"
                        placeholder="Digite sua senha"
                        autocomplete="off"
                        required
                    >

                </div>

                <button
                    type="submit"
                    class="botao-cadastrar"
                >
                    Acessar sistema
                </button>

            </form>

        </section>

            <footer>Sistema Financeiro © 2026</footer>
</main>

</body>

</html>