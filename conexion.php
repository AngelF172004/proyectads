<?php
$host = 'localhost'; // O la IP del servidor PostgreSQL
$db   = 'Petlife';
$user = 'postgres';
$pass = '17112018Z';
$charset = 'utf8mb4';

$data = "pgsql:host=$host;port=5432;dbname=$db;user=$user;password=$pass"; // datos de la conexion a la base de datos.

// Opciones adicionales para la conexión
$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION, // Permite lanzar excepciones en caso de error
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,       // Devuelve resultados como arreglos asociativos
    PDO::ATTR_EMULATE_PREPARES   => false,                  // Desactiva la emulación de sentencias preparadas
];

try {
     $pdo = new PDO($data, $user, $pass, $options); // Crea la instancia PDO
     //echo "¡Conectado a la base de datos PostgreSQL!";
} catch (\PDOException $e) {
    echo "ups, error al conectar con la base de datos";
     throw new \PDOException($e->getMessage(), (int)$e->getCode());
}
?>