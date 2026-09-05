<?php
session_start();
include 'config/db.php';
include 'config/functions.php';

if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    exit;
}

$user_id = $_SESSION['user_id'];
$camera_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

if ($camera_id <= 0) {
    http_response_code(404);
    exit;
}

// Obtener datos de la cámara
$query = "SELECT * FROM cameras WHERE id = ? AND user_id = ?";
$stmt = $conn->prepare($query);
$stmt->bind_param("ii", $camera_id, $user_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    http_response_code(404);
    exit;
}

$camera = $result->fetch_assoc();
$stmt->close();

// Desencriptar contraseña
$password = base64_decode($camera['password']);

$ip = $camera['ip_address'];
$username = $camera['username'];
$port = 80;

// Para Wisenet, usar la interfaz web interna que ya está transmitiendo
// El elemento div con id="cm-video" es donde se reproduce el stream

// Opciones de URL para obtener el stream de video
$stream_urls = array(
    // Endpoint MJPEG directo (si está disponible)
    "http://{$ip}:{$port}/motion/mjpegserver",
    // Endpoint de video streaming
    "http://{$ip}:{$port}/cgi-bin/video.cgi",
    // Alternativa CGI
    "http://{$ip}:{$port}/cgi-bin/viewer.cgi",
);

// Configurar headers para streaming MJPEG
header('Content-Type: multipart/x-mixed-replace; boundary=--BOUNDARY');
header('Cache-Control: no-cache');
header('Connection: keep-alive');
header('Pragma: no-cache');

// Intentar cada URL hasta encontrar la que funcione
$success = false;

foreach ($stream_urls as $stream_url) {
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $stream_url);
    curl_setopt($ch, CURLOPT_USERPWD, "{$username}:{$password}");
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, false);
    curl_setopt($ch, CURLOPT_BINARYTRANSFER, true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
    curl_setopt($ch, CURLOPT_TIMEOUT, 300);
    curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 5);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
    curl_setopt($ch, CURLOPT_HTTPAUTH, CURLAUTH_BASIC);
    
    // Ejecutar petición
    $result = curl_exec($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $error = curl_error($ch);
    curl_close($ch);
    
    // Si la conexión fue exitosa, enviar el stream
    if ($http_code == 200 && !$error) {
        $success = true;
        break;
    }
}

if (!$success) {
    // Si no funciona MJPEG, intentar obtener un frame estático
    header('Content-Type: image/jpeg');
    
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, "http://{$ip}:{$port}/cgi-bin/snapshot.cgi");
    curl_setopt($ch, CURLOPT_USERPWD, "{$username}:{$password}");
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_BINARYTRANSFER, true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
    curl_setopt($ch, CURLOPT_TIMEOUT, 5);
    curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 3);
    curl_setopt($ch, CURLOPT_HTTPAUTH, CURLAUTH_BASIC);
    
    $image = curl_exec($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    if ($http_code == 200 && !empty($image)) {
        echo $image;
    } else {
        // Placeholder si no funciona nada
        $img = imagecreate(800, 600);
        $red = imagecolorallocate($img, 255, 0, 0);
        $white = imagecolorallocate($img, 255, 255, 255);
        imagefill($img, 0, 0, $red);
        imagestring($img, 5, 300, 280, "Camera Offline", $white);
        imagejpeg($img);
        imagedestroy($img);
    }
}
?>
