<?php

/**
 * Created by PhpStorm.
 * User: element
 * Date: 2/7/2018
 * Time: 11:59 PM
 */
class SearchEngine_Src_MySql_SearchQuery implements
    SearchEngine_Interface_MysqlSearchQuery
{
    /**
     * @var SearchEngine_Src_MySql_QueryBuilder
     */
    private $QueryBuilder;

    /**
     * SearchEngine_Src_MySql_SearchQuery constructor.
     * @param SearchEngine_Src_MySql_QueryBuilder $QueryBuilder
     */
    public function __construct(SearchEngine_Src_MySql_QueryBuilder $QueryBuilder)
    {
        $this->QueryBuilder = $QueryBuilder;
    }

    /**
     * @return SearchEngine_Src_MySql_QueryBuilder
     */
    public function getQueryBuilder()
    {
        return $this->QueryBuilder;
    }

    /**
     * @param SearchEngine_Src_MySql_QueryBuilder $QueryBuilder
     * @return SearchEngine_Src_MySql_SearchQuery
     */
    public function setQueryBuilder($QueryBuilder)
    {
        $this->QueryBuilder = $QueryBuilder;
        return $this;
    }

    /**
     * @return string
     */
    public function generateFullTextSearchQuery()
    {
        $QueryBuilder = $this->getQueryBuilder();

        /** @var SearchEngine_Src_SentenceAnalyzer_MysqlFullText $sentenceAnalyzer */
        $sentenceAnalyzer = $QueryBuilder->getSentenceAnalyzer();
        $searchStringExpression = (string)$sentenceAnalyzer;
        $fullTextIndexedColumns = $sentenceAnalyzer->getFullTextIndexedColumns();

        $subQuery = $this->generateSearchSubQuery(true, $fullTextIndexedColumns);

        $searchMode = $sentenceAnalyzer->getSearchMode();
        $whereClause = "MATCH (" . implode(", ", $fullTextIndexedColumns) . ") AGAINST ('$searchStringExpression' $searchMode) ";
        $relevanceColumn = ($whereClause." AS relevance ");


        $Query = " SELECT DISTINCT t.*, $relevanceColumn ";
        $Query .= " FROM ($subQuery) AS t ";
        foreach($QueryBuilder->getTableList() as $table)
        {
            $match = array();
            foreach($fullTextIndexedColumns as $tableDotFieldName){
                $temp = explode(".", $tableDotFieldName);
                if ($table==$temp[0]){
                    $match[] = " t.$temp[1] = $tableDotFieldName ";
                }
            }
            if (count($match) > 0) {
                $Query .= "LEFT JOIN $table ON ".implode(" AND ", $match);
            }
        }
        $Query .= " WHERE $whereClause ";
        $appendToMasterQuery = $QueryBuilder->getAppendToMasterQuery();
        if (!empty($appendToMasterQuery))
        {
            $Query .= $appendToMasterQuery;
        }
        if (stripos($Query, "ORDER BY")!==false)
        {
            $Query .= "ORDER BY relevance DESC";
        }
        $limit = $QueryBuilder->getLimit();
        if (is_int($limit) && $limit > 0 && stripos($Query, "LIMIT")!==false)
        {
            $Query .= " LIMIT ".$limit;
        }

        return $Query;
    }

    /**
     * @param bool|false $isFulltextSearch
     * @param array $fulltextIndexedColumn
     * @return mixed
     */
    public function generateSearchSubQuery($isFulltextSearch = false, array $fulltextIndexedColumn = array())
    {
        $QueryBuilder = $this->getQueryBuilder();

        $tableColumnsNeeded = $QueryBuilder->getColumns();
        $realFieldNames = $QueryBuilder->getFieldList();
        $excludeColumnsFromSearch = $QueryBuilder->getExcludeColumnsFromSearch();
        $columnIn = $columnNotIn = "";

        if ($isFulltextSearch)
        {
            $QueryBuilder->setMasterColumn("fulltext_columns");
            if (count($tableColumnsNeeded) > 0) {
                $query = "  SELECT CONCAT( 'SELECT ".implode(", ", $tableColumnsNeeded)
                    .", CONCAT(".implode(", \" \", ", $fulltextIndexedColumn).") AS {$QueryBuilder->getMasterColumn()} "
                    ." FROM ".$QueryBuilder->getTableName();
            }
            else{
                $query = " SELECT CONCAT('SELECT *, CONCAT(".implode(", \" \", ", $fulltextIndexedColumn).") AS {$QueryBuilder->getMasterColumn()} "
                    ." FROM " . $QueryBuilder->getTableName();
            }
        }
        else
        {
            $QueryBuilder->setMasterColumn("all_columns");
            if (count($tableColumnsNeeded) > 0) {
                $query = " SELECT CONCAT( 'SELECT " . implode(", ", $tableColumnsNeeded) . ",
                                    CONCAT_WS(\' \', ',
                                    GROUP_CONCAT(CONCAT(TABLE_NAME, '.', COLUMN_NAME)), ') AS {$QueryBuilder->getMasterColumn()}
                                    FROM " . $QueryBuilder->getTableName();

                $columnIn = " AND column_name IN ('" . implode("', '", $QueryBuilder->getFieldNamesWithoutTableName($realFieldNames)) . "') ";
            }
            else {
                $query = " SELECT CONCAT( 'SELECT *, " . "
                                    CONCAT_WS(\' \', ',
                                    GROUP_CONCAT(CONCAT(TABLE_NAME, '.', COLUMN_NAME)), ') AS {$QueryBuilder->getMasterColumn()}
                                    FROM " . $QueryBuilder->getTableName();

                $columnIn = "";
            }
        }


        if (count($excludeColumnsFromSearch) > 0)
        {
            $columnNotIn = " AND column_name NOT IN ('" . implode("', '", $QueryBuilder->getFieldNamesWithoutTableName($excludeColumnsFromSearch)) . "') ";
        }

        $joins = $QueryBuilder->getJoins();
        if (count($joins) > 0)
        {
            $query .= " ".implode(" ", $joins);
        }

        $where = $QueryBuilder->getWhere();
        if (count($where) > 0)
        {
            $query .= " WHERE " . implode(" ", $where) . " ";
        }

        $append = $QueryBuilder->getAppendToQuery();
        if (!empty($append))
        {
            $query .= " ".addslashes($append);
        }

        $orderBy = $QueryBuilder->getOrderBy();
        if (!empty($orderBy) && stripos($query, "ORDER BY")!==false)
        {
            $query .= " ".$orderBy;
        }

        $query .= "' ) AS query FROM `information_schema`.`columns`
                    WHERE  `table_schema`= DATABASE() ";
        $tableList = $QueryBuilder->getTableList();
        if (count($tableList) > 0)
        {
            $tbParts = array();
            foreach ($tableList as $table) {
                $tbParts[] = " `table_name` = '$table' ";
            }
            $query .= " AND (".implode(" OR ", $tbParts).") ";
        }

        $query .= $columnIn;
        $query .= $columnNotIn;

        $result = $QueryBuilder->getConnection()->executeQuery($query);
        $data = $result->fetch_assoc();
        $generatedQuery = $data["query"];

        $union = $QueryBuilder->getUnion();
        if (count($union) > 0)
        {
            foreach($union as $unionType => $QBs)
            {
                /** @var SearchEngine_Src_MySql_QueryBuilder $QB */
                foreach($QBs as $QB)
                {
                    $QB->setConnection($QueryBuilder->getConnection());
                    $QB->setSentenceAnalyzer($QueryBuilder->getSentenceAnalyzer());
                    $searchQuery = new SearchEngine_Src_MySql_SearchQuery($QB);
                    $generatedQuery .= " $unionType " . $searchQuery->generateSearchSubQuery($isFulltextSearch, $fulltextIndexedColumn);
                }
            }
        }

        return $generatedQuery;
    }

    /**
     * @return string
     */
    public function generateMysqlLikeOrRegexSearchQuery()
    {
        $QueryBuilder = $this->getQueryBuilder();
        $sentenceAnalyzer = $QueryBuilder->getSentenceAnalyzer();
        $searchStringExpression = (string)$sentenceAnalyzer;
        $query = $this->generateSearchSubQuery();
        $appendToMasterQuery = $QueryBuilder->getAppendToMasterQuery();
        return $this->getStandardQuery($query, $searchStringExpression, $appendToMasterQuery);
    }

    /**
     * @param string $subQuery
     * @param $searchStringExpression
     * @param string $appendToMasterQuery
     * @return string
     */
    public function getStandardQuery($subQuery = "", $searchStringExpression, $appendToMasterQuery = "")
    {
        $QueryBuilder = $this->getQueryBuilder();
        
        if (empty($subQuery)) {
            $subQuery = $this->generateSearchSubQuery();
        }

        $field = "t.all_columns";
        $WhereClause = $QueryBuilder->getSentenceAnalyzer()->finalizeExpression($field, $searchStringExpression);
        $Query = " SELECT DISTINCT * ";
        $Query .= " FROM ($subQuery) AS t WHERE $WhereClause ";
        if (!empty($appendToMasterQuery))
        {
            $Query .= " AND " . $appendToMasterQuery;
        }
        $limit = $QueryBuilder->getLimit();
        if (is_int($limit) && $limit > 0 && stripos($Query, "LIMIT")!==false)
        {
            $Query .= " LIMIT ".$limit;
        }

        return $Query;
    }

    /**
     * @return array
     */
    public function getTableColumns()
    {
        $QueryBuilder = $this->getQueryBuilder();

        $query = " select * from information_schema.columns where table_schema = DATABASE() ";
        $query .= " AND `table_name` = '".$QueryBuilder->getTableName()."' ";
        $query .= " order by table_name ";
        $result =  $QueryBuilder->getConnection()->executeQuery($query);
        $columnNames = array();
        while ($row = $result->fetch_assoc()) {
            $columnNames[] = $row["TABLE_NAME"] . "." . $row["COLUMN_NAME"];
        }
        return $columnNames;
    }

}