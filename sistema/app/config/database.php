<?php

/*
==============================
CONEXIÓN BASE DE DATOS
==============================
*/

if (!function_exists('cfg')) {
    function cfg(string $envKey, string $default): string
    {
        $value = getenv($envKey);
        return ($value !== false && $value !== '') ? $value : $default;
    }
}

$DB_HOST = cfg('DB_HOST', "localhost");
$DB_USER = cfg('DB_USER', "u412805401_suaveurbanst");
$DB_PASS = cfg('DB_PASS', "Adamitas27@");
$DB_NAME = cfg('DB_NAME', "u412805401_suaveurbanst");

/*
==============================
URL DEL SISTEMA
==============================
*/

$APP_URL = cfg('APP_URL', "https://suaveurbanstudio.com.mx");

/*
==============================
CONEXIÓN MYSQL
==============================
*/

if (!class_exists('mysqli')) {
    http_response_code(500);
    die('La extensión mysqli no está disponible en este servidor.');
}

mysqli_report(MYSQLI_REPORT_OFF);

$conn = @new mysqli($DB_HOST, $DB_USER, $DB_PASS, $DB_NAME);

if ($conn->connect_error) {
    http_response_code(500);
    die("Error de conexión a la base de datos: " . $conn->connect_error);
}

$conn->set_charset("utf8mb4");

/*
==============================
CONSTANTES DEL SISTEMA
==============================
*/

if (!defined('APP_URL')) {
    define('APP_URL', $APP_URL);
}

/*
==============================
CREDENCIALES GREEN API SEGURAS
==============================
*/

require_once __DIR__ . "/../private/secure_greenapi.php";
