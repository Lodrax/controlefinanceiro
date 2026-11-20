<?php

require_once __DIR__ . "/proteger.php";

?>
<!DOCTYPE html>
<html lang="pt-BR">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gerenciamento de Fornecedores</title>
    <link rel="stylesheet" href="../css/cadastro.css?v=<?= filemtime(__DIR__ . "/../css/cadastro.css") ?>">
</head>

<body>
    <main class="container painel-container">
        <a href="sair.php" class="voltar" aria-label="Voltar e sair">←</a>
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

            <h1>Gerenciar <span>Fornecedores</span></h1>
            <p class="descricao">Escolha uma das opções abaixo.</p>

            <a href="cadastro.php" class="botao-painel">Cadastrar fornecedor</a>
            <a href="listar.php" class="botao-painel">Listar fornecedores</a>
            <a href="consultar.php" class="botao-painel">Consultar fornecedor</a>
            <a href="alterar.php" class="botao-painel">Alterar fornecedor</a>
            <a href="excluir.php" class="botao-painel">Excluir fornecedor</a>
        </section>

        <footer>Sistema Financeiro &copy; 2026</footer>
    </main>
</body>

</html>
