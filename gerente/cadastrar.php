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
    header("Location: cadastro.php");
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

/* Recebe os dados */

$nome = trim($_POST["nome"] ?? "");
$senha = $_POST["senha"] ?? "";

/* Valida os campos */

if (
    $nome === "" ||
    $senha === "" ||
    strlen($nome) > 100 ||
    strlen($senha) < 8
) {
    header("Location: cadastro.php?erro=dados");
    exit;
}

/* Conecta ao banco */

require_once __DIR__ . "/../conexao.php";

/* Verifica se o nome já existe */

$sql = "SELECT nome FROM tb_gerentes WHERE nome = ? LIMIT 1";

$consulta = mysqli_prepare($conexao, $sql);

if (!$consulta) {
    error_log("Erro ao preparar a consulta de gerentes.");
    http_response_code(500);
    exit("Não foi possível processar o cadastro.");
}

mysqli_stmt_bind_param($consulta, "s", $nome);

if (!mysqli_stmt_execute($consulta)) {
    error_log("Erro ao consultar os gerentes.");
    mysqli_stmt_close($consulta);
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

/* Protege a senha */

$senhaHash = password_hash($senha, PASSWORD_DEFAULT);

/* Insere o gerente */

$sql = "INSERT INTO tb_gerentes (nome, senha) VALUES (?, ?)";

$cadastro = mysqli_prepare($conexao, $sql);

if (!$cadastro) {
    error_log("Erro ao preparar o cadastro.");
    mysqli_close($conexao);
    http_response_code(500);
    exit("Não foi possível processar o cadastro.");
}

mysqli_stmt_bind_param($cadastro, "ss", $nome, $senhaHash);

if (mysqli_stmt_execute($cadastro)) {

    mysqli_stmt_close($cadastro);
    mysqli_close($conexao);

    header("Location: cadastro.php?sucesso=1");
    exit;

} else {

    error_log("Erro ao cadastrar gerente.");

    mysqli_stmt_close($cadastro);
    mysqli_close($conexao);

    header("Location: cadastro.php?erro=cadastro");
    exit;
}