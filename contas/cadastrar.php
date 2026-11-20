<?php

require_once __DIR__ . "/proteger.php";

if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    header("Location: painel.php");
    exit;
}

$token = $_POST["token_csrf"] ?? "";

if (
    !is_string($token) ||
    !hash_equals($_SESSION["csrf_contas"], $token)
) {
    http_response_code(403);
    exit("Solicitação inválida. Atualize a página e tente novamente.");
}

$campos = [
    "codigo",
    "num_doc",
    "descricao",
    "data_compra",
    "data_vencimento",
    "valor",
];
$dados = [];

foreach ($campos as $campo) {
    $valor = $_POST[$campo] ?? "";

    if (!is_string($valor)) {
        header("Location: cadastro.php?erro=dados");
        exit;
    }

    $dados[$campo] = trim($valor);
}

$codigo = filter_var(
    $dados["codigo"],
    FILTER_VALIDATE_INT,
    ["options" => ["min_range" => 1, "max_range" => 2147483647]]
);

if ($codigo === false) {
    header("Location: cadastro.php?erro=codigo");
    exit;
}

$numDoc = filter_var(
    $dados["num_doc"],
    FILTER_VALIDATE_INT,
    ["options" => ["min_range" => 1, "max_range" => 2147483647]]
);

$dataValida = static function (string $data): bool {
    $dataFormatada = DateTime::createFromFormat("!Y-m-d", $data);

    return $dataFormatada !== false &&
        $dataFormatada->format("Y-m-d") === $data;
};

$valor = is_numeric($dados["valor"]) ? (float) $dados["valor"] : 0.0;

if (
    $numDoc === false ||
    preg_match('/\A.{1,30}\z/us', $dados["descricao"]) !== 1 ||
    !$dataValida($dados["data_compra"]) ||
    !$dataValida($dados["data_vencimento"]) ||
    $valor <= 0 ||
    !is_finite($valor)
) {
    header("Location: cadastro.php?erro=dados");
    exit;
}

$numDoc = (int) $numDoc;
$descricao = $dados["descricao"];
$dataCompra = $dados["data_compra"];
$dataVencimento = $dados["data_vencimento"];
$status = "D";

require_once __DIR__ . "/../conexao.php";

$sql = "INSERT INTO tb_contas (codigo, num_doc, descricao, data_compra, data_vencimento, data_pagamento, valor, status)
        SELECT f.codigo, ?, ?, ?, ?, NULL, ?, ?
        FROM tb_fornecedor f
        WHERE f.codigo = ?";
$cadastro = mysqli_prepare($conexao, $sql);

if (!$cadastro) {
    error_log("Erro ao preparar o cadastro de conta.");
    mysqli_close($conexao);
    http_response_code(500);
    exit("Não foi possível processar o cadastro da conta.");
}

mysqli_stmt_bind_param(
    $cadastro,
    "isssdsi",
    $numDoc,
    $descricao,
    $dataCompra,
    $dataVencimento,
    $valor,
    $status,
    $codigo
);

try {
    $executouCadastro = mysqli_stmt_execute($cadastro);
    $erroBanco = $executouCadastro ? 0 : mysqli_stmt_errno($cadastro);
} catch (mysqli_sql_exception $erro) {
    $executouCadastro = false;
    $erroBanco = (int) $erro->getCode();
}

if (!$executouCadastro) {
    error_log("Erro ao cadastrar conta.");
    mysqli_stmt_close($cadastro);
    mysqli_close($conexao);

    if ($erroBanco === 1062) {
        header("Location: cadastro.php?erro=duplicado");
        exit;
    }

    http_response_code(500);
    exit("Não foi possível cadastrar a conta. Verifique a conexão e a estrutura da tabela tb_contas.");
}

$contaCadastrada = mysqli_stmt_affected_rows($cadastro) === 1;
mysqli_stmt_close($cadastro);
mysqli_close($conexao);

if (!$contaCadastrada) {
    header("Location: cadastro.php?erro=fornecedor");
    exit;
}

header("Location: painel.php?sucesso=criada");
exit;
