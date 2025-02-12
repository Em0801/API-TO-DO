<?php

class Auth
{
    private $conn;

    public function __construct($db)
    {
        $this->conn = $db;
    }

    /**
     * Valida si una API key es válida y pertenece a un usuario activo.
     * @param string $apiKey
     * @return bool
     * @throws Exception
     */
    public function validateApiKey($apiKey)
    {
        writeLog("Validando API Key: " . substr($apiKey, 0, 5) . '...');

        if (empty($apiKey)) {
            writeLog("API Key vacía", 'ERROR');
            return false;
        }

        try {
            $sql = "SELECT * FROM api_keys WHERE api_key = :api_key";
            writeLog("SQL Query: " . $sql);
            
            $stmt = $this->conn->prepare($sql);
            $stmt->bindValue(':api_key', $apiKey);
            $stmt->execute();

            $isValid = $stmt->rowCount() > 0;
            writeLog("API Key " . ($isValid ? "válida" : "inválida"));

            return $isValid;

        } catch (PDOException $e) {
            writeLog("Error en validateApiKey: " . $e->getMessage(), 'ERROR');
            return false;
        }
    }

    /**
     * Obtiene el ID del usuario asociado a una API key.
     * @param string $apiKey
     * @return int|null
     * @throws Exception
     */
    public function getUserIdByApiKey($apiKey)
    {
        writeLog("Obteniendo ID de usuario por API Key: " . substr($apiKey, 0, 5) . '...');

        if (empty($apiKey)) {
            throw new Exception('API key no puede estar vacía');
        }

        try {
            $sql = "SELECT user_id FROM api_keys WHERE api_key = :api_key";
            $stmt = $this->conn->prepare($sql);
            $stmt->bindValue(':api_key', $apiKey);
            $stmt->execute();

            $result = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$result) {
                writeLog("No se encontró usuario para la API Key", 'WARNING');
                return null;
            }

            writeLog("Usuario encontrado con ID: " . $result['user_id']);
            return $result['user_id'];

        } catch (PDOException $e) {
            writeLog("Error en getUserIdByApiKey: " . $e->getMessage(), 'ERROR');
            return null;
        }
    }

    /**
     * Obtiene la información del usuario
     * @param int $userId
     * @return array
     * @throws PDOException
     */
    public function getUserData($userId)
    {
        if (empty($userId)) {
            throw new InvalidArgumentException('ID de usuario no válido');
        }

        $sql = "SELECT id, username, status, created_at 
                FROM users 
                WHERE id = :user_id AND status = 'active'";

        try {
            $stmt = $this->conn->prepare($sql);
            $stmt->bindParam(':user_id', $userId, PDO::PARAM_INT);
            $stmt->execute();

            $userData = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$userData) {
                throw new RuntimeException('Usuario no encontrado');
            }

            // Removemos datos sensibles antes de enviar
            unset($userData['password']);

            return $userData;
        } catch (PDOException $e) {
            error_log("Error al obtener datos del usuario: " . $e->getMessage());
            throw $e;
        }
    }

    public function getUserInfo($apiKey) {
        writeLog("Obteniendo información de usuario por API Key: " . substr($apiKey, 0, 5) . '...');

        try {
            $sql = "SELECT u.id, u.username 
                    FROM users u 
                    INNER JOIN api_keys ak ON u.id = ak.user_id 
                    WHERE ak.api_key = :api_key";

            writeLog("SQL Query: " . $sql);

            $stmt = $this->conn->prepare($sql);
            $stmt->bindValue(':api_key', $apiKey);
            $stmt->execute();

            $user = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$user) {
                writeLog("Usuario no encontrado", 'WARNING');
                throw new Exception('Usuario no encontrado');
            }

            writeLog("Información de usuario recuperada exitosamente");

            return [
                'status' => 'success',
                'message' => 'Usuario encontrado',
                'count' => 1,
                'data' => $user
            ];

        } catch (PDOException $e) {
            writeLog("Error en getUserInfo: " . $e->getMessage(), 'ERROR');
            throw $e;
        }
    }
}
