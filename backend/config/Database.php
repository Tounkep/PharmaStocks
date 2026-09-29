<?php

class Database
{
    private string $host = 'localhost';
    private string $port = '3306';
    private string $database = 'gestion_pharmacie';
    private string $username = 'root';
    private string $password = '';

    public function getConnection(): PDO
    {
        $dsn = "mysql:host={$this->host};port={$this->port};dbname={$this->database};charset=utf8mb4";

        try {
            $pdo = new PDO(
                $dsn,
                $this->username,
                $this->password,
                [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                    PDO::ATTR_EMULATE_PREPARES => false
                ]
            );

            return $pdo;

        } catch (PDOException $e) {
            throw new PDOException(
                'Erreur de connexion à la base de données : ' . $e->getMessage(),
                (int) $e->getCode()
            );
        }
    }
}