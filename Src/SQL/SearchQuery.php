<?php

namespace SearchEngine\Src\SQL;

use SearchEngine\Interfaces\SqlSearchQuery;
use SearchEngine\Src\SentenceAnalyzer\MysqlFullText;

/**
 * @Author: Ademola Aina
 * Email: debascoguy@gmail.com
 * Date: 2/7/2018
 * Time: 11:59 PM
 */
class SearchQuery implements SqlSearchQuery
{
    /**
     * @var QueryBuilder
     */
    private $QueryBuilder;

    /**
     * SearchQuery constructor.
     * @param QueryBuilder $QueryBuilder
     */
    public function __construct(QueryBuilder $QueryBuilder)
    {
        $this->QueryBuilder = $QueryBuilder;
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
     * @return SearchQuery
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

        /** @var MysqlFullText $sentenceAnalyzer */
        $sentenceAnalyzer = $QueryBuilder->getSentenceAnalyzer();
        $searchStringExpression = (string)$sentenceAnalyzer;
        $fullTextIndexedColumns = $sentenceAnalyzer->getFullTextIndexedColumns();

        $subQuery = $this->generateSearchSubQuery(true, $fullTextIndexedColumns);

        $searchMode = $sentenceAnalyzer->getSearchMode();
        $whereClause = "MATCH (" . implode(", ", $fullTextIndexedColumns) . ") AGAINST ('$searchStringExpression' $searchMode) ";
        $relevanceColumn = ($whereClause . " AS relevance ");
        
        if (count($QueryBuilder->getTableList()) == 1) {
            $temp = explode(" FROM ", $subQuery);
            $Query = $temp[0] . ", " . $relevanceColumn . " FROM " . $temp[1];
        }
        else{
            $Query = " SELECT DISTINCT t.*, $relevanceColumn ";
            $Query .= " FROM ($subQuery) AS t ";
            foreach ($QueryBuilder->getTableList() as $table) {
                $match = array();
                foreach ($fullTextIndexedColumns as $tableDotFieldName) {
                    $temp = explode(".", $tableDotFieldName);
                    if ($table != $temp[0]) {
                        $match[] = " t.$temp[1] = $tableDotFieldName ";
                    }
                }
                if (count($match) > 0) {
                    $Query .= "LEFT JOIN $table ON " . implode(" AND ", $match);
                }
            }
        }

        $Query .= " WHERE $whereClause ";
        $appendToMasterQuery = $QueryBuilder->getAppendToMasterQuery();
        if (!empty($appendToMasterQuery)) {
            $Query .= $appendToMasterQuery;
        }
        if (stripos($Query, "ORDER BY") === false) {
            $Query .= "ORDER BY relevance DESC";
        }
        $limit = $QueryBuilder->getLimit();
        if (is_int($limit) && $limit > 0 && stripos($Query, "LIMIT") === false) {
            $Query .= " LIMIT $limit ";
            $endLimit = $QueryBuilder->getEndLimit();
            $offLimit = $QueryBuilder->getOffset();
            if (is_int($endLimit) && $endLimit > 0) {
                $Query .= ", $endLimit ";
            }
            if (is_int($offLimit) && $offLimit > 0) {
                $Query .= " OFFSET $offLimit ";
            }
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

        if ($isFulltextSearch) {
            $QueryBuilder->setMasterColumn("fulltext_columns");
            if (!empty($tableColumnsNeeded)) {
                $query = "  SELECT CONCAT( 'SELECT " . implode(", ", $tableColumnsNeeded)
                    . ", CONCAT(" . implode(", \" \", ", $fulltextIndexedColumn) . ") AS {$QueryBuilder->getMasterColumn()} "
                    . " FROM " . $QueryBuilder->getTableName();
            } else {
                $query = " SELECT CONCAT('SELECT *, CONCAT(" . implode(", \" \", ", $fulltextIndexedColumn) . ") AS {$QueryBuilder->getMasterColumn()} "
                    . " FROM " . $QueryBuilder->getTableName();
            }
        } else {
            $QueryBuilder->setMasterColumn("all_columns");
            if (!empty($tableColumnsNeeded)) {
                $query = " SELECT CONCAT( 'SELECT " . addslashes(implode(", ", $tableColumnsNeeded )) . ",
                                    CONCAT_WS(\' \', ',
                                    GROUP_CONCAT(CONCAT(TABLE_NAME, '.', COLUMN_NAME)), ') AS {$QueryBuilder->getMasterColumn()}
                                    FROM " . $QueryBuilder->getTableName();

                $columnIn = " AND column_name IN ('" . implode("', '", $QueryBuilder->getFieldNamesWithoutTableName($realFieldNames)) . "') ";
            } else {
                $query = " SELECT CONCAT( 'SELECT *, " . "
                                    CONCAT_WS(\' \', ',
                                    GROUP_CONCAT(CONCAT(TABLE_NAME, '.', COLUMN_NAME)), ') AS {$QueryBuilder->getMasterColumn()}
                                    FROM " . $QueryBuilder->getTableName();

                $columnIn = "";
            }
        }


        if (is_array($excludeColumnsFromSearch) && count($excludeColumnsFromSearch) > 0) {
            $columnNotIn = " AND column_name NOT IN ('" . implode("', '", $QueryBuilder->getFieldNamesWithoutTableName($excludeColumnsFromSearch)) . "') ";
        }

        $joins = $QueryBuilder->getJoins();
        if (!empty($joins)) {
            $query .= " " . implode(" ", $joins);
        }

        $where = $QueryBuilder->getWhere();
        if (!empty($where)) {
            $query .= " WHERE " . addslashes(implode(" ", $where)) . " "; 
        }

        $append = $QueryBuilder->getAppendToQuery();
        if (!empty($append)) {
            $query .= " " . addslashes($append);
        }

        $orderBy = $QueryBuilder->getOrderBy();
        if (!empty($orderBy) && stripos($query, "ORDER BY") === false) {
            $query .= " " . $orderBy;
        }

        $db = $QueryBuilder->getConnection()->getConnectionProperty()->getDatabase();
        $query .= "' ) AS query FROM `information_schema`.`columns`
                    WHERE  `table_schema`= '$db' ";
        $tableList = $QueryBuilder->getTableList();
        if (!empty($tableList) > 0) {
            $query .= " AND `table_name` IN ('" . implode("', '", $tableList) . "') ";
        }

        $query .= $columnIn;
        $query .= $columnNotIn;

        $result = $QueryBuilder->getConnection()->executeQuery($query);
        $data = $result->fetch();
        $generatedQuery = $data["query"];

        $union = $QueryBuilder->getUnion();
        if (!empty($union)) {
            foreach ($union as $unionType => $QBs) {
                /** @var QueryBuilder $QB */
                foreach ($QBs as $QB) {
                    $QB->setConnection($QueryBuilder->getConnection());
                    $QB->setSentenceAnalyzer($QueryBuilder->getSentenceAnalyzer());
                    $searchQuery = new SearchQuery($QB);
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
        if (!empty($appendToMasterQuery)) {
            $Query .= " AND " . $appendToMasterQuery;
        }
        $limit = $QueryBuilder->getLimit();
        if (is_int($limit) && $limit > 0 && stripos($Query, "LIMIT") === false) {
            $Query .= " LIMIT $limit ";
            $endLimit = $QueryBuilder->getEndLimit();
            $offLimit = $QueryBuilder->getOffset();
            if (is_int($endLimit) && $endLimit > 0) {
                $Query .= ", $endLimit ";
            }
            if (is_int($offLimit) && $offLimit > 0) {
                $Query .= " OFFSET $offLimit ";
            }
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
        $query .= " AND `table_name` = '" . $QueryBuilder->getTableName() . "' ";
        $query .= " order by table_name ";
        $result = $QueryBuilder->getConnection()->executeQuery($query);
        $columnNames = array();
        while ($row = $result->fetch()) {
            $columnNames[] = $row["TABLE_NAME"] . "." . $row["COLUMN_NAME"];
        }
        return $columnNames;
    }

}