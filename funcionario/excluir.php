<?php

session_start();

if (
    !isset($_SESSION["funcionario_autorizado"]) ||
    $_SESSION["funcionario_autorizado"] !== true
) {
    header("Location: acesso.php");
    exit;
}

if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    header("Location: listar.php");
    exit;
}

$token = $_POST["token_csrf"] ?? "";

if (
    !isset($_SESSION["csrf_funcionario"]) ||
    !is_string($token) ||
    !hash_equals($_SESSION["csrf_funcionario"], $token)
) {
    http_response_code(403);
    exit("Solicitação inválida. Atualize a página e tente novamente.");
}

$nomeRecebido = $_POST["nome"] ?? "";
$nome = is_string($nomeRecebido) ? trim($nomeRecebido) : "";

if ($nome === "") {
    header("Location: listar.php?erro=exclusao");
    exit;
}

require_once __DIR__ . "/../conexao.php";

$exclusao = mysqli_prepare(
    $conexao,
    "DELETE FROM tb_funcionarios WHERE nome = ? LIMIT 1"
);

if (!$exclusao) {
    error_log("Erro ao preparar a exclusão de funcionário.");
    mysqli_close($conexao);
    http_response_code(500);
    exit("Não foi possível excluir o funcionário.");
}

mysqli_stmt_bind_param($exclusao, "s", $nome);

if (!mysqli_stmt_execute($exclusao)) {
    error_log("Erro ao excluir funcionário.");
    mysqli_stmt_close($exclusao);
    mysqli_close($conexao);
    http_response_code(500);
    exit("Não foi possível excluir o funcionário.");
}

$excluido = mysqli_stmt_affected_rows($exclusao) === 1;
mysqli_stmt_close($exclusao);
mysqli_close($conexao);

if (!$excluido) {
    header("Location: listar.php?erro=nao-encontrado");
    exit;
}

header("Location: listar.php?excluido=1");
exit;