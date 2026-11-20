<?php

session_start();

unset(
    $_SESSION["ver_contas_autorizado"],
    $_SESSION["ver_contas_usuario"]
);

header("Location: acesso.php");
exit;
