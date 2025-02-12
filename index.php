<?php

// Configuración inicial
error_reporting(E_ALL);
ini_set('display_errors', 0);
date_default_timezone_set('Europe/Madrid');

// Incluir helpers primero para tener disponibles las funciones de log
require_once __DIR__ . '/utils/helpers.php';

// Configurar el manejador de errores personalizado
set_error_handler('customErrorHandler');

// Configurar el log de errores de PHP
ini_set('error_log', __DIR__ . '/logs/api.log');
ini_set('log_errors', 1);

// Headers para CORS y tipo de contenido
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: GET,POST,PUT,DELETE");
header("Access-Control-Allow-Headers: Content-Type, Authorization");

// Incluir archivos necesarios
require_once __DIR__ . '/classes/db.class.php';
require_once __DIR__ . '/classes/auth.class.php';
require_once __DIR__ . '/classes/task.class.php';

try {
    writeLog("Nueva solicitud recibida: " . $_SERVER['REQUEST_METHOD'] . " " . $_SERVER['REQUEST_URI']);
    
    // Instanciar clases necesarias
    $database = new DB();
    $db = $database->connect();
    $auth = new Auth($db);
    $task = new Task($db);

    // Obtener método y ruta
    $method = getRequestMethod();
    
    // Procesar la URL correctamente
    $requestUri = $_SERVER['REQUEST_URI'];
    $basePath = '/API-TO-DO/'; // Ajusta esto según tu configuración
    
    // Eliminar la base path y cualquier query string
    $uri = str_replace($basePath, '', $requestUri);
    $uri = strtok($uri, '?');
    
    // Dividir la ruta en segmentos
    $endpoint = array_values(array_filter(explode('/', $uri)));
    
    // Debug
    error_log("Request URI: " . $requestUri);
    error_log("URI procesada: " . $uri);
    error_log("Endpoint array: " . print_r($endpoint, true));

    // Validar endpoint
    if (empty($endpoint[0])) {
        throw new RuntimeException('Endpoint no especificado');
    }

    // Rutear a los controladores correspondientes
    switch ($endpoint[0]) {
        case 'tasks':
            require_once __DIR__ . '/routes/tasks.php';
            break;
        case 'users':
            require_once __DIR__ . '/routes/users.php';
            break;
        case 'auth':
            require_once __DIR__ . '/routes/auth.php';
            break;
        default:
            throw new RuntimeException('Endpoint no encontrado');
    }

} catch (InvalidArgumentException $e) {
    writeLog("Error de validación: " . $e->getMessage(), 'ERROR');
    http_response_code(400);
    echo json_encode(generateResponse(
        'fail',
        $e->getMessage()
    ));
} catch (RuntimeException $e) {
    writeLog("Error de ejecución: " . $e->getMessage(), 'ERROR');
    http_response_code(404);
    echo json_encode(generateResponse(
        'fail',
        $e->getMessage()
    ));
} catch (PDOException $e) {
    writeLog("Error de base de datos: " . $e->getMessage(), 'ERROR');
    http_response_code(500);
    echo json_encode(generateResponse(
        'error',
        'Error interno del servidor'
    ));
} catch (Exception $e) {
    writeLog("Error general: " . $e->getMessage(), 'ERROR');
    http_response_code(500);
    echo json_encode(generateResponse(
        'error',
        'Error interno del servidor'
    ));
}

// Cerrar conexión a la base de datos
if (isset($database)) {
    $database->disconnect();
}
