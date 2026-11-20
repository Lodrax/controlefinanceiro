<?php

session_start();

if (
    !isset($_SESSION["fornecedor_autorizado"]) ||
    $_SESSION["fornecedor_autorizado"] !== true
) {
    header("Location: acesso.php");
    exit;
}

if (
    !isset($_SESSION["csrf_fornecedor"]) ||
    !is_string($_SESSION["csrf_fornecedor"])
) {
    $_SESSION["csrf_fornecedor"] = bin2hex(random_bytes(32));
}
