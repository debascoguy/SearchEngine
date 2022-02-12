<?php

namespace SearchEngine\Interfaces;
use SearchEngine\Src\SQL\PDOConnection;
use SearchEngine\Src\SQL\QueryBuilder;

interface SqlLoadDB
{
    /**
     * @return PDOConnection
     */
    public function getConnection();

    /**
     * @param PDOConnection $connection
     * @return $this
     */
    public function setConnection($connection);

    /**
     * @return QueryBuilder
     */
    public function getQueryBuilder();

    /**
     * @param QueryBuilder $QueryBuilder
     * @return SqlLoadDB
     */
    public function setQueryBuilder($QueryBuilder);

    /**
     * @return MysqlFullText|MysqlLike|MysqlRegex|sentenceAnalyzer
     */
    public function getSentenceAnalyzer();

    /**
     * @param null $sentenceAnalyzer
     */
    public function setSentenceAnalyzer($sentenceAnalyzer);

    /**
     * @return $this
     */
    public function resetCallBacks();

    /**
     * @return array|CallBackHandler[]
     */
    public function getResultCallBacks();

    /**
     * @param callback $callable [Also, Accepts Anonymous function]
     * @param array $arguments
     */
    public function registerResultCallBack($callable, $arguments = array());

    /**
     * @param $row
     * @param $masterColumnName
     * @param $searchString
     * @return mixed
     */
    public function handleCallBacks($row, $masterColumnName, $searchString);

}