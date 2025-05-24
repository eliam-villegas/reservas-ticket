<?php
// public/register.php

// 1) Arranca la sesión (para luego poder usar mensajes flash si deseas)
session_start();

// 2) Carga la conexión
require __DIR__ . '/../config/db.php';  // aquí se define $pdo

// 3) Si vienen datos por POST, procesamos el registro
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Sanitizar y validar
    $usuario   = trim($_POST['usuario'] ?? '');
    $email     = filter_var($_POST['email'] ?? '', FILTER_VALIDATE_EMAIL);
    $password  = $_POST['password'] ?? '';
    $password2 = $_POST['password2'] ?? '';

    $errors = [];
    if (strlen($usuario) < 3) {
        $errors[] = "El nombre de usuario debe tener al menos 3 caracteres.";
    }
    if (!$email) {
        $errors[] = "Debes introducir un email válido.";
    }
    if (strlen($password) < 6) {
        $errors[] = "La contraseña debe tener mínimo 6 caracteres.";
    }
    if ($password !== $password2) {
        $errors[] = "Las contraseñas no coinciden.";
    }

    if (empty($errors)) {
        // Hashear contraseña
        $hash = password_hash($password, PASSWORD_BCRYPT);

        // Insertar en BD
        $stmt = $pdo->prepare("
            INSERT INTO usuarios (usuario_cod, usuario, contrasena)
            VALUES (gen_random_uuid()::STRING, :usuario, :contrasena)
        ");
        try {
            $stmt->execute([
                ':usuario'   => $usuario,
                ':contrasena'=> $hash
            ]);
            // Redirigir a login con mensaje de éxito
            $_SESSION['success'] = "Registro exitoso. Ya puedes iniciar sesión.";
            header('Location: login.php');
            exit;
        } catch (PDOException $e) {
            if (strpos($e->getMessage(), 'unique') !== false) {
                $errors[] = "El nombre de usuario ya existe.";
            } else {
                $errors[] = "Error al registrar: " . $e->getMessage();
            }
        }
    }

    // Guardar errores en sesión para mostrarlos tras redirección
    $_SESSION['errors'] = $errors;
    header('Location: register.php');
    exit;
}

// 4) Si vengo por GET (o tras error), muestro el formulario
$errors  = $_SESSION['errors']  ?? [];
$success = $_SESSION['success'] ?? '';
unset($_SESSION['errors'], $_SESSION['success']);
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Registro</title>
  <link
    href="https://cdn.jsdelivr.net/npm/bootswatch@4.5.2/dist/pulse/bootstrap.min.css"
    rel="stylesheet"
    integrity="sha384-L7+YG8QLqGvxQGffJ6utDKFwmGwtLcCjtwvonVZR/Ba2VzhpMwBz51GaXnUsuYbj" 
    crossorigin="anonymous"
  />
</head>
<body class="bg-light">

  <div class="container d-flex justify-content-center align-items-center vh-100">
    <div class="card shadow-sm" style="max-width: 480px; width: 100%;">
      <div class="card-body">
        <h2 class="card-title text-center mb-4">Registro de usuario</h2>

        <?php if(!empty($success)): ?>
          <div class="alert alert-success">
            <?= htmlspecialchars($success) ?>
          </div>
        <?php endif; ?>

        <?php if(!empty($errors)): ?>
          <div class="alert alert-danger">
            <ul class="mb-0">
              <?php foreach($errors as $e): ?>
                <li><?= htmlspecialchars($e) ?></li>
              <?php endforeach; ?>
            </ul>
          </div>
        <?php endif; ?>

        <form method="POST" action="register.php" novalidate>
          <div class="mb-3">
            <label for="usuario" class="form-label">Nombre de usuario</label>
            <input
              type="text"
              id="usuario"
              name="usuario"
              class="form-control"
              required
              value="<?= htmlspecialchars($_POST['usuario'] ?? '') ?>"
            >
          </div>

          <div class="mb-3">
            <label for="email" class="form-label">Email</label>
            <input
              type="email"
              id="email"
              name="email"
              class="form-control"
              required
              value="<?= htmlspecialchars($_POST['email'] ?? '') ?>"
            >
          </div>

          <div class="mb-3">
            <label for="password" class="form-label">Contraseña</label>
            <input
              type="password"
              id="password"
              name="password"
              class="form-control"
              required
            >
          </div>

          <div class="mb-3">
            <label for="password2" class="form-label">Repite contraseña</label>
            <input
              type="password"
              id="password2"
              name="password2"
              class="form-control"
              required
            >
          </div>

          <button type="submit" class="btn btn-primary w-100">Registrarme</button>
        </form>

        <p class="text-center mt-3 mb-0">
          ¿Ya tienes cuenta? <a href="login.php">Inicia sesión aquí</a>
        </p>
      </div>
    </div>
  </div>

  <script
    src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.6/dist/js/bootstrap.bundle.min.js"
    integrity="sha384-j1CDi7MgGQ12Z7Qab0qlWQ/Qqz24Gc6BM0thvEMVjHnfYGF0rmFCozFSxQBxwHKO"
    crossorigin="anonymous"
  ></script>
</body>
</html>

