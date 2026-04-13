<?php
session_start();
header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/app/config/bootstrap.php';
require_once __DIR__ . '/app/helpers/whatsapp_helper.php';

if (!isset($_SESSION['usuario_id'])) {
    echo json_encode(['ok' => false, 'mensaje' => 'Sesión inválida'], JSON_UNESCAPED_UNICODE);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['ok' => false, 'mensaje' => 'Método no permitido'], JSON_UNESCAPED_UNICODE);
    exit;
}

$payload = json_decode((string)file_get_contents('php://input'), true);
if (!is_array($payload)) {
    $payload = $_POST;
}

$telefono = trim((string)($payload['telefono'] ?? ''));
$mensaje = trim((string)($payload['mensaje'] ?? ''));

if ($telefono === '') {
    echo json_encode(['ok' => false, 'mensaje' => 'Falta teléfono'], JSON_UNESCAPED_UNICODE);
    exit;
}

if ($mensaje === '') {
    echo json_encode(['ok' => false, 'mensaje' => 'Falta mensaje'], JSON_UNESCAPED_UNICODE);
    exit;
}

$cred = whatsappObtenerCredenciales();
if (empty($cred['ok'])) {
    echo json_encode(['ok' => false, 'mensaje' => (string)($cred['mensaje'] ?? 'Sin credenciales')], JSON_UNESCAPED_UNICODE);
    exit;
}

$resultado = whatsappEnviarMensaje($telefono, $mensaje, (string)$cred['instance'], (string)$cred['token']);

if (!empty($resultado['ok'])) {
    echo json_encode([
        'ok' => true,
        'mensaje' => 'Mensaje enviado',
        'chatId' => (string)($resultado['chatId'] ?? ''),
        'idMessage' => (string)($resultado['idMessage'] ?? '')
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

echo json_encode([
    'ok' => false,
    'mensaje' => (string)($resultado['error'] ?? 'No se pudo enviar mensaje'),
    'detalle' => $resultado
], JSON_UNESCAPED_UNICODE);
