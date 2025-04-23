<?php
require '../config/config.php';
session_start();
$mensaje = null;

if (isset($_GET['msg'])) {
    switch ($_GET['msg']) {
        case 'ok':
            $mensaje = "✅ Reserva realizada correctamente.";
            break;
        case 'edit':
            $mensaje = "✏️ Reserva actualizada correctamente.";
            break;
        case 'del':
            $mensaje = "🗑️ Reserva eliminada.";
            break;
        case 'duplicado':
            $mensaje = "⚠️ El asiento ya está reservado.";
            break;
        default:
            $mensaje = null;
    }
}
if (!isset($_SESSION['usuario_cod'])) {
    header('Location: login.php');
    exit;
}

// ======================== VERIFICAR QUÓRUM ========================
if (!checkQuorum(2)) {  // Requiere 2/3 nodos disponibles
    die("❌ Sistema no disponible por partición de red.");
}

// ======================== CONEXIÓN PRINCIPAL ========================
global $pdo;
$usuario_cod = $_SESSION['usuario_cod'];

// Obtener nombre de usuario (desde cualquier nodo disponible)
$stmt = $pdo->prepare("SELECT usuario FROM usuarios WHERE usuario_cod = ?");
$stmt->execute([$usuario_cod]);
$usuario_actual = $stmt->fetchColumn();

// ======================== INSERTAR RESERVA (EN TODOS LOS NODOS) ========================
if ($_SERVER['REQUEST_METHOD'] === 'POST' && !isset($_POST['editar'])) {
    $asiento_cod = $_POST['asiento_cod'];
    $fecha = $_POST['fecha'];
    $cliente = $_POST['cliente'];

    // Validar duplicado en TODOS los nodos
    $duplicado = false;
    foreach ($nodes as $index => $node) {
        $nodePdo = getNodeConnection($index);
        if ($nodePdo) {
            $stmt = $nodePdo->prepare("SELECT COUNT(*) FROM reservas WHERE asiento_cod = ? AND fecha = ?");
            $stmt->execute([$asiento_cod, $fecha]);
            if ($stmt->fetchColumn() > 0) $duplicado = true;
        }
    }

    if (!$duplicado) {
        $exitoso = true;
        foreach ($nodes as $index => $node) {  // Replicar en todos los nodos
            $nodePdo = getNodeConnection($index);
            if ($nodePdo) {
                try {
                    $nodePdo->beginTransaction();
                    $stmt = $nodePdo->prepare("
                        INSERT INTO reservas (fecha, cliente, asiento_cod, usuario_cod)
                        VALUES (?, ?, ?, ?)
                    ");
                    $stmt->execute([$fecha, $cliente, $asiento_cod, $usuario_cod]);
                    $nodePdo->commit();
                } catch (Exception $e) {
                    $nodePdo->rollBack();
                    $exitoso = false;
                    error_log("Error en nodo {$node['host']}: " . $e->getMessage());
                }
            }
        }
        if ($exitoso) {
            header("Location: reservar.php?msg=ok");
            exit;
        } else {
            $mensaje = "❌ Error parcial: algunos nodos no registraron la reserva.";
        }
    } else {
        $mensaje = "⚠️ El asiento ya está reservado en al menos un nodo.";
    }
}

// ======================== ELIMINAR RESERVA (EN TODOS LOS NODOS) ========================
if (isset($_GET['eliminar'])) {
    $reserva_id = $_GET['eliminar'];
    foreach ($nodes as $index => $node) {
        $nodePdo = getNodeConnection($index);
        if ($nodePdo) {
            $stmt = $nodePdo->prepare("DELETE FROM reservas WHERE reserva_id = ?");
            $stmt->execute([$reserva_id]);
        }
    }
    header("Location: reservar.php?msg=del");
    exit;
}

// ======================== EDITAR RESERVA (EN TODOS LOS NODOS) ========================
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['editar'])) {
    $reserva_id = $_POST['reserva_id'];
    $asiento_cod = $_POST['asiento_cod'];
    $fecha = $_POST['fecha'];
    $cliente = $_POST['cliente'];

    // Verificar duplicado en TODOS los nodos
    $duplicado = false;
    foreach ($nodes as $index => $node) {
        $nodePdo = getNodeConnection($index);
        if ($nodePdo) {
            $stmt = $nodePdo->prepare("
                SELECT COUNT(*) FROM reservas 
                WHERE asiento_cod = ? AND fecha = ? AND reserva_id != ?
            ");
            $stmt->execute([$asiento_cod, $fecha, $reserva_id]);
            if ($stmt->fetchColumn() > 0) $duplicado = true;
        }
    }

    if (!$duplicado) {
        $exitoso = true;
        foreach ($nodes as $index => $node) {
            $nodePdo = getNodeConnection($index);
            if ($nodePdo) {
                try {
                    $stmt = $nodePdo->prepare("
                        UPDATE reservas 
                        SET fecha = ?, cliente = ?, asiento_cod = ?
                        WHERE reserva_id = ?
                    ");
                    $stmt->execute([$fecha, $cliente, $asiento_cod, $reserva_id]);
                } catch (Exception $e) {
                    $exitoso = false;
                    error_log("Error en nodo {$node['host']}: " . $e->getMessage());
                }
            }
        }
        if ($exitoso) {
            header("Location: reservar.php?msg=edit");
            exit;
        } else {
            $mensaje = "❌ Error parcial: algunos nodos no actualizaron la reserva.";
        }
    } else {
        $mensaje = "⚠️ El asiento ya está reservado en al menos un nodo.";
    }
}

// ======================== OBTENER RESERVAS (DESDE CUALQUIER NODO) ========================
$reservas = $pdo->query("
    SELECT 
      r.reserva_id,
      r.fecha,
      r.cliente,
      r.asiento_cod,
      a.asiento,
      u.usuario 
    FROM reservas r
    JOIN asientos a ON r.asiento_cod = a.asiento_cod
    JOIN usuarios u ON r.usuario_cod = u.usuario_cod
    ORDER BY r.fecha DESC
")->fetchAll(PDO::FETCH_ASSOC);

// Resto del código HTML permanece igual...
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
            <?= $reserva['reserva_id'] ?>,
            '<?= $reserva['fecha'] ?>',
            '<?= htmlspecialchars($reserva['cliente']) ?>',
            <?= $reserva['asiento_cod'] ?>
          )">Editar</button>
          <button onclick="openDeleteModal(<?= $reserva['reserva_id'] ?>)">Eliminar</button>
        </td>
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
        <input type="hidden" name="reserva_id" id="reserva_id">
        <label>Fecha:</label><br>
        <input type="date" name="fecha" id="edit_fecha" required><br><br>

        <label>Cliente:</label><br>
        <input type="text" name="cliente" id="edit_cliente" required><br><br>

        <label>Asiento:</label><br>
        <select name="asiento_cod" id="edit_asiento_cod" required>
          <option value="">Seleccione una fecha primero</option>
        </select><br><br>

        <button type="submit" name="editar">Actualizar</button>
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
      window.location.href = '?eliminar=' + reserva_id;
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
