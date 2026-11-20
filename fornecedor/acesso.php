<?php

session_start();

if (
    isset($_SESSION["fornecedor_autorizado"]) &&
    $_SESSION["fornecedor_autorizado"] === true
) {
    header("Location: painel.php");
    exit;
}

$erro = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $senha = $_POST["senha"] ?? "";

    if (is_string($senha) && $senha !== "") {
        require_once __DIR__ . "/../conexao.php";

        $resultado = mysqli_query($conexao, "SELECT senha FROM tb_funcionarios");

        if (!$resultado) {
            error_log("Erro ao consultar as senhas dos funcionários.");
            mysqli_close($conexao);
            http_response_code(500);
            exit("Não foi possível verificar a senha do funcionário.");
        }

        $senhaAutorizada = false;
        $senhaLegada = null;

        while ($funcionario = mysqli_fetch_assoc($resultado)) {
            $senhaArmazenada = $funcionario["senha"] ?? null;

            if (
                is_string($senhaArmazenada) &&
                password_verify($senha, $senhaArmazenada)
            ) {
                $senhaAutorizada = true;
                break;
            }

            if (
                is_string($senhaArmazenada) &&
                hash_equals($senhaArmazenada, $senha)
            ) {
                $senhaAutorizada = true;
                $senhaLegada = $senhaArmazenada;
                break;
            }
        }

        mysqli_free_result($resultado);

        if ($senhaAutorizada) {
            if ($senhaLegada !== null) {
                $senhaHash = password_hash($senha, PASSWORD_DEFAULT);
                $atualizacao = mysqli_prepare(
                    $conexao,
                    "UPDATE tb_funcionarios SET senha = ? WHERE senha = ?"
                );

                if ($atualizacao) {
                    mysqli_stmt_bind_param(
                        $atualizacao,
                        "ss",
                        $senhaHash,
                        $senhaLegada
                    );

                    if (!mysqli_stmt_execute($atualizacao)) {
                        error_log(
                            "Não foi possível atualizar uma senha legada de funcionário."
                        );
                    }

                    mysqli_stmt_close($atualizacao);
                } else {
                    error_log(
                        "Não foi possível preparar a atualização da senha legada de funcionário."
                    );
                }
            }

            mysqli_close($conexao);
            session_regenerate_id(true);
            $_SESSION["fornecedor_autorizado"] = true;
            $_SESSION["csrf_fornecedor"] = bin2hex(random_bytes(32));

            header("Location: painel.php");
            exit;
        }

        mysqli_close($conexao);
        $erro = "Senha de funcionário incorreta.";
    } else {
        $erro = "Informe a senha de um funcionário.";
    }
}

?>
<!DOCTYPE html>
<html lang="pt-BR">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Acesso aos Fornecedores</title>
    <link rel="stylesheet" href="../css/cadastro.css?v=<?= filemtime(__DIR__ . "/../css/cadastro.css") ?>">
</head>

<body>
    <main class="container">
        <a href="../index.html" class="voltar" aria-label="Voltar ao início">←</a>
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

            <h1>Acesso a <span>Fornecedores</span></h1>
            <p class="descricao">
                Informe a senha de um funcionário cadastrado para continuar.
            </p>

            <?php if ($erro !== ""): ?>
                <div class="mensagem erro">
                    <?= htmlspecialchars($erro, ENT_QUOTES, "UTF-8") ?>
                </div>
            <?php endif; ?>

            <form method="POST" autocomplete="off">
                <div class="campo">
                    <label for="senha">Senha do funcionário</label>
                    <input
                        type="password"
                        id="senha"
                        name="senha"
                        placeholder="Digite a senha do funcionário"
                        autocomplete="off"
                        required
                        autofocus
                    >
                </div>

                <button type="submit" class="botao-cadastrar">
                    Acessar fornecedores
                </button>
            </form>

            <footer>SISTEMA FINANCEIRO · GESTÃO INTELIGENTE</footer>
        </section>
    </main>
</body>

</html>
