<?php

require_once __DIR__ . "/proteger.php";
require_once __DIR__ . "/../conexao.php";

$resultadoFornecedores = mysqli_query(
    $conexao,
    "SELECT codigo, nome FROM tb_fornecedor ORDER BY nome ASC"
);

if (!$resultadoFornecedores) {
    error_log("Erro ao carregar fornecedores para o cadastro de contas.");
    mysqli_close($conexao);
    http_response_code(500);
    exit("Não foi possível carregar os fornecedores.");
}

$fornecedores = [];

while ($fornecedor = mysqli_fetch_assoc($resultadoFornecedores)) {
    $fornecedores[] = $fornecedor;
}

mysqli_free_result($resultadoFornecedores);
mysqli_close($conexao);

?>
<!DOCTYPE html>
<html lang="pt-BR">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cadastrar Conta</title>
    <link rel="stylesheet" href="../css/cadastro.css?v=<?= filemtime(__DIR__ . "/../css/cadastro.css") ?>">
    <script src="../js/fornecedores-conta.js?v=<?= filemtime(__DIR__ . "/../js/fornecedores-conta.js") ?>" defer></script>
</head>

<body>
    <main class="container">
        <a href="painel.php" class="voltar" aria-label="Voltar às opções de contas">←</a>
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

            <h1>Criar <span>Conta</span></h1>
            <p class="descricao">
                Preencha os dados da conta. Ela será cadastrada como devendo (D) até ser paga.
            </p>

            <?php if (isset($_GET["erro"]) && $_GET["erro"] === "duplicado"): ?>
                <div class="mensagem erro" role="alert">
                    Já existe uma conta com esse número de documento.
                </div>
            <?php elseif (isset($_GET["erro"]) && $_GET["erro"] === "fornecedor"): ?>
                <div class="mensagem erro" role="alert">
                    O código informado não pertence a um fornecedor cadastrado.
                </div>
            <?php elseif (isset($_GET["erro"]) && $_GET["erro"] === "codigo"): ?>
                <div class="mensagem erro" role="alert">
                    Informe um código de fornecedor válido.
                </div>
            <?php elseif (isset($_GET["erro"])): ?>
                <div class="mensagem erro" role="alert">
                    Confira os dados informados e tente novamente.
                </div>
            <?php endif; ?>

            <form action="cadastrar.php" method="POST" class="formulario-contas" autocomplete="off">
                <input
                    type="hidden"
                    name="token_csrf"
                    value="<?= htmlspecialchars(
                        $_SESSION["csrf_contas"],
                        ENT_QUOTES,
                        "UTF-8"
                    ) ?>"
                >

                <div class="campo">
                    <label for="codigo">Código do fornecedor</label>
                    <div class="seletor-fornecedor">
                        <input type="number" id="codigo" name="codigo" min="1" max="2147483647" step="1" required
                            aria-describedby="ajuda-fornecedor" aria-expanded="false" aria-controls="lista-fornecedores">
                        <button type="button" class="botao-fornecedores" aria-label="Mostrar fornecedores" aria-controls="lista-fornecedores" aria-expanded="false">
                            <span aria-hidden="true">⌃</span>
                        </button>
                        <ul class="lista-fornecedores" id="lista-fornecedores" hidden>
                            <?php foreach ($fornecedores as $fornecedor): ?>
                                <li>
                                    <button
                                        type="button"
                                        class="opcao-fornecedor"
                                        data-codigo="<?= htmlspecialchars((string) $fornecedor["codigo"], ENT_QUOTES, "UTF-8") ?>"
                                    >
                                        <span><?= htmlspecialchars($fornecedor["nome"], ENT_QUOTES, "UTF-8") ?></span>
                                        <small>Código <?= htmlspecialchars((string) $fornecedor["codigo"], ENT_QUOTES, "UTF-8") ?></small>
                                    </button>
                                </li>
                            <?php endforeach; ?>
                            <?php if (!$fornecedores): ?>
                                <li class="fornecedores-vazios">Nenhum fornecedor cadastrado.</li>
                            <?php endif; ?>
                        </ul>
                    </div>
                    <small id="ajuda-fornecedor">Digite o código ou selecione um fornecedor da lista.</small>
                </div>

                <div class="campo">
                    <label for="num_doc">Número do documento</label>
                    <input type="number" id="num_doc" name="num_doc" min="1" max="2147483647" required>
                </div>

                <div class="campo">
                    <label for="descricao">Descrição</label>
                    <input type="text" id="descricao" name="descricao" maxlength="30" required>
                </div>

                <div class="campo">
                    <label for="valor">Valor (R$)</label>
                    <input type="number" id="valor" name="valor" min="0.01" step="0.01" required>
                </div>

                <div class="campo">
                    <label for="data_compra">Data da compra</label>
                    <input type="date" id="data_compra" name="data_compra" required>
                </div>

                <div class="campo">
                    <label for="data_vencimento">Data de vencimento</label>
                    <input type="date" id="data_vencimento" name="data_vencimento" required>
                </div>

                <button type="submit" class="botao-cadastrar botao-contas">
                    Cadastrar conta
                </button>
            </form>

        </section>
        <footer>Sistema Financeiro © 2026</footer>
    </main>
</body>

</html>
