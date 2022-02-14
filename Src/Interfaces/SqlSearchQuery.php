<?php

namespace SearchEngine\Interfaces;
use SearchEngine\SQL\QueryBuilder;

interface SqlSearchQuery
{
    /**
     * @return QueryBuilder
     */
    public function getQueryBuilder();

    /**
     * @param QueryBuilder $QueryBuilder
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
    public function generateSqlLikeOrRegexSearchQuery();

    /**
     * @param string $subQuery
     * @param $searchStringExpression
     * @param string $appendToMasterQuery
     * @return string
     */
    public function getStandardQuery($subQuery = "", $searchStringExpression, $appendToMasterQuery = "");

}