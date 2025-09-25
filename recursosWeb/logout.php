<?php
// logout.php
require_once dirname(__DIR__) . '/config.php';

if (session_status() === PHP_SESSION_NONE) {
  session_start();
}

// Limpia todas las variables de sesión
$_SESSION = [];

// Elimina la cookie de sesión (opcional, por seguridad extra)
if (ini_get("session.use_cookies")) {
  $params = session_get_cookie_params();
  setcookie(session_name(), '', time() - 42000,
    $params["path"], $params["domain"],
    $params["secure"], $params["httponly"]
  );
}

// Finalmente destruye la sesión
session_destroy();

// Redirige a inicio
header("Location: ../index.php");
exit;
