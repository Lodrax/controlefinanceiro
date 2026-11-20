<?php

session_start();

if (
    !isset($_SESSION["ver_contas_autorizado"]) ||
    $_SESSION["ver_contas_autorizado"] !== true
) {
    header("Location: acesso.php");
    exit;
}

if (
    !isset($_SESSION["csrf_ver_contas"]) ||
    !is_string($_SESSION["csrf_ver_contas"])
) {
    $_SESSION["csrf_ver_contas"] = bin2hex(random_bytes(32));
}
