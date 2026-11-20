<?php

session_start();

if (
    !isset($_SESSION["contas_autorizado"]) ||
    $_SESSION["contas_autorizado"] !== true
) {
    header("Location: acesso.php");
    exit;
}

if (
    !isset($_SESSION["csrf_contas"]) ||
    !is_string($_SESSION["csrf_contas"])
) {
    $_SESSION["csrf_contas"] = bin2hex(random_bytes(32));
}
