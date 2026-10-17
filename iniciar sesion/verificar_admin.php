<?php
// Evita que el navegador cachee la página
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Cache-Control: post-check=0, pre-check=0", false);
header("Pragma: no-cache");

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Si no hay sesión, fuera de aquí
if (!isset($_SESSION["usuario"]) || !isset($_SESSION["rol"])) {
    header("Location: /iniciar sesion/login.php");
    exit();
}
// Si llega aquí, es porque el usuario está autenticado, el script continúa normalmente.
?>