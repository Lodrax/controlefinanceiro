<?php

session_start();

unset(
    $_SESSION["contas_autorizado"],
    $_SESSION["contas_funcionario"],
    $_SESSION["csrf_contas"]
);

header("Location: acesso.php");
exit;
