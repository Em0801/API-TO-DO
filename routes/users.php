<?php

require_once __DIR__ . '/../classes/auth.class.php';

try {
    writeLog("Iniciando procesamiento de ruta /users");

    // Validar que el usuario está autenticado
    $headers = getallheaders();
    $apiKey = $headers['Authorization'] ?? null;

    if (!$apiKey) {
        writeLog("API Key no proporcionada", 'ERROR');
        throw new Exception('API Key no proporcionada');
    }

    writeLog("API Key recibida: " . substr($apiKey, 0, 5) . '...');

    if (!$auth->validateApiKey($apiKey)) {
        writeLog("API Key inválida", 'ERROR');
        throw new Exception('API Key inválida');
    }

    // Manejar solo solicitudes GET
    if ($method !== 'GET') {
        writeLog("Método no permitido: $method", 'WARNING');
        throw new Exception('Método no permitido');
    }

    writeLog("Obteniendo información del usuario");
    $response = $auth->getUserInfo($apiKey);
    
    writeLog("Información de usuario obtenida exitosamente");
    echo json_encode($response);

} catch (InvalidArgumentException $e) {
    writeLog("Error de validación: " . $e->getMessage(), 'ERROR');
    http_response_code(400);
    echo json_encode([
        'status' => 'fail',
        'message' => $e->getMessage(),
        'count' => 0,
        'data' => null
    ]);
} catch (Exception $e) {
    writeLog("Error en users.php: " . $e->getMessage(), 'ERROR');
    http_response_code(500);
    echo json_encode([
        'status' => 'error',
        'message' => $e->getMessage(),
        'count' => 0,
        'data' => null
    ]);
}