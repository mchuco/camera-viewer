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

// Eliminar la cámara
$delete_query = "DELETE FROM cameras WHERE id = ? AND user_id = ?";
$delete_stmt = $conn->prepare($delete_query);
$delete_stmt->bind_param("ii", $camera_id, $user_id);
$delete_stmt->execute();
$delete_stmt->close();

header('Location: index.php?deleted=1');
exit;
?>
