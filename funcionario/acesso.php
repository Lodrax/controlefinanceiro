<?php

session_start();

unset(
    $_SESSION["funcionario_autorizado"],
    $_SESSION["csrf_funcionario"]
);

require_once __DIR__ . "/../conexao.php";
require_once __DIR__ . "/../gerente/segredo.php";

$erro = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $senha = $_POST["senha"] ?? "";

    if (is_string($senha) && $senha !== "") {

        $sql = "SELECT senha FROM tb_gerentes";

        $resultado = mysqli_query($conexao, $sql);

        if (!$resultado) {
            error_log("Erro ao consultar as senhas dos gerentes.");
            mysqli_close($conexao);
            http_response_code(500);
            exit("Não foi possível verificar a senha do gerente.");
        }

        $senhaAutorizada = hash_equals(SENHA_ADMINISTRADOR, $senha);
        $senhaLegada = null;

        if (!$senhaAutorizada) {
            while ($gerente = mysqli_fetch_assoc($resultado)) {
                $senhaArmazenada = $gerente["senha"] ?? null;

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
        }

        mysqli_free_result($resultado);

        if ($senhaAutorizada) {
            if ($senhaLegada !== null) {
                $senhaHash = password_hash($senha, PASSWORD_DEFAULT);
                $atualizacao = mysqli_prepare(
                    $conexao,
                    "UPDATE tb_gerentes SET senha = ? WHERE senha = ?"
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
                            "Não foi possível atualizar uma senha legada de gerente."
                        );
                    }

                    mysqli_stmt_close($atualizacao);
                } else {
                    error_log(
                        "Não foi possível preparar a atualização da senha legada de gerente."
                    );
                }
            }

            mysqli_close($conexao);
            session_regenerate_id(true);
            $_SESSION["funcionario_autorizado"] = true;
            $_SESSION["csrf_funcionario"] = bin2hex(random_bytes(32));

            header("Location: painel.php");
            exit;
        }

        mysqli_close($conexao);
        $erro = "Senha de gerente incorreta.";
    } else {
        $erro = "Informe a senha do gerente.";
    }
}

?>

<!DOCTYPE html>
<html lang="pt-BR">

<head>

    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Acesso aos Funcionários</title>

    <link rel="stylesheet" href="../css/cadastro.css?v=<?= filemtime(__DIR__ . "/../css/cadastro.css") ?>">

</head>

<body>

    <main class="container">

        <a href="../index.html" class="voltar">
            ←
        </a>

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

                <p>ÁREA ADMINISTRATIVA</p>

            </div>

            <h1>Acesso aos <span>Funcionários</span></h1>

            <p class="descricao">
                Para acessar o gerenciamento de funcionários,
                informe a senha de um gerente cadastrado ou a senha administrativa.
            </p>

            <?php if ($erro !== "") { ?>

                <div class="mensagem erro">
                    <?php echo htmlspecialchars($erro); ?>
                </div>

            <?php } ?>

            <form method="POST" autocomplete="off">

                <div class="campo">

                    <label for="senha">Senha do gerente</label>

                    <input
                        type="password"
                        id="senha"
                        name="senha"
                        placeholder="Digite a senha do gerente"
                        autocomplete="off"
                        required
                        autofocus
                    >

                </div>

                <div class="separador"></div>

                <button type="submit" class="botao-cadastrar">
                    Acessar sistema
                </button>

            </form>

            <footer>Sistema Financeiro © 2026</footer>

        </section>

    </main>

</body>

</html>