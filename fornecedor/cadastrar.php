<?php

require_once __DIR__ . "/proteger.php";

if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    header("Location: cadastro.php");
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

$campos = ["nome", "endereco", "bairro", "cidade", "estado", "cep", "email", "telefone"];
$dados = [];

foreach ($campos as $campo) {
    $valor = $_POST[$campo] ?? "";

    if (!is_string($valor)) {
        header("Location: cadastro.php?erro=dados");
        exit;
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
    if (preg_match('/\A.{1,' . $limite . '}\z/us', $dados[$campo]) !== 1) {
        header("Location: cadastro.php?erro=dados");
        exit;
    }
}

if (filter_var($dados["email"], FILTER_VALIDATE_EMAIL) === false) {
    header("Location: cadastro.php?erro=dados");
    exit;
}

require_once __DIR__ . "/../conexao.php";

$cadastro = mysqli_prepare(
    $conexao,
    "INSERT INTO tb_fornecedor (nome, endereco, bairro, cidade, estado, cep, email, telefone) VALUES (?, ?, ?, ?, ?, ?, ?, ?)"
);

if (!$cadastro) {
    error_log("Erro ao preparar o cadastro de fornecedor.");
    mysqli_close($conexao);
    http_response_code(500);
    exit("Não foi possível processar o cadastro.");
}

mysqli_stmt_bind_param(
    $cadastro,
    "ssssssss",
    $dados["nome"],
    $dados["endereco"],
    $dados["bairro"],
    $dados["cidade"],
    $dados["estado"],
    $dados["cep"],
    $dados["email"],
    $dados["telefone"]
);

if (!mysqli_stmt_execute($cadastro)) {
    error_log("Erro ao cadastrar fornecedor.");
    mysqli_stmt_close($cadastro);
    mysqli_close($conexao);
    http_response_code(500);
    exit("Não foi possível cadastrar o fornecedor.");
}

mysqli_stmt_close($cadastro);
mysqli_close($conexao);

header("Location: cadastro.php?sucesso=1");
exit;
