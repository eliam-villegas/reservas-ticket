<?php
require __DIR__ . '/../config/config.php';
header('Content-Type: application/json; charset=utf-8');

$fecha = $_GET['fecha'] ?? '';
$sql = "
  SELECT 
    a.asiento_cod::STRING AS asiento_cod,
    s.sector || ' - ' || a.asiento AS asiento
  FROM asientos a
  JOIN sectores s ON a.sector_cod = s.sector_cod
  LEFT JOIN reservas r
    ON r.asiento_cod = a.asiento_cod AND r.fecha = :fecha
  WHERE a.ocupado = false
    AND r.reserva_id IS NULL
  ORDER BY s.sector, a.asiento
";
$stmt = $conexion->prepare($sql);
$stmt->execute([':fecha' => $fecha]);
$libres = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Asegura que asiento_cod sea string
foreach ($libres as &$row) {
    $row['asiento_cod'] = (string)$row['asiento_cod'];
}
echo json_encode($libres, JSON_UNESCAPED_UNICODE);
