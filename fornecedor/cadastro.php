<?php

require_once __DIR__ . "/proteger.php";

?>
<!DOCTYPE html>
<html lang="pt-BR">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cadastrar Fornecedor</title>
    <link rel="stylesheet" href="../css/cadastro.css?v=<?= filemtime(__DIR__ . "/../css/cadastro.css") ?>">
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

            <h1>Cadastrar <span>Fornecedor</span></h1>
            <p class="descricao">Preencha os dados do fornecedor.</p>

            <?php if (isset($_GET["erro"])): ?>
                <div class="mensagem erro">
                    Não foi possível cadastrar o fornecedor. Confira todos os dados e tente novamente.
                </div>
            <?php endif; ?>

            <?php if (isset($_GET["sucesso"])): ?>
                <div class="mensagem sucesso">Fornecedor cadastrado com sucesso.</div>
            <?php endif; ?>

            <form action="cadastrar.php" method="POST" autocomplete="off">
                    <input type="hidden" name="token_csrf" value="<?= htmlspecialchars($_SESSION["csrf_fornecedor"], ENT_QUOTES, "UTF-8") ?>">
                <div class="campo">
                    <label for="nome">Nome</label>
                    <input autocomplete="off" type="text" id="nome" name="nome" maxlength="30" required>
                </div>

                <div class="campo">
                    <label for="endereco">Endereço</label>
                    <input autocomplete="off" type="text" id="endereco" name="endereco" maxlength="30" required>
                </div>

                <div class="campo">
                    <label for="bairro">Bairro</label>
                    <input autocomplete="off" type="text" id="bairro" name="bairro" maxlength="20" required>
                </div>

                <div class="campo">
                    <label for="cidade">Cidade</label>
                    <input autocomplete="off" type="text" id="cidade" name="cidade" maxlength="20" required>
                </div>

                <div class="campo">
                    <label for="estado">Estado</label>
                    <input autocomplete="off" type="text" id="estado" name="estado" maxlength="10" required>
                </div>

                <div class="campo">
                    <label for="cep">CEP</label>
                    <input autocomplete="off" type="text" id="cep" name="cep" maxlength="10" required>
                </div>

                <div class="campo">
                    <label for="email">E-mail</label>
                    <input autocomplete="off" type="email" id="email" name="email" maxlength="30" required>
                </div>

                <div class="campo">
                    <label for="telefone">Telefone</label>
                    <input autocomplete="off" type="tel" id="telefone" name="telefone" maxlength="15" required>
                </div>

                <button type="submit" class="botao-cadastrar">Cadastrar fornecedor</button>
            </form>
        </section>
    </main>
</body>

</html>
