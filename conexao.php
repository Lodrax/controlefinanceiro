<?php

$servidor = "localhost";
$usuario = "root";
$senhaBanco = "";
$banco = "controlefinanceiro";

$conexao = mysqli_connect(
    $servidor,
    $usuario,
    $senhaBanco,
    $banco
);

if (!$conexao) {
    error_log("Erro ao conectar ao banco de dados.");

    http_response_code(500);
    exit("Erro ao conectar ao banco de dados.");
}

mysqli_set_charset($conexao, "utf8mb4");