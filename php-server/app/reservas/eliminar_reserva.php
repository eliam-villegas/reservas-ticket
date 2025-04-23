<?php
require '../config/config.php';

if (isset($_GET['asiento_cod'])) {
    // Eliminar en todos los nodos
    foreach ($nodes as $index => $node) {
        $nodePdo = getNodeConnection($index);
        if ($nodePdo) {
            $stmt = $nodePdo->prepare("DELETE FROM reservas WHERE asiento_cod = ?");
            $stmt->execute([$_GET['asiento_cod']]);
        }
    }
    header('Location: reservas.php');
}
?>