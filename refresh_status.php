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

// Desencriptar contraseña
$password = base64_decode($camera['password']);
$username = $camera['username'];
$ip = $camera['ip_address'];
$port = $camera['port'];

// Si es una solicitud AJAX, retornar JSON
if (isset($_GET['ajax'])) {
    header('Content-Type: application/json');
    
    // Verificar conexión
    $connection_status = checkCameraConnection($ip, $username, $password, $port) ? 'online' : 'offline';
    
    // Actualizar estado si cambió
    if ($connection_status !== $camera['status']) {
        $update_query = "UPDATE cameras SET status = ? WHERE id = ?";
        $update_stmt = $conn->prepare($update_query);
        $update_stmt->bind_param("si", $connection_status, $camera_id);
        $update_stmt->execute();
        $update_stmt->close();
    }
    
    echo json_encode(['status' => $connection_status]);
    exit;
}

// Redireccionar a view_camera después de actualizar
header('Location: view_camera.php?id=' . $camera_id);
?>
