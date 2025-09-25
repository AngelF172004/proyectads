<?php
// config.php (RAÍZ)
$DB_HOST = 'localhost';
$DB_NAME = 'petlife5';   // <-- que coincida con tu BD
$DB_USER = 'root';
$DB_PASS = '';          // En XAMPP/Wamp suele ser vacío

$options = [
  PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
  PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
  PDO::ATTR_EMULATE_PREPARES   => false,
];

try {
  $pdo = new PDO("mysql:host=$DB_HOST;dbname=$DB_NAME;charset=utf8mb4", $DB_USER, $DB_PASS, $options);
} catch (PDOException $e) {
  http_response_code(500);
  die("Error de conexión a la BD: " . $e->getMessage());
}

if (session_status() !== PHP_SESSION_ACTIVE) {
  session_start();
}

if (session_status() !== PHP_SESSION_ACTIVE) {
  session_start();
}
