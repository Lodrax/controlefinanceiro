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
    header("Location: cadastro.php");
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
$senha = $_POST["senha"] ?? "";
$nome = is_string($nomeRecebido) ? trim($nomeRecebido) : "";
$nomeValido = preg_match('/\A.{1,30}\z/us', $nome) === 1;
$senhaValida = is_string($senha) && strlen($senha) >= 8 && strlen($senha) <= 72;

if (!$nomeValido || !$senhaValida) {
    header("Location: cadastro.php?erro=dados");
    exit;
}

require_once __DIR__ . "/../conexao.php";

$consulta = mysqli_prepare(
    $conexao,
    "SELECT nome FROM tb_funcionarios WHERE nome = ? LIMIT 1"
);

if (!$consulta) {
    error_log("Erro ao preparar a consulta de funcionários.");
    mysqli_close($conexao);
    http_response_code(500);
    exit("Não foi possível processar o cadastro.");
}

mysqli_stmt_bind_param($consulta, "s", $nome);

if (!mysqli_stmt_execute($consulta)) {
    error_log("Erro ao consultar funcionários.");
    mysqli_stmt_close($consulta);
    mysqli_close($conexao);
    http_response_code(500);
    exit("Não foi possível processar o cadastro.");
}

$resultado = mysqli_stmt_get_result($consulta);

if (mysqli_num_rows($resultado) > 0) {
    mysqli_stmt_close($consulta);
    mysqli_close($conexao);
    header("Location: cadastro.php?erro=duplicado");
    exit;
}

mysqli_stmt_close($consulta);

$senhaHash = password_hash($senha, PASSWORD_DEFAULT);
$cadastro = mysqli_prepare(
    $conexao,
    "INSERT INTO tb_funcionarios (nome, senha) VALUES (?, ?)"
);

if (!$cadastro) {
    error_log("Erro ao preparar o cadastro de funcionário.");
    mysqli_close($conexao);
    http_response_code(500);
    exit("Não foi possível processar o cadastro.");
}

mysqli_stmt_bind_param($cadastro, "ss", $nome, $senhaHash);

if (!mysqli_stmt_execute($cadastro)) {
    error_log("Erro ao cadastrar funcionário.");
    mysqli_stmt_close($cadastro);
    mysqli_close($conexao);
    header("Location: cadastro.php?erro=cadastro");
    exit;
}

mysqli_stmt_close($cadastro);
mysqli_close($conexao);

header("Location: cadastro.php?sucesso=1");
exit;