<?php

session_start();

if (
    isset($_SESSION["ver_contas_autorizado"]) &&
    $_SESSION["ver_contas_autorizado"] === true
) {
    header("Location: listar.php");
    exit;
}

$erro = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $senha = $_POST["senha"] ?? "";

    if (!is_string($senha) || $senha === "") {
        $erro = "Informe a senha de um funcionário ou do administrador.";
    } else {
        require_once __DIR__ . "/../gerente/segredo.php";

        if (hash_equals(SENHA_ADMINISTRADOR, $senha)) {
            session_regenerate_id(true);
            $_SESSION["ver_contas_autorizado"] = true;
            $_SESSION["ver_contas_usuario"] = "Administrador";

            header("Location: listar.php");
            exit;
        }

        require_once __DIR__ . "/../conexao.php";

        $resultado = mysqli_query(
            $conexao,
            "SELECT nome, senha FROM tb_funcionarios"
        );

        if (!$resultado) {
            error_log("Erro ao consultar as senhas dos funcionários para ver contas.");
            mysqli_close($conexao);
            http_response_code(500);
            exit("Não foi possível verificar a senha do funcionário.");
        }

        $funcionarioAutorizado = null;
        $senhaLegada = null;

        while ($funcionario = mysqli_fetch_assoc($resultado)) {
            $senhaArmazenada = $funcionario["senha"] ?? null;

            if (
                is_string($senhaArmazenada) &&
                password_verify($senha, $senhaArmazenada)
            ) {
                $funcionarioAutorizado = $funcionario;
                break;
            }

            if (
                is_string($senhaArmazenada) &&
                hash_equals($senhaArmazenada, $senha)
            ) {
                $funcionarioAutorizado = $funcionario;
                $senhaLegada = $senhaArmazenada;
                break;
            }
        }

        mysqli_free_result($resultado);

        if ($funcionarioAutorizado !== null) {
            if ($senhaLegada !== null) {
                $senhaHash = password_hash($senha, PASSWORD_DEFAULT);
                $atualizacao = mysqli_prepare(
                    $conexao,
                    "UPDATE tb_funcionarios SET senha = ? WHERE nome = ? AND senha = ?"
                );

                if ($atualizacao) {
                    mysqli_stmt_bind_param(
                        $atualizacao,
                        "sss",
                        $senhaHash,
                        $funcionarioAutorizado["nome"],
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
            $_SESSION["ver_contas_autorizado"] = true;
            $_SESSION["ver_contas_usuario"] = $funcionarioAutorizado["nome"];

            header("Location: listar.php");
            exit;
        }

        mysqli_close($conexao);
        $erro = "Senha de funcionário ou administrador incorreta.";
    }
}

?>
<!DOCTYPE html>
<html lang="pt-BR">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Acesso para ver contas</title>
    <link rel="stylesheet" href="../css/cadastro.css?v=<?= filemtime(__DIR__ . "/../css/cadastro.css") ?>">
</head>

<body>
    <main class="container">
        <a href="../index.html" class="voltar" aria-label="Voltar à página inicial">←</a>
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
                <p>CONSULTA FINANCEIRA</p>
            </div>

            <h1>Ver <span>Contas</span></h1>
            <p class="descricao">
                Acesse com a senha de qualquer funcionário cadastrado ou com a senha administrativa.
            </p>

            <?php if ($erro !== ""): ?>
                <div class="mensagem erro" role="alert">
                    <?= htmlspecialchars($erro, ENT_QUOTES, "UTF-8") ?>
                </div>
            <?php endif; ?>

            <form method="POST" autocomplete="off">
                <div class="campo">
                    <label for="senha">Senha do funcionário ou administrador</label>
                    <input
                        type="password"
                        id="senha"
                        name="senha"
                        placeholder="Digite sua senha"
                        autocomplete="off"
                        required
                        autofocus
                    >
                </div>

                <div class="separador"></div>

                <button type="submit" class="botao-cadastrar">
                    Acessar contas
                </button>
            </form>

            <footer>SISTEMA FINANCEIRO · GESTÃO INTELIGENTE</footer>
        </section>
    </main>
</body>

</html>
