<?php

require_once __DIR__ . "/proteger.php";
require_once __DIR__ . "/../conexao.php";

$fornecedor = null;
$erro = "";
$codigoRecebido = $_GET["codigo"] ?? "";
$termoRecebido = $_GET["q"] ?? "";
$codigo = is_string($codigoRecebido)
    ? filter_var($codigoRecebido, FILTER_VALIDATE_INT)
    : false;
$termo = is_string($termoRecebido) ? trim($termoRecebido) : "";
$resultado = null;

if ($codigo !== false && $codigo !== null && $codigo > 0) {
    $consulta = mysqli_prepare(
        $conexao,
        "SELECT codigo, nome, endereco, bairro, cidade, estado, cep, email, telefone FROM tb_fornecedor WHERE codigo = ?"
    );

    if (!$consulta) {
        error_log("Erro ao preparar a consulta de fornecedor.");
        mysqli_close($conexao);
        http_response_code(500);
        exit("Não foi possível consultar o fornecedor.");
    }

    mysqli_stmt_bind_param($consulta, "i", $codigo);

    if (!mysqli_stmt_execute($consulta)) {
        error_log("Erro ao consultar fornecedor.");
        mysqli_stmt_close($consulta);
        mysqli_close($conexao);
        http_response_code(500);
        exit("Não foi possível consultar o fornecedor.");
    }

    $resultado = mysqli_stmt_get_result($consulta);
    $fornecedor = mysqli_fetch_assoc($resultado) ?: null;
    mysqli_stmt_close($consulta);

    if ($fornecedor === null) {
        $erro = "Fornecedor não encontrado.";
    }
} elseif ($termo !== "") {
    $filtro = "%" . $termo . "%";
    $consulta = mysqli_prepare(
        $conexao,
        "SELECT codigo, nome, cidade, estado FROM tb_fornecedor WHERE nome LIKE ? ORDER BY nome ASC"
    );

    if (!$consulta) {
        error_log("Erro ao preparar a busca de fornecedores.");
        mysqli_close($conexao);
        http_response_code(500);
        exit("Não foi possível consultar os fornecedores.");
    }

    mysqli_stmt_bind_param($consulta, "s", $filtro);

    if (!mysqli_stmt_execute($consulta)) {
        error_log("Erro ao buscar fornecedores.");
        mysqli_stmt_close($consulta);
        mysqli_close($conexao);
        http_response_code(500);
        exit("Não foi possível consultar os fornecedores.");
    }

    $resultado = mysqli_stmt_get_result($consulta);
}

?>
<!DOCTYPE html>
<html lang="pt-BR">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Consultar Fornecedor</title>
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

            <h1>Consultar <span>Fornecedor</span></h1>
            <p class="descricao">Pesquise pelo nome do fornecedor.</p>

            <form method="GET" autocomplete="off">
                <div class="campo">
                    <label for="q">Nome do fornecedor</label>
                    <input type="search" id="q" name="q" value="<?= htmlspecialchars($termo, ENT_QUOTES, "UTF-8") ?>" required>
                </div>
                <button type="submit" class="botao-cadastrar">Consultar</button>
            </form>

            <?php if ($erro !== ""): ?>
                <div class="mensagem erro"><?= htmlspecialchars($erro, ENT_QUOTES, "UTF-8") ?></div>
            <?php elseif ($fornecedor !== null): ?>
                <div class="item-gerente">
                    <p><strong>Código:</strong> <?= (int) $fornecedor["codigo"] ?></p>
                    <p><strong>Nome:</strong> <?= htmlspecialchars($fornecedor["nome"], ENT_QUOTES, "UTF-8") ?></p>
                    <p><strong>Endereço:</strong> <?= htmlspecialchars($fornecedor["endereco"], ENT_QUOTES, "UTF-8") ?></p>
                    <p><strong>Bairro:</strong> <?= htmlspecialchars($fornecedor["bairro"], ENT_QUOTES, "UTF-8") ?></p>
                    <p><strong>Cidade:</strong> <?= htmlspecialchars($fornecedor["cidade"], ENT_QUOTES, "UTF-8") ?></p>
                    <p><strong>Estado:</strong> <?= htmlspecialchars($fornecedor["estado"], ENT_QUOTES, "UTF-8") ?></p>
                    <p><strong>CEP:</strong> <?= htmlspecialchars($fornecedor["cep"], ENT_QUOTES, "UTF-8") ?></p>
                    <p><strong>E-mail:</strong> <?= htmlspecialchars($fornecedor["email"], ENT_QUOTES, "UTF-8") ?></p>
                    <p><strong>Telefone:</strong> <?= htmlspecialchars($fornecedor["telefone"], ENT_QUOTES, "UTF-8") ?></p>
                    <a class="botao-painel" href="alterar.php?codigo=<?= (int) $fornecedor["codigo"] ?>">Alterar fornecedor</a>
                </div>
            <?php elseif ($resultado !== null): ?>
                <div class="lista-gerentes">
                    <?php if (mysqli_num_rows($resultado) > 0): ?>
                        <?php while ($item = mysqli_fetch_assoc($resultado)): ?>
                            <div class="item-gerente">
                                <p><strong>Código:</strong> <?= (int) $item["codigo"] ?></p>
                                <p><?= htmlspecialchars($item["nome"], ENT_QUOTES, "UTF-8") ?></p>
                                <p><?= htmlspecialchars($item["cidade"], ENT_QUOTES, "UTF-8") ?> - <?= htmlspecialchars($item["estado"], ENT_QUOTES, "UTF-8") ?></p>
                                <a class="botao-painel" href="consultar.php?codigo=<?= (int) $item["codigo"] ?>">Ver detalhes</a>
                            </div>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <p class="sem-gerentes">Nenhum fornecedor encontrado.</p>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
        </section>
    </main>
</body>

</html>
<?php

if ($resultado !== null) {
    mysqli_free_result($resultado);
}

mysqli_close($conexao);
