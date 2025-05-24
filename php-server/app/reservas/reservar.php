<?php
session_start();
require_once __DIR__ . '/../config/config.php'; // Ajusta la ruta según tu estructura

// 1) Verificar sesión
if (empty($_SESSION['usuario_id'])) {
    header('Location: ../auth/login.php');
    exit;
}
$usuario_cod    = $_SESSION['usuario_id'];
$usuario_actual = $_SESSION['usuario'];

// 2) Mensaje de estado
$mensaje = null;
if (isset($_GET['msg'])) {
    switch ($_GET['msg']) {
        case 'ok':       $mensaje = "✅ Reserva realizada correctamente.";     break;
        case 'edit':     $mensaje = "✏️ Reserva actualizada correctamente.";   break;
        case 'del':      $mensaje = "🗑️ Reserva eliminada.";                  break;
        case 'duplicado':$mensaje = "⚠️ El asiento ya está reservado.";         break;
    }
}

// 3) DELETE via GET
/*if (isset($_GET['eliminar'])) {
    $resId = intval($_GET['eliminar']);
    try {
        $conexion->beginTransaction();
        // Bloquear y obtener el asiento
        $stmt = $conexion->prepare(
            'SELECT asiento_cod FROM reservas 
           WHERE reserva_id = :r AND usuario_cod = :u FOR UPDATE'
        );
        $stmt->execute([':r'=>$resId, ':u'=>$usuario_cod]);
        $fila = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$fila) throw new Exception('No encontrada o no autorizada.');
        // Borrar reserva
        $conexion->prepare('DELETE FROM reservas WHERE reserva_id = :r')
            ->execute([':r'=>$resId]);
        // Liberar asiento
        $conexion->prepare('UPDATE asientos SET ocupado = false WHERE asiento_cod = :a')
            ->execute([':a'=>$fila['asiento_cod']]);
        $conexion->commit();
        header("Location: reservar.php?msg=" . ($resId ? 'edit' : 'ok'));
        exit;
    } catch (Exception $e) {
        $conexion->rollBack();
        $mensaje = "❌ Error al eliminar: ".$e->getMessage();
    }
}*/

// 4) CREATE / UPDATE via POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $resId = $_POST['reserva_id'] ?? '';         // 0 → nueva reserva
    $fecha   = $_POST['fecha']     ?? '';
    $cliente = trim($_POST['cliente'] ?? '');
    $asiento = $_POST['asiento_cod'] ?? '';                // MANTENER como string

    if (!$fecha || !$cliente || !$asiento) {
        $mensaje = '⚠️ Complete todos los campos.';
    } else {
        try {
            /* -----------------------------------------------------------------
               INICIAR TRANSACCIÓN
            ----------------------------------------------------------------- */
            error_log("DEBUG reservar.php POST → resId=$resId, fecha=$fecha, cliente=$cliente, asiento=$asiento, usuario=$usuario_cod");
            $conexion->beginTransaction();

            /* -----------------------------------------------------------------
               ➊ BLOQUEAR Y COMPROBAR EL NUEVO ASIENTO
            ----------------------------------------------------------------- */
            $stmt = $conexion->prepare(
                'SELECT ocupado
       FROM asientos
      WHERE asiento_cod = :a
      FOR UPDATE'
            );
            $stmt->execute([':a' => $asiento]);
            $rowNew = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$rowNew) {
                throw new Exception('Asiento no encontrado.');
            }

            $asientoOcupado = filter_var($rowNew['ocupado'], FILTER_VALIDATE_BOOLEAN);

            /* -----------------------------------------------------------------
               ➋ SI ES EDICIÓN: OBTENER Y BLOQUEAR EL ASIENTO ANTIGUO,
                  ACTUALIZAR LA RESERVA, OCUPAR NUEVO Y LIBERAR ANTIGUO
            ----------------------------------------------------------------- */
            if ($resId) {
                // 1. Obtener asiento antiguo
                $stmtOld = $conexion->prepare(
                    'SELECT asiento_cod
           FROM reservas
          WHERE reserva_id = :r
            AND usuario_id = :u
          FOR UPDATE'
                );
                $stmtOld->execute([':r' => $resId, ':u' => $usuario_cod]);
                $old = $stmtOld->fetch(PDO::FETCH_ASSOC);
                error_log('DEBUG reserva antigua: ' . var_export($old, true));
                error_log("DEBUG asiento ocupado? " . ($asientoOcupado ? 'sí' : 'no'));

                if (!$old) {
                    throw new Exception('Reserva no encontrada o no autorizada.');
                }
                $oldAsiento = $old['asiento_cod'];

                // 2. ¿El usuario cambió de asiento?
                $cambioDeAsiento = ($asiento !== $oldAsiento);

                //    - Si intenta cambiar **a uno ocupado**, lo bloqueamos aquí
                if ($cambioDeAsiento && $asientoOcupado) {
                    throw new Exception('Asiento no disponible.');
                }

                // 3. Actualizar la reserva (fecha/cliente + posible nuevo asiento)
                $conexion->prepare(
                    'UPDATE reservas
            SET fecha = :f,
                cliente = :c,
                asiento_cod = :a
          WHERE reserva_id = :r'
                )->execute([
                    ':f' => $fecha,
                    ':c' => $cliente,
                    ':a' => $asiento,
                    ':r' => $resId
                ]);

                // 4. Ajustar tabla ASIENTOS sólo si hubo cambio
                if ($cambioDeAsiento) {
                    // ocupar nuevo
                    $conexion->prepare(
                        'UPDATE asientos SET ocupado = true WHERE asiento_cod = :a'
                    )->execute([':a' => $asiento]);
                    // liberar antiguo
                    $conexion->prepare(
                        'UPDATE asientos SET ocupado = false WHERE asiento_cod = :a'
                    )->execute([':a' => $oldAsiento]);
                }

                $mensaje = '✏️ Reserva actualizada.';
            }

            /* -----------------------------------------------------------------
               ➌ SI ES NUEVA RESERVA: INSERTAR Y OCUPAR ASIENTO
            ----------------------------------------------------------------- */
            else {
                $ins = $conexion->prepare(
                    'INSERT INTO reservas (fecha, cliente, asiento_cod, usuario_id)
                   VALUES (:f, :c, :a, :u)'
                );
                $ins->execute([
                    ':f' => $fecha,
                    ':c' => $cliente,
                    ':a' => $asiento,
                    ':u' => $usuario_cod
                ]);

                // Marcar asiento ocupado
                $conexion->prepare(
                    'UPDATE asientos SET ocupado = true WHERE asiento_cod = :a'
                )->execute([':a' => $asiento]);

                $mensaje = '✅ Reserva creada.';
            }

            /* -----------------------------------------------------------------
               ➍ CONFIRMAR TRANSACCIÓN
            ----------------------------------------------------------------- */
            $conexion->commit();

            // Redirigir y evitar reenvío de formulario
            $query = $resId ? 'edit' : 'ok';
            header("Location: reservar.php?msg=$query");
            exit;

        } catch (Exception $e) {
            $conexion->rollBack();
            $mensaje = '❌ Error al procesar: ' . $e->getMessage();
        }
    }
}

// 5) Cargar reservas del usuario
try {
    $stmt = $conexion->prepare(
        'SELECT
         r.reserva_id,
         r.fecha,
         r.cliente,
         a.asiento,
         s.sector,
         u.usuario,       -- nombre de usuario
         a.asiento_cod
     FROM reservas  r
     JOIN asientos  a ON r.asiento_cod = a.asiento_cod
     JOIN sectores  s ON a.sector_cod  = s.sector_cod
     JOIN usuarios  u ON r.usuario_id  = u.id        -- <-- cambio aquí
     WHERE r.usuario_id = :u
     ORDER BY r.fecha DESC'
    );

    $stmt->execute([':u'=>$usuario_cod]);
    $reservas = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $mensaje = '❌ Error al cargar reservas: '.$e->getMessage();
    $reservas = [];
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Reservas</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            background: #f0f2f5;
            padding: 20px;
        }

        .top-bar {
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .dropdown {
            position: relative;
            display: inline-block;
        }

        .dropdown-toggle {
            background: #4A90E2;
            color: white;
            padding: 10px 15px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
        }

        .dropdown-menu {
            display: none;
            position: absolute;
            right: 0;
            background-color: white;
            min-width: 160px;
            box-shadow: 0px 8px 16px rgba(0,0,0,0.2);
            z-index: 1;
            border-radius: 5px;
            overflow: hidden;
        }

        .dropdown-menu a {
            color: black;
            padding: 12px 16px;
            text-decoration: none;
            display: block;
        }

        .dropdown-menu a:hover {
            background-color: #f1f1f1;
        }

        .dropdown:hover .dropdown-menu {
            display: block;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
            background: white;
        }

        th, td {
            padding: 12px;
            border: 1px solid #ddd;
            text-align: center;
        }

        th {
            background: #4A90E2;
            color: white;
        }

        button {
            padding: 10px 20px;
            background: #4A90E2;
            color: white;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            margin-top: 10px;
        }

        #modal, #editModal, #confirmDeleteModal {
            display: none;
            position: fixed;
            inset: 0;
            background-color: rgba(0, 0, 0, 0.6);
            justify-content: center;
            align-items: center;
            animation: fadeIn 0.3s ease forwards;
        }

        .modal-content {
            background: white;
            padding: 30px;
            border-radius: 10px;
            width: 100%;
            max-width: 400px;
            transform: scale(0.9);
            opacity: 0;
            animation: zoomIn 0.4s ease forwards;
        }

        @keyframes zoomIn {
            to {
                transform: scale(1);
                opacity: 1;
            }
        }

        @keyframes fadeIn {
            from { background-color: rgba(0, 0, 0, 0); }
            to   { background-color: rgba(0, 0, 0, 0.6); }
        }

        .close {
            float: right;
            cursor: pointer;
            font-size: 20px;
        }

        .mensaje {
            margin-top: 15px;
            color: green;
        }
    </style>
</head>
<body>

<div class="top-bar">
    <h2>📅 Sistema de Reservas</h2>
    <div class="dropdown">
        <button class="dropdown-toggle">👤 <?= htmlspecialchars($usuario_actual) ?></button>
        <div class="dropdown-menu">
            <a href="#" onclick="openLogoutModal()">Cerrar sesión</a>
        </div>
    </div>
</div>

<?php if ($mensaje): ?>
    <div class="mensaje"><?= htmlspecialchars($mensaje) ?></div>
<?php endif; ?>

<button onclick="openModal()">➕ Nueva Reserva</button>

<table>
    <thead>
    <tr>
        <th>Fecha</th>
        <th>Cliente</th>
        <th>Asiento</th>
        <th>Reservado por</th>
        <th>Acciones</th>
    </tr>
    </thead>
    <tbody>
    <?php foreach ($reservas as $reserva): ?>
        <tr>
            <td><?= htmlspecialchars($reserva['fecha']) ?></td>
            <td><?= htmlspecialchars($reserva['cliente']) ?></td>
            <td><?= htmlspecialchars($reserva['asiento']) ?></td>
            <td><?= htmlspecialchars($reserva['usuario']) ?></td>
            <td>
                <button onclick="openEditModal(
                        '<?= $reserva['reserva_id'] ?>',             // ← entre comillas
                        '<?= $reserva['fecha'] ?>',
                        '<?= htmlspecialchars($reserva['cliente'], ENT_QUOTES) ?>',
                        '<?= $reserva['asiento_cod'] ?>'
                        )">Editar</button>

                <button type="button"
                        onclick="openDeleteModal('<?= $reserva['reserva_id'] ?>')">
                    Eliminar
                </button>         </td>
        </tr>
    <?php endforeach; ?>
    </tbody>
</table>

<!-- Modal para nueva reserva -->
<div id="modal">
    <div class="modal-content">
        <span class="close" onclick="closeModal()">&times;</span>
        <h3>📝 Nueva Reserva</h3>
        <form method="POST">
            <label>Fecha:</label><br>
            <input type="date" name="fecha" id="fecha" required><br><br>

            <label>Cliente:</label><br>
            <input type="text" name="cliente" required><br><br>

            <label>Asiento:</label><br>
            <select name="asiento_cod" id="asiento_cod" required>
                <option value="">Seleccione una fecha primero</option>
            </select><br><br>

            <button type="submit">Reservar</button>
        </form>
    </div>
</div>

<!-- Modal para editar reserva -->
<div id="editModal">
    <div class="modal-content">
        <span class="close" onclick="closeEditModal()">&times;</span>
        <h3>✏️ Editar Reserva</h3>
        <form method="POST">
            <input type="hidden" name="reserva_id" id="reserva_id"><!--  ahora DENTRO  -->
            <label>Fecha:</label><br>
            <input type="date" name="fecha" id="edit_fecha" required><br><br>

            <label>Cliente:</label><br>
            <input type="text" name="cliente" id="edit_cliente" required><br><br>

            <label>Asiento:</label><br>
            <select name="asiento_cod" id="edit_asiento_cod" required>
                <option value="">Seleccione una fecha primero</option>
            </select><br><br>

            <button type="submit">Actualizar</button>
        </form>
    </div>
</div>

<!-- Modal para confirmar eliminación -->
<div id="confirmDeleteModal">
    <div class="modal-content">
        <span class="close" onclick="closeDeleteModal()">&times;</span>
        <h3>🗑️ Confirmar eliminación</h3>
        <p>¿Estás seguro de que deseas eliminar esta reserva?</p>
        <button onclick="confirmDelete()">Sí, eliminar</button>
        <button onclick="closeDeleteModal()">Cancelar</button>
    </div>
</div>

<!-- Modal de Confirmación de Cerrar Sesión -->
<div id="logoutModal" style="display: none; position: fixed; inset: 0; background-color: rgba(0,0,0,0.5); justify-content: center; align-items: center;">
    <div style="background: white; padding: 30px; border-radius: 10px; text-align: center; max-width: 400px; width: 90%; animation: zoomIn 0.3s ease-out;">
        <h3>¿Cerrar sesión?</h3>
        <p>¿Estás seguro que deseas cerrar tu sesión actual?</p>
        <button onclick="confirmLogout()" style="background-color: #E74C3C; margin: 10px;">Sí, cerrar sesión</button>
        <button onclick="closeLogoutModal()" style="background-color: #3498DB; margin: 10px;">Cancelar</button>
    </div>
</div>

<script>
    function openLogoutModal() {
        document.getElementById('logoutModal').style.display = 'flex';
    }

    function closeLogoutModal() {
        document.getElementById('logoutModal').style.display = 'none';
    }

    function confirmLogout() {
        window.location.href = 'logout.php';
    }
</script>

<script>
    function openModal() {
        const modal = document.getElementById('modal');
        modal.style.display = 'flex';
        const fecha = document.getElementById('fecha').value;
        if (fecha) fetchAsientosDisponibles(fecha, 'asiento_cod');
    }


    function closeModal() {
        const modal = document.getElementById('modal');
        modal.style.display = 'none';
    }

    function openEditModal(reserva_id, fecha, cliente, asiento_cod) {
        const modal = document.getElementById('editModal');
        document.getElementById('reserva_id').value = reserva_id;
        document.getElementById('edit_fecha').value = fecha;
        document.getElementById('edit_cliente').value = cliente;

        // Llenar select con asientos disponibles para esa fecha
        fetch(`asientos_disponibles.php?fecha=${fecha}&editar=${reserva_id}`)
            .then(res => res.json())
            .then(asientos => {
                const select = document.getElementById('edit_asiento_cod');
                select.innerHTML = '';
                asientos.forEach(asiento => {
                    const option = document.createElement('option');
                    option.value = asiento.asiento_cod;
                    option.text = asiento.asiento;
                    if (asiento.asiento_cod == asiento_cod) {
                        option.selected = true;
                    }
                    select.appendChild(option);
                });
            });

        modal.style.display = 'flex';
    }


    function closeEditModal() {
        const modal = document.getElementById('editModal');
        modal.style.display = 'none';
    }

    function openDeleteModal(reserva_id) {
        const modal = document.getElementById('confirmDeleteModal');
        document.getElementById('confirmDeleteModal').dataset.reservaId = reserva_id;
        modal.style.display = 'flex';
    }

    function closeDeleteModal() {
        const modal = document.getElementById('confirmDeleteModal');
        modal.style.display = 'none';
    }

    function confirmDelete() {
        const reserva_id = document.getElementById('confirmDeleteModal').dataset.reservaId;
        window.location.href = 'eliminar_reserva.php?id=' + reserva_id;
    }
</script>

<script>
    document.getElementById('fecha').addEventListener('change', function () {
        const fecha = this.value;
        fetchAsientosDisponibles(fecha, 'asiento_cod');
    });

    document.getElementById('edit_fecha').addEventListener('change', function () {
        const fecha = this.value;
        fetchAsientosDisponibles(fecha, 'edit_asiento_cod');
    });

    function fetchAsientosDisponibles(fecha, selectId) {
        fetch(`asientos_disponibles.php?fecha=${encodeURIComponent(fecha)}`)
            .then(response => response.json())
            .then(data => {
                const select = document.getElementById(selectId);
                select.innerHTML = '';

                if (data.length > 0) {
                    select.innerHTML = '<option value="">Seleccione un asiento</option>';
                    data.forEach(asiento => {
                        const option = document.createElement('option');
                        option.value = asiento.asiento_cod;
                        option.textContent = asiento.asiento;
                        select.appendChild(option);
                    });
                } else {
                    select.innerHTML = '<option value="">No hay asientos disponibles</option>';
                }
            })
            .catch(error => {
                console.error('Error al obtener asientos:', error);
            });
    }
</script>

</body>
</html>
