<?php

class Database
{
    private $host;
    private $dbname;
    private $username;
    private $password;
    /**
     * @var PDO
     */
    private $pdo;

    public function __construct(string $host, string $dbname, string $username, string $password)
    {
        $this->host = $host;
        $this->dbname = $dbname;
        $this->username = $username;
        $this->password = $password;
    }

    /**
     * Connect to database
     *
     * @return boolean
     */
    public function connect(): bool
    {
        try {
            $this->pdo = new PDO("mysql:host=$this->host;dbname=$this->dbname;charset=utf8", $this->username, $this->password);
            $this->pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            return true;
        } catch (PDOException $e) {
            echo "Database connection failed: " . $e->getMessage();
            return false;
        }
    }

    /**
     * Disconnect from database
     *
     * @return void
     */
    public function disconnect()
    {
        $this->pdo = null;
    }

    /**
     * SELECT
     *
     * @param string $query
     * @return array|null
     */
    public function select(string $query)
    {
        $stmt = $this->pdo->prepare($query);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * INSERT
     *
     * @param string $query
     * @param array $params
     * @return boolean
     */
    public function insert(string $query, array $params): bool
    {
        $stmt = $this->pdo->prepare($query);
        return $stmt->execute($params);
    }

    /**
     * Get last inserted row id
     *
     * @return string|false
     */
    public function getlastInsertId()
    {
        return $this->pdo->lastInsertId();
    }

    /**
     * UPDATE
     *
     * @param string $query
     * @param array $params
     * @return boolean
     */
    public function update(string $query, array $params): bool
    {
        $stmt = $this->pdo->prepare($query);
        return $stmt->execute($params);
    }

    /**
     * DELETE
     *
     * @param string $query
     * @return int|false
     */
    public function delete(string $query)
    {
        return $this->pdo->exec($query);
    }
}
