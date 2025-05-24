<?php
require __DIR__ . '/../vendor/autoload.php';

// Carga las variables de .env
$dotenv = Dotenv\Dotenv::createImmutable(__DIR__ . '/..');
$dotenv->load();

// Lee valores
$host = $_ENV['DB_HOST'];
$port = $_ENV['DB_PORT'];
$db   = $_ENV['DB_NAME'];
$user = $_ENV['DB_USER'];
$pass = $_ENV['DB_PASS'];

// Prepara el DSN de PostgreSQL con SSL
$dsn = "pgsql:host={$host};port={$port};dbname={$db};sslmode=require";

try {
    $pdo = new PDO($dsn, $user, $pass, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    ]);
} catch (PDOException $e) {
    // Si hay error de conexión, se muestra por pantalla
    echo "Error de conexión a la BD: " . $e->getMessage();
    exit;
}

