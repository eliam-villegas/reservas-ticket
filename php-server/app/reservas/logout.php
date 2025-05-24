<?php
session_start();

// Destruir sesión completamente
session_unset();
session_destroy();

header('Location: ../auth/login.php');
exit;
?>
