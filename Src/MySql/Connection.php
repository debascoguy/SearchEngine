<?php

/**
 * Created By: Ademola Aina
 * Email: debascoguy@gmail.com
 */
class SearchEngine_Src_MySql_Connection {

    const CONNECTION_TYPE_MYSQLI_OBJECT = 0;
    const CONNECTION_TYPE_MYSQLI_PROCEDURAL = 1;

    /**
     * @var mysqli
     */
    private $connection;

    /**
     * @var null|mysqli_result
     */
    private $result = null;

    /**
     * @var SearchEngine_Src_MySql_Connection
     */
    protected static $instance;

    /**
     * @param SearchEngine_Src_MySql_ConnectionProperty $connectionProperty
     * @param int $conn_type
     * @return SearchEngine_Src_MySql_Connection
     */
    public static function getInstance(SearchEngine_Src_MySql_ConnectionProperty $connectionProperty, $conn_type = SearchEngine_Src_MySql_Connection::CONNECTION_TYPE_MYSQLI_OBJECT)
    {
        if (self::$instance==null){
            self::$instance = new self($connectionProperty, $conn_type);
        }
        return self::$instance;
    }

    /**
     * @param mysqli $connection
     * @return SearchEngine_Src_MySql_Connection
     */
    public static function create(mysqli $connection)
    {
        $self = new self(null, 0);
        $self->setConnection($connection);
        return $self;
    }

    /**
     * @param SearchEngine_Src_MySql_ConnectionProperty $connectionProperty
     * @param int $conn_type
     */
    public function __construct(SearchEngine_Src_MySql_ConnectionProperty $connectionProperty, $conn_type = 0)
    {
        if ($connectionProperty instanceof SearchEngine_Src_MySql_ConnectionProperty){
            $this->connect($connectionProperty, $conn_type);
        }
    }

    /**
     * @param SearchEngine_Src_MySql_ConnectionProperty $connectionProperty
     * @param int $conn_type
     */
    public function connect(SearchEngine_Src_MySql_ConnectionProperty $connectionProperty, $conn_type = 1)
    {
        if ($conn_type==1)
        {   //Procedural SearchEngine_Services_Connection.
            /** @var $this->connection mysqli */
            $this->connection = mysqli_connect(
                $connectionProperty->getHost(),
                $connectionProperty->getUser(),
                $connectionProperty->getPassword(),
                $connectionProperty->getDatabase(),
                $connectionProperty->getPort(),
                $connectionProperty->getSocket()
            );
            /* check connection */
            if (mysqli_connect_errno()) {
                $this->sqlErrorHandler(sprintf("Connect failed: %s\n", mysqli_connect_error()));
                exit();
            }
        }
        else
        {   //Object Oriented SearchEngine_Services_Connection
            /** @var $this->connection mysqli */
            $this->connection = new mysqli(
                $connectionProperty->getHost(),
                $connectionProperty->getUser(),
                $connectionProperty->getPassword(),
                $connectionProperty->getDatabase(),
                $connectionProperty->getPort(),
                $connectionProperty->getSocket()
            );
            // Check connection
            if ($this->connection->connect_error) {
                $this->sqlErrorHandler("Connection failed: " . $this->connection->connect_error);
            }
        }
    }

    /**
     * @param $result
     * @return bool
     */
    public function isMysqliResult($result)
    {
        return ($result instanceof mysqli_result);
    }

    /**
     * @param $connection
     * @return bool
     */
    public function isConnection($connection)
    {
        return ($connection instanceof mysqli);
    }

    /**
     * @param string $string
     * @return string
     */
    public function escapeString($string = "")
    {
        $string  = trim($string);
        return trim(mysqli_real_escape_string($this->connection, $string));
    }

    /**
     * @param $connection
     * @param string $string
     * @return string
     */
    public function escapeStringWithConnection($connection, $string = "")
    {
        $string  = trim($string);
        return trim(mysqli_real_escape_string($connection, $string));
    }

    /**
     * @close SearchEngine_Services_Connection
     */
    public function close()
    {
        $this->connection->close();
    }

    /**
     * @param $sql
     * @param array $params
     * @return bool|mysqli_result
     */
    public function executeQuery($sql, $params = array())
    {
        if (empty($params)){
            return $this->connection->query($sql);
        }
        else{
            return $this->query($sql, $params);
        }
    }

    /**
     * @param string $query
     * @param array|mysqli $binds
     * @return bool|mysqli_result
     * @throws Exception
     */
    public function query($query, $binds = array())
    {
        $query = trim($query);
        if ($this->isConnection($binds)) {
            $this->connection = $binds;
            $binds = array();
        } elseif (!$this->isConnection($this->connection)) {
            $this->connection = $GLOBALS['connection'];
        }
        $binds = (array)$binds;

        if (!$this->isConnection($this->connection)) {
            return false;
        }


        $queryResult = false;
        $mysqlnd = function_exists("mysqli_fetch_all");
        if (version_compare(PHP_VERSION, '5.3.0') >= 0 && $mysqlnd) {
            $stmt = $this->connection->prepare($query);
            if (!$stmt) {
                db_error($this->connection, $query);
                return false;
            }
            $types = '';
            foreach ($binds as &$bind) {
                switch (gettype($bind)) {
                    case 'boolean':
                        $bind = $bind ? 1 : 0;
                        $types .= 'i';
                        break;
                    case 'double':
                        $types .= 'd';
                        break;
                    case 'integer':
                        $types .= 'i';
                        break;
                    case 'string':
                    default:
                        $types .= 's';
                }
            }
            if (!empty($binds)) {
                $params = array();
                $params[0] = $types;
                foreach ($binds as &$v) {
                    $params[] = &$v;
                }
                call_user_func_array(array($stmt, 'bind_param'), $params);
            }
            if ($stmt->execute()) {
                if ($stmt->affected_rows >= 0) {
                    $queryResult = true;
                } else {
                    $queryResult = $stmt->get_result();
                }
            }
        }
        else {
            if (!empty($binds)) {
                $query = $this->bind_prepare($query, $binds);
            }
            $queryResult = $this->connection->query($query);
        }

        if ($queryResult == false) {
            $this->error($query);
        }

        $this->result = $queryResult;
        return $queryResult;
    }

    /**
     * @param string $sql
     * @param array $bind
     * @return string
     * @throws Exception
     */
    public function bind_prepare($sql, array $bind)
    {
        if (!$this->isConnection($this->connection)) {
            $this->connection = $GLOBALS['connection'];
        }

        $indexParams = array();
        foreach ($bind as $name => $value) {
            $isString = is_string($value);
            $isNull = $value === null;

            if ($isString) {
                $value = sprintf("'%s'", $value);
            } elseif ($isNull) {
                $value = "NULL";
            }
            if (!is_numeric($name)) {
                // For mysqli compatibility.
                throw new Exception(sprintf('Invalid parameter: named parameters are not supported in "%s"', $sql));
            }
            $indexParams[] = $value;
        }

        $escaped = false;
        $inSingleQuotes = false;
        $inDoubleQuotes = false;
        for ($i = 0; $i < strlen($sql); $i++) {
            if ($escaped) {
                $escaped = false;
                continue;
            }

            if (!$inDoubleQuotes && $sql[$i] == "'") {
                $inSingleQuotes = $inSingleQuotes ? false : true;
                continue;
            }

            if (!$inSingleQuotes && $sql[$i] == '"') {
                $inDoubleQuotes = $inDoubleQuotes ? false : true;
                continue;
            }

            if ($sql[$i] == '\\') {
                $escaped = true;
                continue;
            }

            if ($inSingleQuotes || $inDoubleQuotes) {
                continue;
            }

            if ($sql[$i] == '?') {
                if (empty($indexParams)) {
                    throw new Exception('Not enough bound parameters for query: '.$sql);
                }
                $replacement = array_shift($indexParams);
                $sql = substr_replace($sql, $replacement, $i, 1);
                $i += strlen($replacement);
                continue;
            }
        }
        return $sql;
    }

    /**
     * Returns the last SQL error from the DB, if it exists
     * @param string $query
     * @return string
     */
    public function error($query = '')
    {
        $this->connection = $this->isConnection($this->connection) ? $this->connection : $GLOBALS['connection'];

        $error = $this->connection->error;
        if ($error == '') {
            return '';
        }

        if ($GLOBALS['DISPLAY_DB_ERRORS'] || $GLOBALS['LOG_DB_ERRORS']) {
            $backtrace = debug_backtrace();
            $msg = "<b>Error in file:</b> {$backtrace[1]['file']}  <b>[Line #:{$backtrace[1]['line']}]</b>\n"
                . "<b>Function:</b> {$backtrace[1]['function']}\n<b>{$error}</b>";
            db_log_sql_error($msg);
            if ($GLOBALS['DISPLAY_DB_ERRORS']) {
                if ($query) {
                    echo trim(htmlspecialchars(mb_convert_encoding($query, 'UTF-8', 'UTF-8'), ENT_QUOTES, 'UTF-8')) . '<br>';
                }
                echo nl2br($msg) . '<br>';
            }
        }

        return $GLOBALS['DISPLAY_DB_ERRORS'] ? $error : '';
    }

    /**
     * @return int|string
     */
    public function getLastInsertId()
    {
        return $this->connection->insert_id;
    }

    /**
     * @return int
     */
    public function getNumberOfAffectedRows()
    {
        return $this->connection->affected_rows;
    }

    /**
     * @return string
     */
    public function mysqli_error()
    {
        return $this->connection->error;
    }

    /**
     * @return mysqli
     */
    public function getConnection()
    {
        return $this->connection;
    }

    /**
     * @param mysqli $connection
     * @return SearchEngine_Src_MySql_Connection
     */
    public function setConnection($connection)
    {
        $this->connection = $connection;
        return $this;
    }

    /**
     * @return mysqli_result|null
     */
    public function getResult()
    {
        return $this->result;
    }

    /**
     * @param mysqli_result|null $result
     * @return ElementMvc_DataFactory_MySql_Connection
     */
    public function setResult($result)
    {
        $this->result = $result;
        return $this;
    }

    /**
     * @param string $msg
     */
    public function sqlErrorHandler($msg)
    {
        if (!$GLOBALS['LOG_DB_ERRORS']) {
            return;
        }
        $msg = strip_tags($msg);
        $msg .= "\nRemote IP: " . $_SERVER['REMOTE_ADDR'] ."\n";
        if ($GLOBALS['mysql_log_file']) {
            file_put_contents($GLOBALS['mysql_log_file'], $msg, FILE_APPEND);
        } else {
            trigger_error('SQLERR: ' . strip_tags($msg), E_USER_WARNING);
        }
    }

}
