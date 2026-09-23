<?php

require_once __DIR__ . '/../vendor/autoload.php';

use Dotenv\Dotenv;

$dotenv = Dotenv::createImmutable(dirname(__DIR__));
$dotenv->safeLoad();

$host = $_ENV['DB_HOST'];
$port = $_ENV['DB_PORT'];
$dbname = $_ENV['DB_NAME'];
$user = $_ENV['DB_USER'];
$password = $_ENV['DB_PASSWORD'];

try {
    $pdo = new PDO(
        "pgsql:host=$host;port=$port;dbname=$dbname",
        $user,
        $password,
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false
        ]
    );

    // Las fechas automáticas (CURRENT_TIMESTAMP) deben quedar en hora de Bolivia,
    // sin depender de la zona horaria configurada en el servidor PostgreSQL.
    $pdo->exec("SET TIME ZONE 'America/La_Paz'");

    return $pdo;

} catch (PDOException $e) {
    throw new RuntimeException('No se pudo establecer la conexión con la base de datos.');
}