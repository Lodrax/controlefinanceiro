<?php

require_once __DIR__ . "/proteger.php";

if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    header("Location: listar.php");
    exit;
}

$token = $_POST["token_csrf"] ?? "";

if (
    !is_string($token) ||
    !hash_equals($_SESSION["csrf_fornecedor"], $token)
) {
    http_response_code(403);
    exit("Solicitação inválida. Atualize a página e tente novamente.");
}

$codigoRecebido = $_POST["codigo"] ?? "";
$codigo = is_string($codigoRecebido) ? filter_var($codigoRecebido, FILTER_VALIDATE_INT) : false;

if ($codigo === false || $codigo === null || $codigo < 1) {
    header("Location: listar.php?erro=exclusao");
    exit;
}

require_once __DIR__ . "/../conexao.php";

$exclusao = mysqli_prepare(
    $conexao,
    "DELETE FROM tb_fornecedor
     WHERE codigo = ?
       AND NOT EXISTS (
           SELECT 1
           FROM tb_contas
           WHERE tb_contas.codigo = ?
             AND tb_contas.status = 'D'
       )"
);

if (!$exclusao) {
    error_log("Erro ao preparar a exclusão de fornecedor.");
    mysqli_close($conexao);
    http_response_code(500);
    exit("Não foi possível excluir o fornecedor.");
}

mysqli_stmt_bind_param($exclusao, "ii", $codigo, $codigo);

if (!mysqli_stmt_execute($exclusao)) {
    error_log("Erro ao excluir fornecedor.");
    mysqli_stmt_close($exclusao);
    mysqli_close($conexao);
    http_response_code(500);
    exit("Não foi possível excluir o fornecedor.");
}

$excluido = mysqli_stmt_affected_rows($exclusao) === 1;
mysqli_stmt_close($exclusao);

if (!$excluido) {
    $contaAtiva = mysqli_prepare(
        $conexao,
        "SELECT 1 FROM tb_contas WHERE codigo = ? AND status = 'D' LIMIT 1"
    );

    if (!$contaAtiva) {
        error_log("Erro ao verificar contas em aberto do fornecedor.");
        mysqli_close($conexao);
        http_response_code(500);
        exit("Não foi possível verificar as contas do fornecedor.");
    }

    mysqli_stmt_bind_param($contaAtiva, "i", $codigo);

    if (!mysqli_stmt_execute($contaAtiva)) {
        error_log("Erro ao verificar contas em aberto do fornecedor.");
        mysqli_stmt_close($contaAtiva);
        mysqli_close($conexao);
        http_response_code(500);
        exit("Não foi possível verificar as contas do fornecedor.");
    }

    mysqli_stmt_store_result($contaAtiva);
    $temContaAtiva = mysqli_stmt_num_rows($contaAtiva) > 0;
    mysqli_stmt_close($contaAtiva);
}

mysqli_close($conexao);

if (!$excluido) {
    header("Location: listar.php?erro=" . ($temContaAtiva ? "conta-ativa" : "nao-encontrado"));
    exit;
}

header("Location: listar.php?excluido=1");
exit;
