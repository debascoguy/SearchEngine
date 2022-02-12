<?php
namespace SearchEngine\Src\SQL;

class ConnectionProperty
{
    private $host = 'localhost';
    private $user = 'root';
    private $password = '';
    private $database = '';
    private $port = '3306';
    private $socket = '';
    private $dbms = DBMS::MYSQL; //There are other fully supported dbms inside the DBMS class...
    private $dsn = ""; //If connection failed, try specifying the PDO connection string using the 'dsn' field.


    /**
     * @param $host
     * @param $user
     * @param $password
     * @param $database
     * @param string $port
     * @param string $socket
     * @param string $dbms
     * @param string $dsn
     */
    public function __construct($host, $user, $password, $database, $port = '3306', $socket = '', $dbms = DBMS::MYSQL, $dsn = "")
    {
        $this->host = $host;
        $this->user = $user;
        $this->password = $password;
        $this->database = $database;
        $this->port = !empty($port) ? $port : 3306;
        $this->socket = !empty($socket) ? $socket : '';
        $this->dbms = !empty($dbms) ? $dbms : DBMS::MYSQL;
        $this->dsn = $dsn;
    }

    /**
     * @return string
     */
    public function getHost()
    {
        return $this->host;
    }

    /**
     * @param string $host
     * @return ConnectionProperty
     */
    public function setHost($host)
    {
        $this->host = $host;
        return $this;
    }

    /**
     * @return string
     */
    public function getUser()
    {
        return $this->user;
    }

    /**
     * @param string $user
     * @return ConnectionProperty
     */
    public function setUser($user)
    {
        $this->user = $user;
        return $this;
    }

    /**
     * @return string
     */
    public function getPassword()
    {
        return $this->password;
    }

    /**
     * @param string $password
     * @return ConnectionProperty
     */
    public function setPassword($password)
    {
        $this->password = $password;
        return $this;
    }

    /**
     * @return string
     */
    public function getDatabase()
    {
        return $this->database;
    }

    /**
     * @param string $database
     * @return ConnectionProperty
     */
    public function setDatabase($database)
    {
        $this->database = $database;
        return $this;
    }

    /**
     * @return string
     */
    public function getPort()
    {
        return $this->port;
    }

    /**
     * @param string $port
     * @return ConnectionProperty
     */
    public function setPort($port)
    {
        $this->port = $port;
        return $this;
    }

    /**
     * @return string
     */
    public function getSocket()
    {
        return $this->socket;
    }

    /**
     * @param string $socket
     * @return ConnectionProperty
     */
    public function setSocket($socket)
    {
        $this->socket = $socket;
        return $this;
    }

    /**
     * @return mixed
     */
    public function getDbms()
    {
        return $this->dbms;
    }

    /**
     * @param mixed $dbms
     * @return ConnectionProperty
     */
    public function setDbms($dbms)
    {
        $this->dbms = $dbms;
        return $this;
    }

    /**
     * @return string
     */
    public function getDsn()
    {
        return $this->dsn;
    }

    /**
     * @param string $dsn
     * @return ConnectionProperty
     */
    public function setDsn($dsn)
    {
        $this->dsn = $dsn;
        return $this;
    }
}