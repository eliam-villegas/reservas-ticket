<?php
require '../config/config.php';

session_start();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $usuario = $_POST['usuario'];
    $contrasena = $_POST['contrasena'];
    $autenticado = false;

    // Intentar autenticación en TODOS los nodos disponibles
    foreach ($nodes as $index => $node) {
        $nodePdo = getNodeConnection($index);
        if ($nodePdo) {
            try {
                $stmt = $nodePdo->prepare("SELECT * FROM usuarios WHERE usuario = ? AND estado = 1");
                $stmt->execute([$usuario]);
                $user = $stmt->fetch();

                if ($user && password_verify($contrasena, $user['contrasena'])) {
                    $_SESSION['usuario_cod'] = $user['usuario_cod'];
                    $autenticado = true;
                    break; // Salir del bucle si se autentica
                }
            } catch (PDOException $e) {
                error_log("Error en nodo {$node['host']}: " . $e->getMessage());
            }
        }
    }

    if ($autenticado) {
        header('Location: ../reservas/reservar.php');
        exit;
    } else {
        $error = "❌ Credenciales inválidas o sistema no disponible.";
    }
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <title>Login</title>
  <style>
    body {
      margin: 0;
      padding: 0;
      font-family: Arial, sans-serif;
      background: linear-gradient(135deg, #74ebd5, #ACB6E5);
      height: 100vh;
      display: flex;
      justify-content: center;
      align-items: center;
    }

    .login-form {
      background-color: white;
      padding: 30px 40px;
      border-radius: 10px;
      box-shadow: 0 8px 16px rgba(0,0,0,0.2);
      width: 100%;
      max-width: 400px;
    }

    .login-form h2 {
      text-align: center;
      margin-bottom: 20px;
    }

    .login-form label {
      display: block;
      margin-bottom: 5px;
      font-weight: bold;
    }

    .login-form input[type="text"],
    .login-form input[type="password"] {
      width: 100%;
      padding: 10px;
      margin-bottom: 15px;
      border: 1px solid #ccc;
      border-radius: 5px;
    }

    .login-form button {
      width: 100%;
      padding: 10px;
      background-color: #4A90E2;
      color: white;
      border: none;
      border-radius: 5px;
      font-size: 16px;
      cursor: pointer;
    }

    .login-form button:hover {
      background-color: #357ABD;
    }

    .error {
      color: red;
      text-align: center;
      margin-bottom: 10px;
    }
  </style>
</head>
<body>

  <form class="login-form" method="POST">
    <h2>Iniciar sesión</h2>

    <?php if (isset($error)): ?>
      <div class="error"><?= $error ?></div>
    <?php endif; ?>

    <label for="usuario">Usuario</label>
    <input type="text" name="usuario" id="usuario" required>

    <label for="contrasena">Clave</label>
    <input type="password" name="contrasena" id="contrasena" required>

    <button type="submit">Ingresar</button>
	
	  <p style="margin-top: 15px;">
  <a href="register.php" style="color: #007BFF; text-decoration: none;">¿No tienes cuenta? Regístrate aquí</a>
</p>
  </form>
  



</body>
</html>
