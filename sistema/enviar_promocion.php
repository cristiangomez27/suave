<?php
session_start();
header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/app/config/bootstrap.php';

if (file_exists(__DIR__ . '/app/helpers/whatsapp_helper.php')) {
    require_once __DIR__ . '/app/helpers/whatsapp_helper.php';
}

if (!isset($_SESSION['usuario_id'])) {
    echo json_encode([
        'ok' => false,
        'mensaje' => 'Sesión inválida'
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

if (!function_exists('whatsappObtenerCredenciales') || !function_exists('whatsappEnviarMensaje')) {
    echo json_encode([
        'ok' => false,
        'mensaje' => 'No se encontró el helper central de WhatsApp'
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

function responder($ok, $mensaje, $extra = [])
{
    echo json_encode(array_merge([
        'ok' => (bool)$ok,
        'mensaje' => $mensaje
    ], $extra), JSON_UNESCAPED_UNICODE);
    exit;
}

try {
    $data = json_decode(file_get_contents('php://input'), true);

    if (!is_array($data)) {
        responder(false, 'No se recibieron datos válidos');
    }

    $cred = whatsappObtenerCredenciales();
    if (empty($cred['ok'])) {
        responder(false, (string)($cred['mensaje'] ?? 'Green API no está configurada correctamente'));
    }

    $greenInstance = (string)$cred['instance'];
    $greenToken = (string)$cred['token'];

    $mensaje = trim((string)($data['mensaje'] ?? ''));
    $clientes = $data['clientes'] ?? [];

    if ($mensaje === '') {
        responder(false, 'Escribe el mensaje de promoción');
    }

    if (!is_array($clientes) || count($clientes) === 0) {
        responder(false, 'No hay clientes seleccionados');
    }

    $enviados = 0;
    $fallidos = 0;
    $detalleOk = [];
    $detalleFallidos = [];

    foreach ($clientes as $cliente) {
        $nombre = trim((string)($cliente['nombre'] ?? 'Cliente'));
        $telefonoOriginal = trim((string)($cliente['telefono'] ?? ''));

        if ($telefonoOriginal === '') {
            $fallidos++;
            $detalleFallidos[] = $nombre . ': sin teléfono';
            continue;
        }

        $mensajeFinal = "Hola {$nombre} 👋\n\n{$mensaje}\n\nSuave Urban Studio";
        $resultado = whatsappEnviarMensaje($telefonoOriginal, $mensajeFinal, $greenInstance, $greenToken);

        if (!empty($resultado['ok'])) {
            $enviados++;
            $detalleOk[] = $nombre . ': ' . ($resultado['chatId'] ?? 'ok');
        } else {
            $fallidos++;
            $detalleFallidos[] = $nombre . ': ' . ($resultado['error'] ?? 'No se pudo enviar') . ' | ' . ($resultado['chatId'] ?? '');
        }

        usleep(350000);
    }

    if ($enviados > 0 && $fallidos === 0) {
        responder(true, 'Promoción enviada', [
            'enviados' => $enviados,
            'fallidos' => $fallidos,
            'detalle_ok' => $detalleOk,
            'detalle_fallidos' => $detalleFallidos
        ]);
    }

    responder(false, "No se pudo completar el envío. Correctos: {$enviados}. Fallidos: {$fallidos}.", [
        'enviados' => $enviados,
        'fallidos' => $fallidos,
        'detalle_ok' => $detalleOk,
        'detalle_fallidos' => $detalleFallidos
    ]);

} catch (Throwable $e) {
    responder(false, 'Error al enviar promociones: ' . $e->getMessage());
}
?>
