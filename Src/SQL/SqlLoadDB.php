<?php

namespace SearchEngine\Src\SQL;

use SearchEngine\Interfaces\Search;
use SearchEngine\Interfaces\SqlLoadDB as InterfacesSqlLoadDB;
use SearchEngine\Services\CallBackHandler;
use SearchEngine\Src\SentenceAnalyzer\SentenceAnalyzer;

/**
 * @Author: Ademola Aina
 * Email: debascoguy@gmail.com
 * Date: 1/29/2018
 * Time: 11:32 PM
 */
class SqlLoadDB implements InterfacesSqlLoadDB, Search, \Countable
{
    /**
     * @var PDOConnection
     */
    private $connection;

    /**
     * @var QueryBuilder
     */
    private $QueryBuilder;

    /**
     * @var SentenceAnalyzer
     */
    private $sentenceAnalyzer;

    /**
     * @var array
     */
    private $result = array();

    /**
     * @var CallBackHandler[]|array
     */
    private $resultCallBacks = null;

    /**
     * @param PDOConnection|null $connection
     * @param QueryBuilder|null $QueryBuilder
     * @param SentenceAnalyzer|null $sentenceAnalyzer
     */
    public function __construct(PDOConnection $connection = null,
                                QueryBuilder $QueryBuilder = null,
                                SentenceAnalyzer $sentenceAnalyzer = null

    )
    {
        $this->connection = $connection;
        $this->QueryBuilder = $QueryBuilder;
        $this->sentenceAnalyzer = $sentenceAnalyzer;
    }

    /**
     * @return array
     */
    public function search()
    {
        $PDOConnection = $this->getConnection();
        $sentenceAnalyzer = $this->getSentenceAnalyzer();

        $QueryBuilder = $this->getQueryBuilder();
        $QueryBuilder->setConnection($PDOConnection);
        $QueryBuilder->setSentenceAnalyzer($sentenceAnalyzer);

        $query = $QueryBuilder->__toString();
        $result = $PDOConnection->executeQuery($query);
        if ($result != false) {
            if (!empty($this->resultCallBacks)) {
                $searchString = $sentenceAnalyzer->getOriginalSearchString();
                $rankingColumn = $QueryBuilder->getMasterColumn();
                while ($row = $result->fetch()) {
                    $row = $this->handleCallBacks($row, $rankingColumn, $searchString);
                    $this->result[] = $row;
                }
            } else {
                while ($row = $result->fetch()) {
                    $this->result[] = $row;
                }
            }
        }
        return $this;
    }

    /**
     * @param $row
     * @param $masterColumnName
     * @param $searchString
     * @return mixed
     */
    public function handleCallBacks($row, $masterColumnName, $searchString)
    {
        if (!empty($row)) {
            foreach ($this->resultCallBacks as $callable) {
                $arguments = array_merge(array($row, $masterColumnName, $searchString), (array)$callable->getMetadata());
                $row = $callable->call($arguments);
            }
        }
        return $row;
    }

    /**
     * @param callback $callable [Also, Accepts Anonymous function]
     * @param array $arguments
     * @return $this
     */
    public function registerResultCallBack($callable, $arguments = array())
    {
        $this->resultCallBacks[] = new CallBackHandler($callable, $arguments);
        return $this;
    }

    /**
     * @return array|CallBackHandler[]
     */
    public function getResultCallBacks()
    {
        return $this->resultCallBacks;
    }

    /**
     * @return $this
     */
    public function resetCallBacks()
    {
        $this->resultCallBacks = array();
        return $this;
    }

    /**
     * @return array
     */
    public function getResult()
    {
        return $this->result;
    }

    /**
     * @param array $result
     * @return SqlLoadDB
     */
    public function setResult($result)
    {
        $this->result = $result;
        return $this;
    }

    /**
     * @return PDOConnection
     */
    public function getConnection()
    {
        return $this->connection;
    }

    /**
     * @param PDOConnection $connection
     * @return $this
     */
    public function setConnection($connection)
    {
        $this->connection = $connection;
        return $this;
    }

    /**
     * @return QueryBuilder
     */
    public function getQueryBuilder()
    {
        return $this->QueryBuilder;
    }

    /**
     * @param QueryBuilder $QueryBuilder
     * @return SqlLoadDB
     */
    public function setQueryBuilder($QueryBuilder)
    {
        $this->QueryBuilder = $QueryBuilder;
        return $this;
    }

    /**
     * @return SentenceAnalyzer
     */
    public function getSentenceAnalyzer()
    {
        return $this->sentenceAnalyzer;
    }

    /**
     * @param $sentenceAnalyzer
     * @return $this
     */
    public function setSentenceAnalyzer($sentenceAnalyzer)
    {
        $this->sentenceAnalyzer = $sentenceAnalyzer;
        return $this;
    }

    /**
     * @return \ArrayIterator
     */
    public function getIterator() : \ArrayIterator
    {
        return new \ArrayIterator((array)$this->result);
    }

    /**
     * @return int
     */
    public function count() : int
    {
        return count($this->result);
    }

}