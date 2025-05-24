<?php
session_start();
ini_set('display_errors', 1);
error_reporting(E_ALL);

require '../config/config.php';

$error = '';
$usuario = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $usuario = trim($_POST['usuario'] ?? '');
    $contrasena = $_POST['contrasena'] ?? '';

    if ($usuario === '' || $contrasena === '') {
        $error = "⚠️ Debes completar todos los campos.";
    } else {
        // Hashear contraseña
        $hash = password_hash($contrasena, PASSWORD_DEFAULT);
        // Generar un ID único para el usuario
        $userId = uniqid('', true);

        try {
            $stmt = $conexion->prepare(
                "INSERT INTO usuarios (usuario, contrasena, estado)
         VALUES (:usuario, :contrasena, true)"
            );
            $stmt->execute([
                ':usuario'    => $usuario,
                ':contrasena' => $hash
            ]);

            header('Location: login.php?registro=exito');
            exit;
        } catch (PDOException $e) {
            if ($e->getCode() === '23505') {
                $error = "❌ El usuario ya existe.";
            } else {
                $error = "❌ Error al registrar: " . $e->getMessage();
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <title>Registro</title>
  <style>
    body {
      font-family: 'Segoe UI', sans-serif;
      background: #f0f4f8;
      display: flex;
      justify-content: center;
      align-items: center;
      height: 100vh;
      margin: 0;
    }
    .form-container {
      background: white;
      padding: 40px;
      border-radius: 15px;
      box-shadow: 0 0 15px rgba(0,0,0,0.1);
      text-align: center;
      width: 300px;
    }
    input[type="text"], input[type="password"] {
      width: 100%;
      padding: 12px;
      margin: 10px 0;
      border: 1px solid #ccc;
      border-radius: 10px;
    }
    button {
      width: 100%;
      padding: 12px;
      background: #007BFF;
      color: white;
      border: none;
      border-radius: 10px;
      font-size: 16px;
      cursor: pointer;
    }
    button:hover {
      background: #0056b3;
    }
    .back-link {
      margin-top: 15px;
      display: block;
      color: #007BFF;
      text-decoration: none;
    }
    .back-link:hover {
      text-decoration: underline;
    }
  </style>
</head>
<body>
  <div class="form-container">
    <h2>Registrarse</h2>
      <?php if (!empty($error)): ?>
          <p style="color:red;"><?= htmlspecialchars($error) ?></p>
      <?php endif; ?>
    <form method="POST">
      <input type="text" name="usuario" placeholder="Usuario" required>
      <input type="password" name="contrasena" placeholder="Contraseña" required>
      <button type="submit">Registrar</button>
    </form>
    <a class="back-link" href="login.php">← Volver a iniciar sesión</a>
  </div>
</body>
</html>
