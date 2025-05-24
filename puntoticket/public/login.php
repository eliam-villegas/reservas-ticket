<?php
session_start();
require __DIR__ . '/../config/db.php';  // inyecta $pdo

// 1) Procesar POST (login)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $usuario  = trim($_POST['usuario'] ?? '');
    $password = $_POST['password'] ?? '';
    $errors   = [];

    if ($usuario === '') {
        $errors[] = "Debes ingresar tu nombre de usuario.";
    }
    if ($password === '') {
        $errors[] = "Debes ingresar tu contraseña.";
    }

    if (empty($errors)) {
        // Buscar usuario activo
        $stmt = $pdo->prepare("
            SELECT usuario_cod, contrasena
            FROM usuarios
            WHERE usuario = :usuario
              AND estado = true
        ");
        $stmt->execute([':usuario' => $usuario]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($user && password_verify($password, $user['contrasena'])) {
            // Credenciales correctas: guardamos sesión
            $_SESSION['usuario_cod'] = $user['usuario_cod'];
            $_SESSION['usuario']     = $usuario;
            header('Location: index.php');
            exit;
        } else {
            $errors[] = "Usuario o contraseña incorrectos.";
        }
    }

    // Si hay errores, almacenarlos y redirigir de nuevo
    $_SESSION['errors'] = $errors;
    header('Location: login.php');
    exit;
}

// 2) Mostrar GET (formulario + mensajes)
$errors  = $_SESSION['errors']  ?? [];
$success = $_SESSION['success'] ?? '';
unset($_SESSION['errors'], $_SESSION['success']);
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Iniciar Sesión</title>
  <link
    rel="stylesheet", 
    href="https://cdn.jsdelivr.net/npm/bootswatch@4.5.2/dist/pulse/bootstrap.min.css"
    integrity="sha384-L7+YG8QLqGvxQGffJ6utDKFwmGwtLcCjtwvonVZR/Ba2VzhpMwBz51GaXnUsuYbj"
    crossorigin="anonymous"
  />
</head>
<body class="bg-light">

  <div class="container d-flex justify-content-center align-items-center vh-100">
    <div class="card shadow-sm" style="max-width: 420px; width: 100%;">
      <div class="card-body">
        <h2 class="card-title text-center mb-4">Iniciar Sesión</h2>

        <?php if($success): ?>
          <div class="alert alert-success"><?= htmlspecialchars($success) ?></div>
        <?php endif; ?>

        <?php if($errors): ?>
          <div class="alert alert-danger">
            <ul class="mb-0">
              <?php foreach($errors as $e): ?>
                <li><?= htmlspecialchars($e) ?></li>
              <?php endforeach; ?>
            </ul>
          </div>
        <?php endif; ?>

        <form method="POST" action="login.php" novalidate>
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
            <label for="password" class="form-label">Contraseña</label>
            <input
              type="password"
              id="password"
              name="password"
              class="form-control"
              required
            >
          </div>

          <button type="submit" class="btn btn-primary w-100">Entrar</button>
        </form>

        <p class="text-center mt-3 mb-0">
          ¿No tienes cuenta? <a href="register.php">Regístrate aquí</a>
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
