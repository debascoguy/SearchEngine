<?php

namespace SearchEngine\SQL;

use SearchEngine\SentenceAnalyzer\MysqlLike;
use SearchEngine\SQL\SearchQuery;

class MySqlSearchQuery extends SearchQuery
{
    /**
     * SearchQuery constructor.
     * @param QueryBuilder $QueryBuilder
     */
    public function __construct(QueryBuilder $QueryBuilder)
    {
        parent::__construct($QueryBuilder);
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
        
        $searchMode = $sentenceAnalyzer->getSearchMode();
        $whereClause = "MATCH (" . implode(", ", $fullTextIndexedColumns) . ") AGAINST ('$searchStringExpression' $searchMode) ";
        $relevanceColumn = ($whereClause . " AS relevance "); 
     
        $subQuery = $this->generateSearchSubQuery(true, $fullTextIndexedColumns);

        $tableList = $QueryBuilder->getTableList();
        if (count($tableList) == 1) {
            $temp = explode(" FROM ", $subQuery);
            $Query = $temp[0] . ", " . $relevanceColumn . " FROM " . $temp[1];
        }
        else{
            $Query = " SELECT DISTINCT t.*, $relevanceColumn ";
            $Query .= " FROM ($subQuery) AS t ";
            $qbTable = $QueryBuilder->getTableName();
            foreach ($tableList as $table) {
                $match = array();
                foreach ($fullTextIndexedColumns as $tableDotFieldName) {
                    $temp = explode(".", $tableDotFieldName);
                    if ($table != $temp[0] && $table != $qbTable) {
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
                $columnIn = " AND CONCAT(TABLE_NAME, '.', COLUMN_NAME) IN ('" . implode("', '", $realFieldNames) . "') ";
            } else {
                $query = " SELECT CONCAT( 'SELECT *, " . "
                                CONCAT_WS(\' \', ',
                                GROUP_CONCAT(CONCAT(TABLE_NAME, '.', COLUMN_NAME)), ') AS {$QueryBuilder->getMasterColumn()}
                                FROM " . $QueryBuilder->getTableName();
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
        $tableList = $QueryBuilder->getTableList();
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
}