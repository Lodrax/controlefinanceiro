<?php

require_once __DIR__ . "/proteger.php";

if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    header("Location: listar.php");
    exit;
}

$token = $_POST["token_csrf"] ?? "";
$numDocRecebido = $_POST["num_doc"] ?? "";
$senhaGerente = $_POST["senha_gerente"] ?? "";

if (
    !is_string($token) ||
    !hash_equals($_SESSION["csrf_ver_contas"], $token) ||
    !is_string($numDocRecebido) ||
    !is_string($senhaGerente)
) {
    http_response_code(403);
    exit("Solicitação inválida. Atualize a página e tente novamente.");
}

$numDoc = filter_var(
    $numDocRecebido,
    FILTER_VALIDATE_INT,
    ["options" => ["min_range" => 1, "max_range" => 2147483647]]
);

$consultaRecebida = $_POST["consulta"] ?? "";
$parametrosConsulta = [];
$camposConsulta = [
    "pagar_de",
    "pagar_ate",
    "paga_de",
    "paga_ate",
    "fornecedor_pagar",
    "fornecedor_paga",
];

if (is_string($consultaRecebida) && strlen($consultaRecebida) <= 1000) {
    parse_str($consultaRecebida, $consulta);

    foreach ($camposConsulta as $campo) {
        if (isset($consulta[$campo]) && is_string($consulta[$campo])) {
            $parametrosConsulta[$campo] = $consulta[$campo];
        }
    }
}

$redirecionar = static function (
    string $resultado,
    ?int $numeroDocumento = null
) use ($parametrosConsulta): void {
    $parametrosConsulta["exclusao"] = $resultado;

    if ($numeroDocumento !== null) {
        $parametrosConsulta["num_doc"] = $numeroDocumento;
    }

    $query = http_build_query($parametrosConsulta);
    header("Location: listar.php?" . $query . "#relatorios-contas");
    exit;
};

if ($numDoc === false || $senhaGerente === "") {
    $redirecionar("erro");
}

require_once __DIR__ . "/../conexao.php";

$resultadoGerentes = mysqli_query($conexao, "SELECT senha FROM tb_gerentes");

if (!$resultadoGerentes) {
    error_log("Erro ao consultar as senhas dos gerentes para excluir conta.");
    mysqli_close($conexao);
    http_response_code(500);
    exit("Não foi possível verificar a senha do gerente.");
}

$gerenteAutorizado = false;
$senhaLegada = null;

while ($gerente = mysqli_fetch_assoc($resultadoGerentes)) {
    $senhaArmazenada = $gerente["senha"] ?? null;

    if (is_string($senhaArmazenada) && password_verify($senhaGerente, $senhaArmazenada)) {
        $gerenteAutorizado = true;
        break;
    }

    if (
        is_string($senhaArmazenada) &&
        hash_equals($senhaArmazenada, $senhaGerente)
    ) {
        $gerenteAutorizado = true;
        $senhaLegada = $senhaArmazenada;
        break;
    }
}

mysqli_free_result($resultadoGerentes);

if (!$gerenteAutorizado) {
    mysqli_close($conexao);
    $redirecionar("senha", (int) $numDoc);
}

if ($senhaLegada !== null) {
    $senhaHash = password_hash($senhaGerente, PASSWORD_DEFAULT);
    $atualizacaoSenha = mysqli_prepare(
        $conexao,
        "UPDATE tb_gerentes SET senha = ? WHERE senha = ?"
    );

    if ($atualizacaoSenha) {
        mysqli_stmt_bind_param($atualizacaoSenha, "ss", $senhaHash, $senhaLegada);

        if (!mysqli_stmt_execute($atualizacaoSenha)) {
            error_log("Não foi possível atualizar uma senha legada de gerente.");
        }

        mysqli_stmt_close($atualizacaoSenha);
    } else {
        error_log("Não foi possível preparar a atualização da senha legada de gerente.");
    }
}

$exclusao = mysqli_prepare($conexao, "DELETE FROM tb_contas WHERE num_doc = ? LIMIT 1");

if (!$exclusao) {
    error_log("Erro ao preparar a exclusão de conta.");
    mysqli_close($conexao);
    http_response_code(500);
    exit("Não foi possível excluir a conta.");
}

mysqli_stmt_bind_param($exclusao, "i", $numDoc);

if (!mysqli_stmt_execute($exclusao)) {
    error_log("Erro ao excluir conta.");
    mysqli_stmt_close($exclusao);
    mysqli_close($conexao);
    http_response_code(500);
    exit("Não foi possível excluir a conta.");
}

$contaExcluida = mysqli_stmt_affected_rows($exclusao) === 1;
mysqli_stmt_close($exclusao);
mysqli_close($conexao);

$redirecionar($contaExcluida ? "sucesso" : "indisponivel");
