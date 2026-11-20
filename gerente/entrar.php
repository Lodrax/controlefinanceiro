<?php

session_start();

unset(
    $_SESSION["admin_autorizado"],
    $_SESSION["token_csrf"]
);

header("Location: acesso.php");
exit;
