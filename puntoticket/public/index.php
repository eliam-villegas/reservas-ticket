<?php
// public/index.php
session_start();
require __DIR__ . '/../config/db.php';  // inyecta $pdo

// 1) Verificar sesión
if (empty($_SESSION['usuario_cod'])) {
    header('Location: login.php');
    exit;
}

// 2) Procesar reserva si viene POST
$errors = [];
$success = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['asiento_cod'])) {
    $asientoCod = (int)$_POST['asiento_cod'];

    // 2.1) Verificar que el asiento siga libre
    $stmt = $pdo->prepare("SELECT ocupado FROM asientos WHERE asiento_cod = :cod");
    $stmt->execute([':cod' => $asientoCod]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$row) {
        $errors[] = "Asiento no válido.";
    } elseif ($row['ocupado']) {
        $errors[] = "Lo siento, ese asiento ya está reservado.";
    } else {
        // 2.2) Insertar reserva
        try {
            $pdo->beginTransaction();

            // Insertar en reservas
            $stmt1 = $pdo->prepare("
                INSERT INTO reservas (fecha, cliente, asiento_cod, usuario_cod)
                VALUES (CURRENT_DATE, :cliente, :asiento, :usuario)
            ");
            $stmt1->execute([
                ':cliente' => $_SESSION['usuario'],
                ':asiento' => $asientoCod,
                ':usuario' => $_SESSION['usuario_cod'],
            ]);

            // Marcar asiento como ocupado
            $stmt2 = $pdo->prepare("
                UPDATE asientos SET ocupado = true
                WHERE asiento_cod = :cod
            ");
            $stmt2->execute([':cod' => $asientoCod]);

            $pdo->commit();
            $success = "¡Reserva realizada con éxito!";
        } catch (Exception $e) {
            $pdo->rollBack();
            $errors[] = "Error al procesar reserva: " . $e->getMessage();
        }
    }
}

// 3) Obtener lista de asientos con su sector
$stmt = $pdo->query("
    SELECT a.asiento_cod, a.asiento, a.ocupado, s.sector
    FROM asientos a
    JOIN sectores s ON a.sector_cod = s.sector_cod
    ORDER BY s.sector_cod, a.asiento
");
$asientos = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>PuntoTicket · Venta</title>
  <link
    href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.6/dist/css/bootstrap.min.css"
    rel="stylesheet"
  />
</head>
<body class="bg-light">

  <nav class="navbar navbar-expand-lg navbar-dark bg-primary">
    <div class="container">
      <a class="navbar-brand" href="#">PuntoTicket</a>
      <div class="ms-auto">
        <span class="navbar-text text-white me-3">
          Hola, <?= htmlspecialchars($_SESSION['usuario']) ?>
        </span>
        <a href="logout.php" class="btn btn-outline-light btn-sm">Cerrar sesión</a>
      </div>
    </div>
  </nav>

  <div class="container py-4">
    <?php if ($success): ?>
      <div class="alert alert-success"><?= htmlspecialchars($success) ?></div>
    <?php endif; ?>
    <?php if ($errors): ?>
      <div class="alert alert-danger">
        <ul class="mb-0">
          <?php foreach ($errors as $e): ?>
            <li><?= htmlspecialchars($e) ?></li>
          <?php endforeach; ?>
        </ul>
      </div>
    <?php endif; ?>

    <h1 class="mb-4">Selecciona tu asiento</h1>
    <div class="row g-3">
      <?php foreach ($asientos as $a): ?>
        <div class="col-6 col-md-3">
          <div class="card text-center shadow-sm">
            <div class="card-body py-2">
              <h5 class="card-title mb-2"><?= htmlspecialchars($a['asiento']) ?></h5>
              <p class="card-text small mb-2"><?= htmlspecialchars($a['sector']) ?></p>
              <?php if ($a['ocupado']): ?>
                <button class="btn btn-secondary btn-sm" disabled>Reservado</button>
              <?php else: ?>
                <form method="POST" action="index.php">
                  <input type="hidden" name="asiento_cod" value="<?= $a['asiento_cod'] ?>">
                  <button type="submit" class="btn btn-success btn-sm">Reservar</button>
                </form>
              <?php endif; ?>
            </div>
          </div>
        </div>
      <?php endforeach; ?>
    </div>
  </div>

  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.6/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
