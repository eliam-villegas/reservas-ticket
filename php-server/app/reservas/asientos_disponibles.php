<?php
require '../config/config.php';

if (isset($_GET['fecha'])) {
    $fecha = $_GET['fecha'];
    $reserva_id_actual = $_GET['editar'] ?? null;

    $conteo_asientos = [];
    $nodos_activos = 0;

    foreach ($nodes as $index => $node) {
        $nodePdo = getNodeConnection($index);
        if ($nodePdo) {
            $nodos_activos++;

            $stmt = $nodePdo->prepare("
                SELECT a.asiento_cod, a.asiento 
                FROM asientos a 
                WHERE a.asiento_cod NOT IN (
                    SELECT asiento_cod FROM reservas WHERE fecha = ? AND reserva_id != ?
                )
            ");
            $stmt->execute([$fecha, $reserva_id_actual]);
            $asientos = $stmt->fetchAll(PDO::FETCH_ASSOC);

            foreach ($asientos as $asiento) {
                $key = $asiento['asiento_cod'] . '|' . $asiento['asiento'];
                if (!isset($conteo_asientos[$key])) {
                    $conteo_asientos[$key] = 0;
                }
                $conteo_asientos[$key]++;
            }
        }
    }

    // Asientos válidos si están presentes en la mayoría de los nodos disponibles
    $mayoria = ceil($nodos_activos / 2);
    $asientosFinales = [];

    foreach ($conteo_asientos as $key => $count) {
        if ($count >= $mayoria) {
            list($cod, $asiento) = explode('|', $key);
            $asientosFinales[] = [
                'asiento_cod' => (int)$cod,
                'asiento' => $asiento
            ];
        }
    }

    echo json_encode($asientosFinales);
}
