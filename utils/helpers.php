<?php

/**
 * Validar que los datos obligatorios están presentes en un array.
 * @param array $requiredKeys
 * @param array $data
 * @return array|null Retorna null si no hay errores, o un array con las claves faltantes
 */
function validateRequiredKeys(array $requiredKeys, array $data): ?array {
    $missing = [];
    foreach ($requiredKeys as $key) {
        if (!array_key_exists($key, $data) || $data[$key] === null || $data[$key] === '') {
            $missing[] = $key;
        }
    }
    return empty($missing) ? null : $missing;
}

/**
 * Sanitiza un string para prevenir XSS
 * @param string $string
 * @return string
 */
function sanitizeString(string $string): string {
    return htmlspecialchars(strip_tags(trim($string)), ENT_QUOTES, 'UTF-8');
}

/**
 * Sanitiza un array recursivamente
 * @param array $array
 * @return array
 */
function sanitizeArray(array $array): array {
    foreach ($array as $key => $value) {
        if (is_array($value)) {
            $array[$key] = sanitizeArray($value);
        } else if (is_string($value)) {
            $array[$key] = sanitizeString($value);
        }
    }
    return $array;
}

/**
 * Valida si un string es una fecha válida
 * @param string $date
 * @param string $format
 * @return bool
 */
function isValidDate(string $date, string $format = 'Y-m-d'): bool {
    $d = DateTime::createFromFormat($format, $date);
    return $d && $d->format($format) === $date;
}

/**
 * Genera una respuesta JSON estandarizada
 * @param string $status 'success', 'fail', o 'error'
 * @param string $message
 * @param mixed $data
 * @param int $count
 * @return array
 */
function generateResponse(string $status, string $message, $data = null, int $count = 0): array {
    return [
        'status' => $status,
        'message' => $message,
        'count' => $count,
        'data' => $data
    ];
}

/**
 * Valida si un email es válido
 * @param string $email
 * @return bool
 */
function isValidEmail(string $email): bool {
    return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
}

/**
 * Obtiene el método HTTP actual
 * @return string
 */
function getRequestMethod(): string {
    return $_SERVER['REQUEST_METHOD'] ?? 'GET';
}

/**
 * Obtiene los headers de la petición
 * @return array
 */
function getRequestHeaders(): array {
    $headers = [];
    foreach ($_SERVER as $key => $value) {
        if (substr($key, 0, 5) === 'HTTP_') {
            $header = str_replace(' ', '-', ucwords(str_replace('_', ' ', strtolower(substr($key, 5)))));
            $headers[$header] = $value;
        }
    }
    return $headers;
}

/**
 * Valida si una petición es AJAX
 * @return bool
 */
function isAjaxRequest(): bool {
    return !empty($_SERVER['HTTP_X_REQUESTED_WITH']) && 
           strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';
}

/**
 * Limita un string a un número específico de caracteres
 * @param string $string
 * @param int $limit
 * @param string $end
 * @return string
 */
function truncateString(string $string, int $limit = 100, string $end = '...'): string {
    if (mb_strlen($string) <= $limit) {
        return $string;
    }
    return rtrim(mb_substr($string, 0, $limit)) . $end;
}

/**
 * Función para escribir logs en el archivo personalizado
 */
function writeLog($message, $type = 'INFO') {
    $logFile = __DIR__ . '/../logs/api.log';
    $timestamp = date('Y-m-d H:i:s');
    $logMessage = "[$timestamp] [$type] $message" . PHP_EOL;
    
    // Asegurarse de que el directorio logs existe
    if (!is_dir(dirname($logFile))) {
        mkdir(dirname($logFile), 0777, true);
    }
    
    // Escribir el log
    file_put_contents($logFile, $logMessage, FILE_APPEND);
}

/**
 * Manejador personalizado de errores
 */
function customErrorHandler($errno, $errstr, $errfile, $errline) {
    $errorType = match($errno) {
        E_ERROR => 'ERROR',
        E_WARNING => 'WARNING',
        E_NOTICE => 'NOTICE',
        default => 'UNKNOWN'
    };
    
    $message = "$errstr in $errfile on line $errline";
    writeLog($message, $errorType);
    
    return true;
}
