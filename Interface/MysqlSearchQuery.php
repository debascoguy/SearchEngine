<?php

/**
 * Created by PhpStorm.
 * User: element
 * Date: 1/30/2018
 * Time: 10:32 AM
 */
interface SearchEngine_Interface_MysqlSearchQuery
{
    /**
     * @return SearchEngine_Src_MySql_QueryBuilder
     */
    public function getQueryBuilder();

    /**
     * @param SearchEngine_Src_MySql_QueryBuilder $QueryBuilder
     * @return $this
     */
    public function setQueryBuilder($QueryBuilder);

    /**
     * @return string
     */
    public function generateFullTextSearchQuery();

    /**
     * @param bool|false $isFulltextSearch
     * @param array $fulltextIndexedColumn
     * @return mixed
     */
    public function generateSearchSubQuery($isFulltextSearch = false, array $fulltextIndexedColumn = array());

    /**
     * @return string
     */
    public function generateMysqlLikeOrRegexSearchQuery();

    /**
     * @param string $subQuery
     * @param $searchStringExpression
     * @param string $appendToMasterQuery
     * @return string
     */
    public function getStandardQuery($subQuery = "", $searchStringExpression, $appendToMasterQuery = "");

}