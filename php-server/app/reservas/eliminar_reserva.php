<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);         // si estás en desarrollo
ini_set('log_errors', 1);
require '../config/config.php';

session_start();

if (!isset($_GET['id'])) {
    die('❌ Parámetro reserva_id requerido');
}
$reservaId = ($_GET['id']);
$userId    = $_SESSION['usuario_id'] ?? null;
error_log("DEBUG eliminar_reserva.php → reservaId=$reservaId, userId=$userId");
try {
    $conexion->beginTransaction();

    // Obtener asiento asociado
    $stmt = $conexion->prepare(
        'SELECT asiento_cod FROM reservas 
         WHERE reserva_id = :r AND usuario_id = :u FOR UPDATE'
    );
    $stmt->execute([':r' => $reservaId, ':u' => $userId]);
    $fila = $stmt->fetch(PDO::FETCH_ASSOC);
    error_log('DEBUG resultado SELECT: ' . var_export($fila, true));
    if (!$fila) {
        throw new Exception('❌ Reserva no encontrada o no autorizada.');
    }
    $as = $fila['asiento_cod'];

    // Eliminar reserva
    $stmt = $conexion->prepare(
        'DELETE FROM reservas WHERE reserva_id = :r'
    );
    $stmt->execute([':r' => $reservaId]);

    // Liberar asiento
    $stmt = $conexion->prepare(
        'UPDATE asientos SET ocupado = false WHERE asiento_cod = :a'
    );
    $stmt->execute([':a' => $as]);

    $conexion->commit();
    header('Location: reservar.php?msg=del');
    exit;
} catch (Exception $e) {
    $conexion->rollBack();
    die('❌ Error al eliminar reserva: ' . $e->getMessage());
}
?>