<?php

namespace SearchEngine\Src\SQL;

class PDOConnection {

    /**
     * @var int the current transaction depth
     */
    protected $transactionLevel = 0;

    /**
     * @var \PDO
     */
    private $connection;

    /**
     * @var int
     */
    private $numberOfAffectedRows = 0;

    /**
     * @var \PDOStatement
     */
    private $statement = null;

    /**
     * @var self
     */
    protected static $instance;

    /**
     * @var ConnectionProperty
     */
    private $connectionProperty;

    /**
     * @param ConnectionProperty|null $connectionProperty
     * @return PDOConnection : Active instance of Connection OR create a new one and return it.
     */
    public static function getInstance(ConnectionProperty $connectionProperty = null) {
        if (self::$instance == null) {
            self::$instance = new self();
            self::$instance->connect($connectionProperty);
        }
        return self::$instance;
    }

    /**
     * Test if database driver support savepoints
     *
     * @return bool
     */
    protected function canSavepoint() {
        return in_array($this->getConnection()->getAttribute(\PDO::ATTR_DRIVER_NAME), DBMS::$supportedDrivers);
    }

    /**
     * @return int
     * Only for supported drivers
     */
    public function savepoint() {
        return $this->getConnection()->exec("SAVEPOINT LEVEL{$this->transactionLevel}");
    }

    /**
     * @return int
     * Only for supported drivers
     */
    public function release_savepoint() {
        return $this->getConnection()->exec("RELEASE SAVEPOINT LEVEL{$this->transactionLevel}");
    }

    /**
     * @return bool
     */
    public function beginTransaction() {
        if (!$this->getConnection()->inTransaction()) {
            $status = ($this->transactionLevel == 0 || !$this->canSavepoint()) ?
                    $this->getConnection()->beginTransaction() :
                    $this->savepoint();
            $this->transactionLevel++;
            return $status;
        }
        return true;
    }

    /**
     * @return bool|int
     */
    public function commit() {
        $this->transactionLevel--;
        return ($this->transactionLevel == 0 || !$this->canSavepoint()) ?
                $this->getConnection()->commit() :
                $this->release_savepoint();
    }

    /**
     * @return bool|int
     */
    public function rollback() {
        if ($this->transactionLevel == 0) {
            throw new \PDOException('Rollback error : There is no transaction started');
        }

        $this->transactionLevel--;
        return ($this->transactionLevel == 0 || !$this->canSavepoint()) ?
                $this->getConnection()->rollBack() :
                $this->getConnection()->exec("ROLLBACK TO SAVEPOINT LEVEL{$this->transactionLevel}");
    }

    /**
     * @return int
     */
    public function getTransactionLevel() {
        return $this->transactionLevel;
    }

    /**
     * @param ConnectionProperty $connectionProperty
     * @return $this
     */
    public function connect(ConnectionProperty $connectionProperty) {
        $this->setConnectionProperty($connectionProperty);
        $dsn = empty($connectionProperty->getDsn()) ? "{dbms}:host={host};port={port};dbname={db}" : $connectionProperty->getDsn();
        $connectionString = str_replace([
                "{dbms}", 
                "{host}", 
                "{port}", 
                "{db}"
            ], [
                $connectionProperty->getDbms(),
                $connectionProperty->getHost(),
                $connectionProperty->getPort(),
                $connectionProperty->getDatabase()
            ], 
            (string) $dsn
        );

        $this->connection = new \PDO($connectionString, $connectionProperty->getUser(), $connectionProperty->getPassword());
        // set the PDO error mode to exception
        $this->connection->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);
        if (version_compare(PHP_VERSION, '5.3.6', '>=')) {
            //Setting the connection character set to UTF-8 prior to PHP 5.3.6
            $this->connection->setAttribute(
                    \PDO::MYSQL_ATTR_INIT_COMMAND, 'SET NAMES ' . str_replace(array("-", "_"), "", strtolower("UTF-8"))
            );
        }
        return $this;
    }

    /**
     * @close Connection
     */
    public function close() {
        $this->connection = null;
    }

    /**
     * @param $sql
     * @param array $params
     * @param int $fetchMode
     * @return \PDOStatement
     */
    public function executeQuery($sql, $params = array(), $fetchMode = \PDO::FETCH_ASSOC) {
        if (is_null($fetchMode) || !is_int($fetchMode)) {
            $fetchMode = \PDO::FETCH_ASSOC;
        }

        try {
            if (empty($params)) {
                $this->statement = $this->connection->query($sql, $fetchMode);
            } else {
                $this->statement = strpos($sql, "?") !== false ?
                        /** SQL statement template with question mark parameters */
                        $this->connection->prepare($sql) :
                        /** SQL statement template with named parameters */
                        $this->connection->prepare($sql, array(\PDO::ATTR_CURSOR => \PDO::CURSOR_FWDONLY));
                $this->statement->execute($params);
            }
            $this->statement->setFetchMode($fetchMode);
            $this->numberOfAffectedRows = $this->statement->rowCount();
            return $this->statement;
        } catch (\PDOException $e) {
            if (!is_null($this->statement)) {
                $message = "PDO Statement Error Code: " . $this->statement->errorCode() . PHP_EOL;
                $message .= "PDO Statement Error Message: " . json_encode($this->statement->errorInfo()) . PHP_EOL;
            }
            $message .= "Exception Code: " . $e->getCode() . PHP_EOL;
            $message .= "Exception File: " . $e->getFile() . PHP_EOL;
            $message .= "Exception Line: " . $e->getLine() . PHP_EOL;
            $message .= "Exception Message: " . $e->getMessage() . PHP_EOL;
            $this->error($sql, $message);
            return false;
        }
    }

    /**
     * @return string
     */
    public function getLastInsertID() {
        return $this->connection->lastInsertId();
    }

    /**
     * @param int $resultType
     * @return bool
     */
    public function setFetchMode($resultType = \PDO::FETCH_ASSOC) {
        if ($this->isPDOStatement($this->statement)) {
            return $this->statement->setFetchMode($resultType);
        } else {
            self::logError("Error: No Result Found for Fetch Array");
            return false;
        }
    }

    /**
     * @return bool
     */
    public function setFetchModeAsAssoc() {
        return $this->setFetchMode();
    }

    /**
     * @return \PDO
     */
    public function getConnection() {
        return $this->connection;
    }

    /**
     * @param \PDO $connection
     * @return PDOConnection
     */
    public function setConnection($connection) {
        $this->connection = $connection;
        return $this;
    }

    /**
     * @return int
     */
    public function getNumberOfAffectedRows() {
        return $this->numberOfAffectedRows;
    }

    /**
     * @param int $numberOfAffectedRows
     * @return PDOConnection
     */
    public function setNumberOfAffectedRows($numberOfAffectedRows) {
        $this->numberOfAffectedRows = $numberOfAffectedRows;
        return $this;
    }

    /**
     * @return \PDOStatement
     */
    public function getStatement() {
        return $this->statement;
    }

    /**
     * @param \PDOStatement $statement
     * @return PDOConnection
     */
    public function setStatement($statement) {
        $this->statement = $statement;
        return $this;
    }

    /**
     * @param $result
     * @return bool
     */
    public function isPDOStatement($result) {
        return ($result instanceof \PDOStatement);
    }

    /**
     * @param $connection
     * @return bool
     */
    public function isConnection($connection) {
        return ($connection instanceof \PDO);
    }

    /**
     * @param string $query
     * @param string $error
     * @return string
     */
    public function error($query = '', $error = "") {
        if ($query) {
            echo $query . '<br>';
        }

        $msg = "QUERY: \n$query\nERROR MESSAGE: \n" . $error . "\nTRACE:\n==============\n";
        $backtrace = debug_backtrace();
        $msg .= "<b>Error in file:</b> {$backtrace[1]['file']}  <b>[Line #:{$backtrace[1]['line']}]</b>\n"
                . "<b>Function:</b> {$backtrace[1]['function']}\n<b>{$error}</b>";
        echo "<pre>" . nl2br($msg) . '</pre><br>';
        self::logError($msg);
        return $error;
    }

    /**
     * @param string $msg
     */
    public static function logError($msg) {
        $msg = strip_tags($msg);
        $msg .= "\nRemote IP: " . $_SERVER['REMOTE_ADDR'] . "\n";
        trigger_error('SQL ERROR: ' . strip_tags($msg), 0); //Will invoke the framework ErrorHandler.
    }

    /**
     * @return  ConnectionProperty
     */ 
    public function getConnectionProperty()
    {
        return $this->connectionProperty;
    }

    /**
     * @param  ConnectionProperty  $connectionProperty
     * @return  self
     */ 
    public function setConnectionProperty(ConnectionProperty $connectionProperty)
    {
        $this->connectionProperty = $connectionProperty;
        return $this;
    }
}
