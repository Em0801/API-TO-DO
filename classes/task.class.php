<?php

class Task {
    private $conn;

    public function __construct($db) {
        $this->conn = $db;
    }

    /**
     * Crear una nueva tarea
     */
    public function createTask($data, $userId) {
        writeLog("Iniciando createTask - Data: " . print_r($data, true) . ", UserID: " . $userId);

        if (empty($data['title']) || empty($userId)) {
            writeLog("Error de validación: título o userId vacío", 'ERROR');
            throw new InvalidArgumentException('El título y el ID de usuario son requeridos');
        }

        try {
            $title = trim($data['title']);
            $description = isset($data['description']) ? trim($data['description']) : '';
            $status = isset($data['status']) && in_array($data['status'], ['pending', 'completed']) 
                     ? $data['status'] 
                     : 'pending';

            $sql = "INSERT INTO tasks (user_id, title, description, status, created_at, updated_at) 
                    VALUES (:user_id, :title, :description, :status, NOW(), NOW())";
            
            writeLog("SQL Query: " . $sql);

            $stmt = $this->conn->prepare($sql);
            
            $stmt->bindValue(':user_id', $userId, PDO::PARAM_INT);
            $stmt->bindValue(':title', $title, PDO::PARAM_STR);
            $stmt->bindValue(':description', $description, PDO::PARAM_STR);
            $stmt->bindValue(':status', $status, PDO::PARAM_STR);

            $stmt->execute();
            
            $taskId = $this->conn->lastInsertId();
            writeLog("Tarea creada con ID: " . $taskId);

            // Obtener la tarea creada
            $sql = "SELECT * FROM tasks WHERE id = :id";
            $stmt = $this->conn->prepare($sql);
            $stmt->bindValue(':id', $taskId, PDO::PARAM_INT);
            $stmt->execute();
            $task = $stmt->fetch(PDO::FETCH_ASSOC);

            return [
                'status' => 'success',
                'message' => 'Tarea creada exitosamente',
                'count' => 1,
                'data' => $task
            ];

        } catch (PDOException $e) {
            writeLog("Error PDO en createTask: " . $e->getMessage(), 'ERROR');
            throw $e;
        }
    }

    /**
     * Obtener todas las tareas
     */
    public function getTasks($userId) {
        writeLog("Obteniendo tareas para el usuario: " . $userId);

        if (empty($userId)) {
            writeLog("Error: ID de usuario no proporcionado", 'ERROR');
            throw new InvalidArgumentException('El ID de usuario es requerido');
        }

        try {
            $sql = "SELECT * FROM tasks WHERE user_id = :user_id ORDER BY created_at DESC";
            $stmt = $this->conn->prepare($sql);
            $stmt->bindValue(':user_id', $userId, PDO::PARAM_INT);
            $stmt->execute();
            
            $tasks = $stmt->fetchAll(PDO::FETCH_ASSOC);
            writeLog("Tareas encontradas: " . count($tasks));

            return [
                'status' => 'success',
                'message' => 'Tareas obtenidas exitosamente',
                'count' => count($tasks),
                'data' => $tasks
            ];

        } catch (PDOException $e) {
            writeLog("Error en getTasks: " . $e->getMessage(), 'ERROR');
            throw $e;
        }
    }

    /**
     * Actualizar una tarea existente.
     * @param int $taskId
     * @param array $data
     * @param int $userId
     * @return array
     * @throws Exception
     */
    public function updateTask($taskId, $data, $userId) {
        writeLog("Iniciando updateTask - TaskID: $taskId, UserID: $userId, Data: " . print_r($data, true));

        if (empty($taskId) || empty($userId)) {
            writeLog("Error: ID de tarea o usuario no proporcionado", 'ERROR');
            throw new InvalidArgumentException('ID de tarea y usuario son requeridos');
        }

        try {
            // Verificar que la tarea existe y pertenece al usuario
            $sql = "SELECT * FROM tasks WHERE id = :id AND user_id = :user_id";
            writeLog("SQL Verificación: " . $sql);
            
            $stmt = $this->conn->prepare($sql);
            $stmt->bindValue(':id', $taskId, PDO::PARAM_INT);
            $stmt->bindValue(':user_id', $userId, PDO::PARAM_INT);
            $stmt->execute();

            if ($stmt->rowCount() === 0) {
                writeLog("Tarea no encontrada o no pertenece al usuario", 'WARNING');
                throw new RuntimeException('Tarea no encontrada');
            }

            // Preparar campos a actualizar
            $updates = [];
            $params = [
                ':id' => $taskId,
                ':user_id' => $userId
            ];

            if (!empty($data['title'])) {
                $updates[] = "title = :title";
                $params[':title'] = trim($data['title']);
            }

            if (isset($data['description'])) {
                $updates[] = "description = :description";
                $params[':description'] = trim($data['description']);
            }

            if (isset($data['status'])) {
                $validStatus = ['pending', 'completed'];
                $status = trim(strtolower($data['status']));
                
                if (!in_array($status, $validStatus)) {
                    writeLog("Estado no válido proporcionado: " . $status, 'WARNING');
                    throw new InvalidArgumentException('Estado no válido. Debe ser: pending o completed');
                }
                
                $updates[] = "status = :status";
                $params[':status'] = $status;
            }

            if (empty($updates)) {
                writeLog("No hay campos para actualizar", 'WARNING');
                throw new InvalidArgumentException('No hay datos para actualizar');
            }

            $updates[] = "updated_at = NOW()";

            $sql = "UPDATE tasks SET " . implode(', ', $updates) . 
                   " WHERE id = :id AND user_id = :user_id";

            writeLog("SQL Update: " . $sql);
            writeLog("Parámetros: " . print_r($params, true));

            $stmt = $this->conn->prepare($sql);
            foreach ($params as $key => $value) {
                $stmt->bindValue($key, $value);
            }
            
            $stmt->execute();
            writeLog("Tarea actualizada exitosamente");

            // Obtener la tarea actualizada
            $sql = "SELECT * FROM tasks WHERE id = :id";
            $stmt = $this->conn->prepare($sql);
            $stmt->bindValue(':id', $taskId, PDO::PARAM_INT);
            $stmt->execute();
            
            return [
                'status' => 'success',
                'message' => 'Tarea actualizada exitosamente',
                'count' => 1,
                'data' => $stmt->fetch(PDO::FETCH_ASSOC)
            ];

        } catch (PDOException $e) {
            writeLog("Error PDO en updateTask: " . $e->getMessage(), 'ERROR');
            throw $e;
        }
    }

    /**
     * Eliminar una tarea (cambiar status a deleted)
     */
    public function deleteTask($taskId, $userId) {
        writeLog("Iniciando deleteTask - TaskID: $taskId, UserID: $userId");

        if (empty($taskId) || empty($userId)) {
            writeLog("Error: ID de tarea o usuario no proporcionado", 'ERROR');
            throw new InvalidArgumentException('ID de tarea y usuario son requeridos');
        }

        try {
            // Primero verificamos que la tarea existe y pertenece al usuario
            $checkSql = "SELECT id FROM tasks WHERE id = :id AND user_id = :user_id";
            $checkStmt = $this->conn->prepare($checkSql);
            $checkStmt->bindValue(':id', $taskId, PDO::PARAM_INT);
            $checkStmt->bindValue(':user_id', $userId, PDO::PARAM_INT);
            $checkStmt->execute();

            if ($checkStmt->rowCount() === 0) {
                writeLog("Tarea no encontrada o no pertenece al usuario", 'WARNING');
                throw new RuntimeException('Tarea no encontrada');
            }

            // Si la tarea existe, procedemos a eliminarla
            $sql = "DELETE FROM tasks WHERE id = :id AND user_id = :user_id";
            writeLog("SQL Delete: " . $sql);

            $stmt = $this->conn->prepare($sql);
            $stmt->bindValue(':id', $taskId, PDO::PARAM_INT);
            $stmt->bindValue(':user_id', $userId, PDO::PARAM_INT);
            $stmt->execute();

            writeLog("Tarea eliminada exitosamente");

            return [
                'status' => 'success',
                'message' => 'Tarea eliminada exitosamente',
                'count' => 1
            ];

        } catch (PDOException $e) {
            writeLog("Error en deleteTask: " . $e->getMessage(), 'ERROR');
            throw $e;
        }
    }
}