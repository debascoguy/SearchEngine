<?php

/**
 * Created By: Ademola Aina
 * Email: debascoguy@gmail.com
 */
class SearchEngine_Src_MySql_ConnectionProperty
{
    private $host = 'localhost';
    private $user = 'root';
    private $password = '';
    private $database = '';
    private $port = '3306';
    private $socket = '';

    /**
     * @param $host
     * @param $user
     * @param $password
     * @param $database
     * @param string $port
     * @param string $socket
     */
    public function __construct($host, $user, $password, $database, $port='3306', $socket='')
    {
        $this->host 	= $host;
        $this->user 	= $user;
        $this->password = $password;
        $this->database = $database;
        $this->port 	= !empty($port) ? $port : '3306';
        $this->socket 	= !empty($socket) ? $socket : '';
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
     * @return ElementMvc_DataFactory_MySql_ConnectionProperty
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
     * @return ElementMvc_DataFactory_MySql_ConnectionProperty
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
     * @return ElementMvc_DataFactory_MySql_ConnectionProperty
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
     * @return ElementMvc_DataFactory_MySql_ConnectionProperty
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
     * @return ElementMvc_DataFactory_MySql_ConnectionProperty
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
     * @return ElementMvc_DataFactory_MySql_ConnectionProperty
     */
    public function setSocket($socket)
    {
        $this->socket = $socket;
        return $this;
    }

}