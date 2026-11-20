<?php

session_start();

unset(
    $_SESSION["fornecedor_autorizado"],
    $_SESSION["csrf_fornecedor"]
);

header("Location: acesso.php");
exit;
