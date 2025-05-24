<?php
// config.php
$host     = 'cockroach';   // ≠ localhost
$port     = '26257';
$dbname   = 'tickets';
$user     = 'root';
$password = '';            // porque arrancamos con --insecure

try {
    $conexion = new PDO(
        "pgsql:host=$host;port=$port;dbname=$dbname;sslmode=disable",
        $user,
        $password
    );
    $conexion->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $conexion->setAttribute(PDO::ATTR_EMULATE_PREPARES, false);
} catch (PDOException $e) {
    die("❌ Error de conexión: " . $e->getMessage());
}
?>
