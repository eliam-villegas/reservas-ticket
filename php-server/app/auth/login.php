<?php
session_start();
ini_set('display_errors', 1);
error_reporting(E_ALL);

// Carga de configuración
require '../config/config.php';

$error = '';

// Procesar formulario solo en POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $usuario    = trim($_POST['usuario'] ?? '');
    $contrasena = $_POST['contrasena'] ?? '';

    if ($usuario === '' || $contrasena === '') {
        $error = "⚠️ Debes ingresar usuario y contraseña.";
    } else {
        try {
            // Consulta de usuario y contraseña
            $stmt = $conexion->prepare(
                'SELECT id, usuario, contrasena FROM usuarios WHERE usuario = :usuario AND estado = true'
            );
            $stmt->bindParam(':usuario', $usuario);
            $stmt->execute();

            if ($stmt->rowCount() === 1) {
                $user = $stmt->fetch(PDO::FETCH_ASSOC);
                if (password_verify($contrasena, $user['contrasena'])) {
                    $_SESSION['usuario_id'] = (int)$user['id'];   // nuevo
                    $_SESSION['usuario']    = $user['usuario'];
                    header('Location: ../reservas/reservar.php');
                    exit;
                } else {
                    $error = "❌ Contraseña incorrecta.";
                }
            } else {
                $error = "❌ Usuario no encontrado o inactivo.";
            }
        } catch (PDOException $e) {
            $error = "❌ Error en la base de datos: " . $e->getMessage();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Iniciar sesión</title>
    <style>
        body { font-family: 'Segoe UI', sans-serif; background: linear-gradient(135deg, #89f7fe 0%, #66a6ff 100%);background-repeat: no-repeat;
            background-attachment: fixed; display: flex; justify-content: center; align-items: center; height: 100vh; margin: 0; }
        .form-container { background: white; padding: 40px; border-radius: 15px; box-shadow: 0 0 15px rgba(0,0,0,0.1); text-align: center; width: 300px; }
        input, button { width: 100%; padding: 12px; margin: 10px 0; border-radius: 10px; }
        input { border: 1px solid #ccc; }
        button { background: #007BFF; color: white; border: none; font-size: 16px; cursor: pointer; }
        button:hover { background: #0056b3; }
        .error { color: red; text-align: left; margin-bottom: 10px; }
        a { color: #007BFF; text-decoration: none; }
        a:hover { text-decoration: underline; }
    </style>
</head>
<body>
<div class="form-container">
    <h2>Iniciar sesión</h2>
    <?php if (!empty($error)): ?>
        <p class="error"><?= htmlspecialchars($error) ?></p>
    <?php endif; ?>
    <form method="POST">
        <input type="text" name="usuario" placeholder="Usuario" required>
        <input type="password" name="contrasena" placeholder="Clave" required>
        <button type="submit">Ingresar</button>
    </form>
    <a href="register.php">¿No tienes cuenta? Regístrate aquí</a>
</div>
</body>
</html>
