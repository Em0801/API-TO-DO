<?php

class DB
{
    private $host;
    private $db_name;
    private $username;
    private $password;
    private $conn;

    public function __construct()
    {
        // Configuración desde variables de entorno
        $this->host = getenv('DB_HOST') ?: 'localhost';
        $this->db_name = getenv('DB_NAME') ?: 'api_to_do';
        $this->username = getenv('DB_USER') ?: 'root';
        $this->password = getenv('DB_PASSWORD') ?: '';
    }

    /**
     * Establece una conexión a la base de datos.
     * @return PDO
     * @throws PDOException
     */
    public function connect()
    {
        if ($this->conn !== null) {
            return $this->conn;
        }

        try {
            $dsn = "mysql:host={$this->host};dbname={$this->db_name};charset=utf8mb4";
            $options = [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false
            ];

            $this->conn = new PDO($dsn, $this->username, $this->password, $options);
            return $this->conn;
        } catch (PDOException $e) {
            throw new PDOException('Error de conexión a la base de datos: ' . $e->getMessage());
        }
    }

    /**
     * Cierra la conexión a la base de datos
     */
    public function disconnect()
    {
        $this->conn = null;
    }

    /**
     * Obtiene la instancia de conexión actual
     * @return PDO|null
     */
    public function getConnection()
    {
        return $this->conn;
    }
}
