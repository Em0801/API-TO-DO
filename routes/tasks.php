<?php

require_once __DIR__ . '/../classes/task.class.php';

try {
    // Validar que el usuario está autenticado
    $headers = getallheaders();
    $apiKey = $headers['Authorization'] ?? null;

    if (!$apiKey) {
        throw new Exception('API Key no proporcionada');
    }

    if (!$auth->validateApiKey($apiKey)) {
        throw new Exception('API Key inválida');
    }

    // Obtener ID del usuario asociado a la API Key
    $userId = $auth->getUserIdByApiKey($apiKey);
    
    if (!$userId) {
        throw new Exception('Usuario no encontrado');
    }

    // Manejar rutas específicas
    switch ($method) {
        case 'POST':
            $input = json_decode(file_get_contents('php://input'), true);
            if (json_last_error() !== JSON_ERROR_NONE) {
                throw new Exception('JSON inválido: ' . json_last_error_msg());
            }
            $response = $task->createTask($input, $userId);
            break;

        case 'GET':
            $response = $task->getTasks($userId);
            break;

        case 'PUT':
            if (!isset($endpoint[1]) || !is_numeric($endpoint[1])) {
                throw new InvalidArgumentException('ID de tarea no válido');
            }
            
            $taskId = (int)$endpoint[1];
            $input = json_decode(file_get_contents('php://input'), true);
            
            if (json_last_error() !== JSON_ERROR_NONE) {
                throw new Exception('JSON inválido: ' . json_last_error_msg());
            }
            
            $response = $task->updateTask($taskId, $input, $userId);
            break;

        case 'DELETE':
            if (!isset($endpoint[1])) {
                throw new Exception('ID de tarea no proporcionado');
            }
            $response = $task->deleteTask($endpoint[1], $userId);
            break;

        default:
            throw new Exception('Método no permitido');
    }

    echo json_encode($response);

} catch (InvalidArgumentException $e) {
    http_response_code(400);
    echo json_encode([
        'status' => 'fail',
        'message' => $e->getMessage(),
        'count' => 0,
        'data' => null
    ]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'status' => 'error',
        'message' => $e->getMessage(),
        'count' => 0,
        'data' => null
    ]);
}