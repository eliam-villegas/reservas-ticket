<?php
// config.php (versión distribuida)
$nodes = [
    [
        'host' => 'mysql1',  // Nombre del contenedor MySQL 1
        'db'   => 'tickets',
        'user' => 'root',
        'pass' => 'root',
        'charset' => 'utf8mb4'
    ],
    [
        'host' => 'mysql2',  // Nombre del contenedor MySQL 2
        'db'   => 'tickets',
        'user' => 'root',
        'pass' => 'root',
        'charset' => 'utf8mb4'
    ],
    [
        'host' => 'mysql3',  // Nombre del contenedor MySQL 3
        'db'   => 'tickets',
        'user' => 'root',
        'pass' => 'root',
        'charset' => 'utf8mb4'
    ]
];

$pdo = null;
$activeNode = null;

// Intentar conexión con los nodos en orden
foreach ($nodes as $node) {
    try {
        $dsn = "mysql:host={$node['host']};dbname={$node['db']};charset={$node['charset']}";
        $pdo = new PDO(
            $dsn,
            $node['user'],
            $node['pass'],
            [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
            ]
        );
        $activeNode = $node;
        break; // Usar el primer nodo disponible
    } catch (PDOException $e) {
        error_log("Error de conexión con {$node['host']}: " . $e->getMessage());
    }
}

// Función para obtener conexión a un nodo específico (útil para operaciones distribuidas)
function getNodeConnection($nodeIndex) {
    global $nodes;
    try {
        $node = $nodes[$nodeIndex];
        $dsn = "mysql:host={$node['host']};dbname={$node['db']};charset={$node['charset']}";
        return new PDO(
            $dsn,
            $node['user'],
            $node['pass'],
            [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
            ]
        );
    } catch (PDOException $e) {
        return null; // Nodo no disponible
    }
}

// Verificar quórum para operaciones críticas (Consistencia + Partición)
function checkQuorum($minNodes = 2) {
    global $nodes;
    $available = 0;

    foreach ($nodes as $node) {
        try {
            $testConn = new PDO(
                "mysql:host={$node['host']};dbname={$node['db']}",
                $node['user'],
                $node['pass'],
                [PDO::ATTR_TIMEOUT => 2]
            );
            $available++;
        } catch (PDOException $e) {
            continue;
        }
    }

    return $available >= $minNodes;
}

// Conexión principal (para operaciones no críticas)
if (!$pdo) {
    die("Error: No se pudo conectar a ningún nodo de base de datos.");
}
?>