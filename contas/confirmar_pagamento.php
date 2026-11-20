<?php

require_once __DIR__ . "/proteger.php";

if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    header("Location: pagar.php");
    exit;
}

$token = $_POST["token_csrf"] ?? "";
$numDocRecebido = $_POST["num_doc"] ?? "";

if (
    !is_string($token) ||
    !hash_equals($_SESSION["csrf_contas"], $token) ||
    !is_string($numDocRecebido)
) {
    http_response_code(403);
    exit("Solicitação inválida. Atualize a página e tente novamente.");
}

$numDoc = filter_var(
    $numDocRecebido,
    FILTER_VALIDATE_INT,
    ["options" => ["min_range" => 1, "max_range" => 2147483647]]
);

if ($numDoc === false) {
    header("Location: pagar.php?erro=dados");
    exit;
}

require_once __DIR__ . "/../conexao.php";

$pagamento = mysqli_prepare(
    $conexao,
    "UPDATE tb_contas SET status = 'P', data_pagamento = CURDATE() WHERE num_doc = ? AND status = 'D'"
);

if (!$pagamento) {
    error_log("Erro ao preparar o pagamento da conta.");
    mysqli_close($conexao);
    http_response_code(500);
    exit("Não foi possível processar o pagamento.");
}

mysqli_stmt_bind_param($pagamento, "i", $numDoc);

if (!mysqli_stmt_execute($pagamento)) {
    error_log("Erro ao registrar o pagamento da conta.");
    mysqli_stmt_close($pagamento);
    mysqli_close($conexao);
    http_response_code(500);
    exit("Não foi possível registrar o pagamento.");
}

$pagamentoAtualizado = mysqli_stmt_affected_rows($pagamento) === 1;
mysqli_stmt_close($pagamento);
mysqli_close($conexao);

if (!$pagamentoAtualizado) {
    header("Location: pagar.php?erro=indisponivel");
    exit;
}

header("Location: pagar.php?sucesso=1");
exit;
