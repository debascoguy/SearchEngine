<?php

/**
 * Created by PhpStorm.
 * User: element
 * Date: 2/2/2018
 * Time: 12:52 AM
 */
interface SearchEngine_Interface_MysqlLoadDB
{
    /**
     * @return ElementMvc_DataFactory_MySql_Connection
     */
    public function getConnection();

    /**
     * @param ElementMvc_DataFactory_MySql_Connection $connection
     * @return $this
     */
    public function setConnection($connection);

    /**
     * @return SearchEngine_Src_MySql_QueryBuilder
     */
    public function getQueryBuilder();

    /**
     * @param SearchEngine_Src_MySql_QueryBuilder $QueryBuilder
     * @return SearchEngine_Src_MySql_MySqlLoadDB
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
     * @return array|ElementMvc_ServiceManager_CallBackHandler[]
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