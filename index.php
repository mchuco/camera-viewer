<?php
session_start();
include 'config/db.php';

// Verificar si el usuario está autenticado
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

// Obtener todas las cámaras del usuario
$user_id = $_SESSION['user_id'];
$query = "SELECT * FROM cameras WHERE user_id = ? ORDER BY created_at DESC";
$stmt = $conn->prepare($query);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
$cameras = $result->fetch_all(MYSQLI_ASSOC);
$stmt->close();
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Visor de Cámaras IP</title>
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
    <div class="container">
        <header class="navbar">
            <div class="navbar-brand">
                <h1>🎥 Visor de Cámaras IP</h1>
            </div>
            <nav class="navbar-menu">
                <a href="dashboard.php">Dashboard</a>
                <a href="add_camera.php" class="btn btn-primary">+ Añadir Cámara</a>
                <a href="logout.php">Cerrar Sesión</a>
            </nav>
        </header>

        <main class="main-content">
            <div class="dashboard">
                <h2>Mis Cámaras</h2>

                <?php if (empty($cameras)): ?>
                    <div class="empty-state">
                        <p>No tienes cámaras registradas aún.</p>
                        <a href="add_camera.php" class="btn btn-primary">Añadir tu primera cámara</a>
                    </div>
                <?php else: ?>
                    <div class="cameras-grid">
                        <?php foreach ($cameras as $camera): ?>
                            <div class="camera-card">
                                <div class="camera-preview">
                                    <img src="https://via.placeholder.com/300x200?text=<?php echo urlencode($camera['name']); ?>" alt="<?php echo htmlspecialchars($camera['name']); ?>">
                                </div>
                                <div class="camera-info">
                                    <h3><?php echo htmlspecialchars($camera['name']); ?></h3>
                                    <p class="camera-ip">IP: <?php echo htmlspecialchars($camera['ip_address']); ?></p>
                                    <p class="camera-status">
                                        <span class="status-badge <?php echo $camera['status'] == 'online' ? 'online' : 'offline'; ?>">
                                            <?php echo $camera['status'] == 'online' ? '● En línea' : '● Sin conexión'; ?>
                                        </span>
                                    </p>
                                </div>
                                <div class="camera-actions">
                                    <a href="view_camera.php?id=<?php echo $camera['id']; ?>" class="btn btn-small">Ver</a>
                                    <a href="edit_camera.php?id=<?php echo $camera['id']; ?>" class="btn btn-small">Editar</a>
                                    <a href="delete_camera.php?id=<?php echo $camera['id']; ?>" class="btn btn-small btn-danger" onclick="return confirm('¿Estás seguro?')">Eliminar</a>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </main>
    </div>
</body>
</html>
