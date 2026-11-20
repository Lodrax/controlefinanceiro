<?php

require_once __DIR__ . "/proteger.php";

?>
<!DOCTYPE html>
<html lang="pt-BR">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gerenciamento de Contas</title>
    <link rel="stylesheet" href="../css/cadastro.css?v=<?= filemtime(__DIR__ . "/../css/cadastro.css") ?>">
</head>

<body>
    <main class="container painel-container">
        <a href="sair.php" class="voltar" aria-label="Sair das contas">←</a>
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

            <h1>Gerenciar <span>Contas</span></h1>
            <p class="descricao">
                Acesso de
                <strong><?= htmlspecialchars(
                    (string) ($_SESSION["contas_funcionario"] ?? ""),
                    ENT_QUOTES,
                    "UTF-8"
                ) ?></strong>.
                Escolha uma opção.
            </p>

            <?php if (isset($_GET["sucesso"]) && $_GET["sucesso"] === "criada"): ?>
                <div class="mensagem sucesso" role="status">
                    Conta cadastrada como devendo (D).
                </div>
            <?php elseif (isset($_GET["erro"]) && $_GET["erro"] === "duplicado"): ?>
                <div class="mensagem erro" role="alert">
                    Já existe uma conta com esse número de documento.
                </div>
            <?php elseif (isset($_GET["erro"])): ?>
                <div class="mensagem erro" role="alert">
                    Confira os dados informados e tente novamente.
                </div>
            <?php endif; ?>

            <a href="cadastro.php" class="botao-painel">Criar conta</a>
            <a href="pagar.php" class="botao-painel">Pagar conta</a>
        </section>

        <footer>Sistema Financeiro &copy; 2026</footer>
    </main>
</body>

</html>
