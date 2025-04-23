<?php
require '../config/config.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $usuario = $_POST['usuario'];
    $contrasena = password_hash($_POST['contrasena'], PASSWORD_BCRYPT);
    $exitoParcial = false;
    $errores = [];

    // Generar ID manualmente para consistencia entre nodos (evitar autoincrement)
    $userId = (int) time().rand(1000, 9999); // ID único basado en timestamp + random

    // Insertar en TODOS los nodos disponibles
    foreach ($nodes as $index => $node) {
        $nodePdo = getNodeConnection($index);
        if ($nodePdo) {
            try {
                $nodePdo->beginTransaction();

                // Insertar usuario
                $stmt = $nodePdo->prepare("
                    INSERT INTO usuarios (usuario_cod, usuario, estado, contrasena)
                    VALUES (?, ?, 1, ?)
                ");
                $stmt->execute([$userId, $usuario, $contrasena]);

                $nodePdo->commit();
                $exitoParcial = true;
            } catch (PDOException $e) {
                $nodePdo->rollBack();
                $errores[] = "Nodo {$node['host']}: " . $e->getMessage();
            }
        }
    }

    if ($exitoParcial) {
        $mensaje = "Usuario registrado exitosamente en " . (count($nodes) - count($errores)) . "/" . count($nodes) . " nodos.";
        if (!empty($errores)) {
            $mensaje .= "<br>Errores: " . implode(", ", $errores);
        }
        echo "<script>
            alert('$mensaje');
            window.location.href = 'login.php';
        </script>";
    } else {
        echo "<script>
            alert('❌ Error: No se pudo registrar en ningún nodo.');
            window.location.href = 'register.php';
        </script>";
    }
    exit;
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
    <form method="POST">
      <input type="text" name="usuario" placeholder="Usuario" required>
      <input type="password" name="contrasena" placeholder="Contraseña" required>
      <button type="submit">Registrar</button>
    </form>
    <a class="back-link" href="login.php">← Volver a iniciar sesión</a>
  </div>
</body>
</html>
