<?php
session_start();
include 'config/db.php';
include 'config/functions.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

$user_id = $_SESSION['user_id'];
$camera_id = isset($_GET['id']) ? intval($_GET['id']) : 0;
$error = '';
$success = '';

if ($camera_id <= 0) {
    header('Location: index.php');
    exit;
}

// Obtener datos de la cámara
$query = "SELECT * FROM cameras WHERE id = ? AND user_id = ?";
$stmt = $conn->prepare($query);
$stmt->bind_param("ii", $camera_id, $user_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    header('Location: index.php');
    exit;
}

$camera = $result->fetch_assoc();
$stmt->close();

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $name = sanitizeInput($_POST['name'] ?? '');
    $ip_address = sanitizeInput($_POST['ip_address'] ?? '');
    $username = sanitizeInput($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';
    $port = sanitizeInput($_POST['port'] ?? '80');
    $description = sanitizeInput($_POST['description'] ?? '');
    
    if (empty($name) || empty($ip_address) || empty($username)) {
        $error = "Por favor completa todos los campos obligatorios";
    } else if (!isValidIP($ip_address)) {
        $error = "IP inválida";
    } else if (!is_numeric($port) || $port < 1 || $port > 65535) {
        $error = "Puerto inválido";
    } else {
        if (!empty($password)) {
            $encrypted_password = base64_encode($password);
        } else {
            $encrypted_password = $camera['password'];
        }
        
        $check_password = !empty($password) ? $password : base64_decode($camera['password']);
        $connection_status = checkCameraConnection($ip_address, $username, $check_password, $port) ? 'online' : 'offline';
        
        $query = "UPDATE cameras SET name = ?, ip_address = ?, username = ?, password = ?, port = ?, description = ?, status = ? WHERE id = ? AND user_id = ?";
        $stmt = $conn->prepare($query);
        
        if (!$stmt) {
            $error = "Error en la consulta: " . $conn->error;
        } else {
            $stmt->bind_param("ssssisii", $name, $ip_address, $username, $encrypted_password, $port, $description, $connection_status, $camera_id, $user_id);
            
            if ($stmt->execute()) {
                $success = "Cámara actualizada exitosamente";
                $camera['name'] = $name;
                $camera['ip_address'] = $ip_address;
                $camera['username'] = $username;
                $camera['port'] = $port;
                $camera['description'] = $description;
                $camera['status'] = $connection_status;
            } else {
                $error = "Error al actualizar la cámara: " . $stmt->error;
            }
            $stmt->close();
        }
    }
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Editar Cámara</title>
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
    <div class="container">
        <header class="navbar">
            <div class="navbar-brand">
                <h1>🎥 Visor de Cámaras IP</h1>
            </div>
            <nav class="navbar-menu">
                <a href="index.php">Dashboard</a>
                <a href="logout.php">Cerrar Sesión</a>
            </nav>
        </header>

        <main class="main-content">
            <div class="form-container">
                <h2>✏️ Editar Cámara</h2>
                
                <?php if ($error): ?>
                    <div class="alert alert-error"><?php echo $error; ?></div>
                <?php endif; ?>
                
                <?php if ($success): ?>
                    <div class="alert alert-success"><?php echo $success; ?></div>
                <?php endif; ?>
                
                <form method="POST">
                    <div class="form-group">
                        <label for="name">Nombre de la Cámara: *</label>
                        <input type="text" id="name" name="name" value="<?php echo htmlspecialchars($camera['name']); ?>" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="ip_address">Dirección IP: *</label>
                        <input type="text" id="ip_address" name="ip_address" value="<?php echo htmlspecialchars($camera['ip_address']); ?>" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="port">Puerto:</label>
                        <input type="number" id="port" name="port" value="<?php echo $camera['port']; ?>" min="1" max="65535">
                    </div>
                    
                    <div class="form-group">
                        <label for="username">Usuario: *</label>
                        <input type="text" id="username" name="username" value="<?php echo htmlspecialchars($camera['username']); ?>" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="password">Contraseña: (Dejar en blanco para mantener la actual)</label>
                        <input type="password" id="password" name="password">
                    </div>
                    
                    <div class="form-group">
                        <label for="description">Descripción:</label>
                        <textarea id="description" name="description" rows="4"><?php echo htmlspecialchars($camera['description']); ?></textarea>
                    </div>
                    
                    <div class="form-actions">
                        <button type="submit" class="btn btn-primary">💾 Guardar Cambios</button>
                        <a href="view_camera.php?id=<?php echo $camera['id']; ?>" class="btn btn-secondary">❌ Cancelar</a>
                    </div>
                </form>
            </div>
        </main>
    </div>
</body>
</html>
