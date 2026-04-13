<?php

require_once __DIR__ . '/../config/whatsapp.php';
require_once __DIR__ . '/greenapi_helper.php';

function whatsappLimpiarTelefono(string $telefono): string
{
    return preg_replace('/\D+/', '', trim($telefono));
}

function whatsappNormalizarTelefonoMX521(string $telefono): string
{
    $telefono = whatsappLimpiarTelefono($telefono);

    if ($telefono === '') {
        return '';
    }

    if (strlen($telefono) === 10) {
        return '521' . $telefono;
    }

    if (strlen($telefono) === 13 && substr($telefono, 0, 3) === '521') {
        return $telefono;
    }

    if (strlen($telefono) === 12 && substr($telefono, 0, 2) === '52') {
        return '521' . substr($telefono, 2);
    }

    if (strlen($telefono) > 13 && substr($telefono, 0, 3) === '521') {
        return '521' . substr($telefono, -10);
    }

    return $telefono;
}

function whatsappObtenerCredenciales(): array
{
    $cred = cargarCredencialesGreenApi();

    $instance = trim((string)($cred['instance'] ?? ''));
    $token = trim((string)($cred['token'] ?? ''));

    if ($instance === '' && defined('GREEN_API_INSTANCE_ID')) {
        $instance = trim((string)GREEN_API_INSTANCE_ID);
    }

    if ($token === '' && defined('GREEN_API_TOKEN')) {
        $token = trim((string)GREEN_API_TOKEN);
    }

    if ($instance === '' || $token === '') {
        return [
            'ok' => false,
            'instance' => '',
            'token' => '',
            'mensaje' => 'Green API no está configurada correctamente.'
        ];
    }

    return [
        'ok' => true,
        'instance' => $instance,
        'token' => $token,
        'mensaje' => 'Credenciales listas.'
    ];
}

function whatsappConstruirUrl(string $instance, string $token): string
{
    $host = defined('GREENAPI_API_HOST') ? trim((string)GREENAPI_API_HOST) : '7107.api.greenapi.com';
    return 'https://' . $host . '/waInstance' . rawurlencode($instance) . '/sendMessage/' . rawurlencode($token);
}

function whatsappEnviarMensaje(string $telefono, string $mensaje, ?string $instance = null, ?string $token = null): array
{
    $mensaje = trim($mensaje);
    $telefonoNormalizado = whatsappNormalizarTelefonoMX521($telefono);

    if ($telefonoNormalizado === '') {
        return ['ok' => false, 'error' => 'Teléfono vacío'];
    }

    if ($mensaje === '') {
        return ['ok' => false, 'error' => 'Mensaje vacío'];
    }

    if ($instance === null || $token === null || trim($instance) === '' || trim($token) === '') {
        $cred = whatsappObtenerCredenciales();
        if (empty($cred['ok'])) {
            return ['ok' => false, 'error' => (string)($cred['mensaje'] ?? 'Sin credenciales')];
        }
        $instance = (string)$cred['instance'];
        $token = (string)$cred['token'];
    }

    $url = whatsappConstruirUrl((string)$instance, (string)$token);
    $payload = [
        'chatId' => $telefonoNormalizado . '@c.us',
        'message' => $mensaje
    ];

    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST => true,
        CURLOPT_HTTPHEADER => ['Content-Type: application/json'],
        CURLOPT_POSTFIELDS => json_encode($payload, JSON_UNESCAPED_UNICODE),
        CURLOPT_TIMEOUT => (int)GREENAPI_REQUEST_TIMEOUT,
        CURLOPT_SSL_VERIFYPEER => true,
        CURLOPT_SSL_VERIFYHOST => 2
    ]);

    $response = curl_exec($ch);
    $curlError = curl_error($ch);
    $httpCode = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($curlError) {
        return ['ok' => false, 'error' => 'cURL: ' . $curlError, 'httpCode' => $httpCode];
    }

    $json = json_decode((string)$response, true);

    if ($httpCode >= 200 && $httpCode < 300 && is_array($json) && !empty($json['idMessage'])) {
        return [
            'ok' => true,
            'chatId' => $payload['chatId'],
            'idMessage' => (string)$json['idMessage'],
            'httpCode' => $httpCode
        ];
    }

    return [
        'ok' => false,
        'error' => 'Green API respondió con error',
        'httpCode' => $httpCode,
        'response' => $json ?: $response,
        'chatId' => $payload['chatId']
    ];
}
