<?php

namespace SearchEngine\Interfaces;
use SearchEngine\SQL\PDOConnection;

interface SqlQueryBuilder
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
     * @return PDOConnection
     */
    public function getConnection();

    /**
     * @param PDOConnection $connection
     * @return $this
     */
    public function setConnection($connection);

    /**
     * @return SentenceAnalyzer
     */
    public function getSentenceAnalyzer();

    /**
     * @param SentenceAnalyzer $sentenceAnalyzer
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