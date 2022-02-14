<?php

namespace SearchEngine\SQL;

use SearchEngine\Interfaces\SqlSearchQuery;
use SearchEngine\SentenceAnalyzer\PostgreSqlFullText;
use SearchEngine\SentenceAnalyzer\MysqlFullText;
use SearchEngine\SentenceAnalyzer\PostgreSqlLike;
use SearchEngine\SentenceAnalyzer\SqlFullText;

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
        $searchStringExpression = addslashes((string)$sentenceAnalyzer);
        $fullTextIndexedColumns = $sentenceAnalyzer->getFullTextIndexedColumns();

        if ($sentenceAnalyzer instanceof MysqlFullText) {
            $searchMode = $sentenceAnalyzer->getSearchMode();
            $whereClause = "MATCH (" . implode(", ", $fullTextIndexedColumns) . ") AGAINST ('$searchStringExpression' $searchMode) ";
            $relevanceColumn = ($whereClause . " AS relevance "); 
        }
        else if ($sentenceAnalyzer instanceof PostgreSqlFullText) {
            if ($sentenceAnalyzer->getSearchMode() == PostgreSqlFullText::SEARCH_NOT_INDEXED) {
                $indexedColumn = "to_tsvector('english', " . implode(" || ' ' || ", $fullTextIndexedColumns).")" ;                           
                $whereClause = "$indexedColumn @@ plainto_tsquery('english', '$searchStringExpression')";
                $relevanceColumn = "ts_rank($indexedColumn, plainto_tsquery('english', '$searchStringExpression') ) AS relevance";
            }
            else{
                $size = count($fullTextIndexedColumns);

                $indexedColumns = array_map(function($ft) use ($searchStringExpression) {
                    return "ts_rank($ft, plainto_tsquery('english', '$searchStringExpression'))";
                }, $fullTextIndexedColumns);

                $relevanceColumn = $size > 1 ? "(".implode(" + ", $indexedColumns) . ")/$size AS relevance" : "(".$indexedColumns[0] . ") AS relevance";

                $where = array_map(function($ft) use ($searchStringExpression) {
                    return "$ft @@ plainto_tsquery('english', '$searchStringExpression')";
                }, $fullTextIndexedColumns);

                $whereClause = implode(" OR ", $where);
            }
        }
        else{
            $whereClause = "1=1";
            $relevanceColumn = "0 AS relevance ";
        }

        $subQuery = $this->generateSearchSubQuery(true, $fullTextIndexedColumns);

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
        $tableList = $QueryBuilder->getTableList();
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
            $allTableColumns = $this->getTableColumns($tableList);
            if (!empty($tableColumnsNeeded)) {
                $query = " SELECT CONCAT( 'SELECT " 
                                . addslashes(implode(", ", $tableColumnsNeeded ))  . ", " 
                                . "CONCAT(".addslashes(implode(", ", $allTableColumns )) . ") AS {$QueryBuilder->getMasterColumn()} 
                                FROM " . $QueryBuilder->getTableName();

                $columnIn = " AND CONCAT(TABLE_NAME, '.', COLUMN_NAME) IN ('" . implode("', '", $realFieldNames) . "') ";
            } else {
                $query = " SELECT CONCAT( 'SELECT *, "  . addslashes(implode(", ", $allTableColumns )) . " AS {$QueryBuilder->getMasterColumn()}  FROM " . $QueryBuilder->getTableName();
                $columnIn = "";
            }
        }


        if (is_array($excludeColumnsFromSearch) && count($excludeColumnsFromSearch) > 0) {
            $columnNotIn = " AND CONCAT(TABLE_NAME, '.', COLUMN_NAME) NOT IN ('" . implode("', '", $excludeColumnsFromSearch) . "') ";
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

        $schema = $QueryBuilder->getConnection()->getConnectionProperty()->getSchema();
        $query .= "' ) AS query FROM information_schema.columns
                    WHERE  table_schema = '$schema' ";
        if (!empty($tableList) > 0) {
            $query .= " AND table_name IN ('" . implode("', '", $tableList) . "') ";
        }

        $query .= $columnIn;
        $query .= $columnNotIn;

        $result = $QueryBuilder->getConnection()->executeQuery($query);
        $data = $result->fetch();
        $generatedQuery = $data["query"];
        $generatedQuery = str_replace('"', "'", $generatedQuery);

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
    public function generateSqlLikeOrRegexSearchQuery()
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
     * @return string
     */
    public function __toString()
    {
        if ($this->QueryBuilder->getSentenceAnalyzer() instanceof SqlFullText) {
            return $this->generateFullTextSearchQuery();
        } else {
            return $this->generateSqlLikeOrRegexSearchQuery();
        }
    }

    /**
     * @return array
     */
    public function getTableColumns($tableList = [])
    {
        $QueryBuilder = $this->getQueryBuilder();
        $schema = $QueryBuilder->getConnection()->getConnectionProperty()->getSchema();

        $query = " select * from information_schema.columns where table_schema = '$schema' ";
        $query .= " AND table_name IN  ('" . implode("', '", $tableList) . "') ";
        $query .= " order by table_name ";
        $result = $QueryBuilder->getConnection()->executeQuery($query);
        $columnNames = array();
        while ($row = $result->fetch()) {
            $columnNames[] = $row["TABLE_NAME"] . "." . $row["COLUMN_NAME"];
        }
        return $columnNames;
    }

}