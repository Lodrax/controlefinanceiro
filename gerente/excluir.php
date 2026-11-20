<?php

session_start();

if (
    !isset($_SESSION["admin_autorizado"]) ||
    $_SESSION["admin_autorizado"] !== true
) {
    header("Location: acesso.php");
    exit;
}

if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    header("Location: listar.php");
    exit;
}

/* Verifica o token de segurança */

$token = $_POST["token_csrf"] ?? "";

if (
    !isset($_SESSION["token_csrf"]) ||
    !is_string($token) ||
    !hash_equals($_SESSION["token_csrf"], $token)
) {
    http_response_code(403);
    exit("Solicitação inválida. Atualize a página e tente novamente.");
}

/* Recebe o nome */

$nome = trim($_POST["nome"] ?? "");

if ($nome === "") {
    header("Location: listar.php");
    exit;
}

/* Conecta ao banco */

require_once __DIR__ . "/../conexao.php";

/* Exclui um registro com o nome informado */

$sql = "DELETE FROM tb_gerentes WHERE nome = ? LIMIT 1";

$consulta = mysqli_prepare($conexao, $sql);

if (!$consulta) {
    error_log("Erro ao preparar a exclusão.");
    mysqli_close($conexao);
    http_response_code(500);
    exit("Não foi possível excluir o gerente.");
}

mysqli_stmt_bind_param($consulta, "s", $nome);

if (mysqli_stmt_execute($consulta)) {

    mysqli_stmt_close($consulta);
    mysqli_close($conexao);

    header("Location: listar.php");
    exit;

}

error_log("Erro ao excluir gerente.");

mysqli_stmt_close($consulta);
mysqli_close($conexao);

http_response_code(500);

exit("Não foi possível excluir o gerente.");