<?php

require_once __DIR__ . "/proteger.php";
require_once __DIR__ . "/../conexao.php";

$codigoRecebido = $_POST["codigo"] ?? $_GET["codigo"] ?? "";
$codigo = is_string($codigoRecebido)
    ? filter_var($codigoRecebido, FILTER_VALIDATE_INT)
    : false;

if ($codigo === false || $codigo === null || $codigo < 1) {
    if ($_SERVER["REQUEST_METHOD"] !== "GET") {
        mysqli_close($conexao);
        header("Location: listar.php");
        exit;
    }

    $fornecedores = mysqli_query(
        $conexao,
        "SELECT codigo, nome, cidade, estado FROM tb_fornecedor ORDER BY nome ASC"
    );

    if (!$fornecedores) {
        error_log("Erro ao carregar fornecedores para alteração.");
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
        <title>Alterar Fornecedor</title>
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

                <h1>Alterar <span>Fornecedor</span></h1>
                <p class="descricao">Selecione o fornecedor que deseja alterar.</p>

                <div class="lista-gerentes">
                    <?php if (mysqli_num_rows($fornecedores) > 0): ?>
                        <?php while ($item = mysqli_fetch_assoc($fornecedores)): ?>
                            <div class="item-gerente">
                                <p><strong>Código:</strong> <?= (int) $item["codigo"] ?></p>
                                <p><?= htmlspecialchars($item["nome"], ENT_QUOTES, "UTF-8") ?></p>
                                <p><?= htmlspecialchars($item["cidade"], ENT_QUOTES, "UTF-8") ?> - <?= htmlspecialchars($item["estado"], ENT_QUOTES, "UTF-8") ?></p>
                                <a class="botao-painel" href="alterar.php?codigo=<?= (int) $item["codigo"] ?>">Alterar</a>
                            </div>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <p class="sem-gerentes">Nenhum fornecedor cadastrado.</p>
                    <?php endif; ?>
                </div>
            </section>
        </main>
    </body>

    </html>
    <?php

    mysqli_free_result($fornecedores);
    mysqli_close($conexao);
    exit;
}

$erro = "";
$sucesso = isset($_GET["sucesso"]);

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $token = $_POST["token_csrf"] ?? "";

    if (
        !is_string($token) ||
        !hash_equals($_SESSION["csrf_fornecedor"], $token)
    ) {
        mysqli_close($conexao);
        http_response_code(403);
        exit("Solicitação inválida. Atualize a página e tente novamente.");
    }

    $campos = ["nome", "endereco", "bairro", "cidade", "estado", "cep", "email", "telefone"];
    $dados = [];

    foreach ($campos as $campo) {
        $valor = $_POST[$campo] ?? "";

        if (!is_string($valor)) {
            $erro = "dados";
            break;
        }

        $dados[$campo] = trim($valor);
    }

    $limites = [
        "nome" => 30,
        "endereco" => 30,
        "bairro" => 20,
        "cidade" => 20,
        "estado" => 10,
        "cep" => 10,
        "email" => 30,
        "telefone" => 15,
    ];

    foreach ($limites as $campo => $limite) {
        if (
            !isset($dados[$campo]) ||
            preg_match('/\A.{1,' . $limite . '}\z/us', $dados[$campo]) !== 1
        ) {
            $erro = "dados";
            break;
        }
    }

    if (
        $erro === "" &&
        filter_var($dados["email"], FILTER_VALIDATE_EMAIL) === false
    ) {
        $erro = "dados";
    }

    if ($erro === "") {
        $atualizacao = mysqli_prepare(
            $conexao,
            "UPDATE tb_fornecedor SET nome = ?, endereco = ?, bairro = ?, cidade = ?, estado = ?, cep = ?, email = ?, telefone = ? WHERE codigo = ?"
        );

        if (!$atualizacao) {
            error_log("Erro ao preparar a alteração de fornecedor.");
            mysqli_close($conexao);
            http_response_code(500);
            exit("Não foi possível alterar o fornecedor.");
        }

        mysqli_stmt_bind_param(
            $atualizacao,
            "ssssssssi",
            $dados["nome"],
            $dados["endereco"],
            $dados["bairro"],
            $dados["cidade"],
            $dados["estado"],
            $dados["cep"],
            $dados["email"],
            $dados["telefone"],
            $codigo
        );

        if (!mysqli_stmt_execute($atualizacao)) {
            error_log("Erro ao alterar fornecedor.");
            mysqli_stmt_close($atualizacao);
            mysqli_close($conexao);
            http_response_code(500);
            exit("Não foi possível alterar o fornecedor.");
        }

        mysqli_stmt_close($atualizacao);
        mysqli_close($conexao);
        header("Location: alterar.php?codigo=" . $codigo . "&sucesso=1");
        exit;
    }
}

$consulta = mysqli_prepare(
    $conexao,
    "SELECT codigo, nome, endereco, bairro, cidade, estado, cep, email, telefone FROM tb_fornecedor WHERE codigo = ?"
);

if (!$consulta) {
    error_log("Erro ao preparar a busca do fornecedor para alteração.");
    mysqli_close($conexao);
    http_response_code(500);
    exit("Não foi possível carregar o fornecedor.");
}

mysqli_stmt_bind_param($consulta, "i", $codigo);

if (!mysqli_stmt_execute($consulta)) {
    error_log("Erro ao carregar fornecedor para alteração.");
    mysqli_stmt_close($consulta);
    mysqli_close($conexao);
    http_response_code(500);
    exit("Não foi possível carregar o fornecedor.");
}

$resultado = mysqli_stmt_get_result($consulta);
$fornecedor = mysqli_fetch_assoc($resultado);
mysqli_stmt_close($consulta);

if (!$fornecedor) {
    mysqli_close($conexao);
    header("Location: listar.php?erro=nao-encontrado");
    exit;
}

mysqli_close($conexao);

?>
<!DOCTYPE html>
<html lang="pt-BR">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Alterar Fornecedor</title>
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

            <h1>Alterar <span>Fornecedor</span></h1>
            <p class="descricao">Atualize os dados do fornecedor código <?= (int) $fornecedor["codigo"] ?>.</p>

            <?php if ($erro !== ""): ?>
                <div class="mensagem erro">Confira os campos informados e tente novamente.</div>
            <?php elseif ($sucesso): ?>
                <div class="mensagem sucesso">Fornecedor alterado com sucesso.</div>
            <?php endif; ?>

            <form method="POST" autocomplete="off">
                <input type="hidden" name="codigo" value="<?= (int) $fornecedor["codigo"] ?>">
                <input type="hidden" name="token_csrf" value="<?= htmlspecialchars($_SESSION["csrf_fornecedor"], ENT_QUOTES, "UTF-8") ?>">

                <div class="campo">
                    <label for="nome">Nome / razão social</label>
                    <input type="text" id="nome" name="nome" maxlength="30" value="<?= htmlspecialchars($fornecedor["nome"], ENT_QUOTES, "UTF-8") ?>" required>
                </div>

                <div class="campo">
                    <label for="endereco">Endereço</label>
                    <input type="text" id="endereco" name="endereco" maxlength="30" value="<?= htmlspecialchars($fornecedor["endereco"], ENT_QUOTES, "UTF-8") ?>" required>
                </div>

                <div class="campo">
                    <label for="bairro">Bairro</label>
                    <input type="text" id="bairro" name="bairro" maxlength="20" value="<?= htmlspecialchars($fornecedor["bairro"], ENT_QUOTES, "UTF-8") ?>" required>
                </div>

                <div class="campo">
                    <label for="cidade">Cidade</label>
                    <input type="text" id="cidade" name="cidade" maxlength="20" value="<?= htmlspecialchars($fornecedor["cidade"], ENT_QUOTES, "UTF-8") ?>" required>
                </div>

                <div class="campo">
                    <label for="estado">Estado</label>
                    <input type="text" id="estado" name="estado" maxlength="10" value="<?= htmlspecialchars($fornecedor["estado"], ENT_QUOTES, "UTF-8") ?>" required>
                </div>

                <div class="campo">
                    <label for="cep">CEP</label>
                    <input type="text" id="cep" name="cep" maxlength="10" value="<?= htmlspecialchars($fornecedor["cep"], ENT_QUOTES, "UTF-8") ?>" required>
                </div>

                <div class="campo">
                    <label for="email">E-mail</label>
                    <input type="email" id="email" name="email" maxlength="30" value="<?= htmlspecialchars($fornecedor["email"], ENT_QUOTES, "UTF-8") ?>" required>
                </div>

                <div class="campo">
                    <label for="telefone">Telefone</label>
                    <input type="tel" id="telefone" name="telefone" maxlength="15" value="<?= htmlspecialchars($fornecedor["telefone"], ENT_QUOTES, "UTF-8") ?>" required>
                </div>

                <button type="submit" class="botao-cadastrar">Salvar alterações</button>
            </form>
        </section>
    </main>
</body>

</html>
