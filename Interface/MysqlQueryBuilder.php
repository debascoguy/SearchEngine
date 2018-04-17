<?php

/**
 * Created by PhpStorm.
 * User: element
 * Date: 1/30/2018
 * Time: 10:32 AM
 */
interface SearchEngine_Interface_MysqlQueryBuilder
{
    /**
     * @return array
     */
    public function getExcludeColumnsFromSearch();

    /**
     * @param array $excludeColumnsFromSearch
     * @return $this
     */
    public function setExcludeColumnsFromSearch(array $excludeColumnsFromSearch = array());

    /**
     * @param $excludeColumn
     * @return $this
     */
    public function excludeColumnFromSearch($excludeColumn = "table_name.column_name");

    /**
     * @param $string
     * @return $this
     */
    public function appendToSubQuery($string);

    /**
     * @return string
     */
    public function getAppendToMasterQuery();

    /**
     * @param string $appendToMasterQuery
     * @return $this
     */
    public function setAppendToMasterQuery($appendToMasterQuery);

    /**
     * @param $string
     * @return $this
     */
    public function appendToMasterQuery($string);

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
     * @return SearchEngine_Src_SentenceAnalyzer_SentenceAnalyzer
     */
    public function getSentenceAnalyzer();

    /**
     * @param SearchEngine_Src_SentenceAnalyzer_SentenceAnalyzer $sentenceAnalyzer
     * @return $this
     */
    public function setSentenceAnalyzer($sentenceAnalyzer);

    /**
     * @return string
     */
    public function getMasterColumn();
    /**
     * @param string $rankingFactor
     * @return $this
     */
    public function setMasterColumn($rankingFactor);

    /**
     * @return string
     */
    public function generateFullTextSearchQuery();

    /**
     * @return string
     */
    public function generateMysqlLikeOrRegexSearchQuery();

}