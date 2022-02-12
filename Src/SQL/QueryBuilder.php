<?php

namespace SearchEngine\Src\SQL;
use Emma\ServiceManager\Singleton;
use SearchEngine\Interfaces\SqlQueryBuilder;
use SearchEngine\Src\SentenceAnalyzer\MysqlFullText;
use SearchEngine\Src\SentenceAnalyzer\SentenceAnalyzer;

/**
 * Class QueryBuilder
 */
class QueryBuilder implements SqlQueryBuilder
{
    /**
     * @var PDOConnection
     */
    private $connection;

    /**
     * @var SentenceAnalyzer
     */
    private $sentenceAnalyzer;

    /**
     * @var string
     */
    private $masterColumn = "";

    /**
     * @var array
     */
    protected $excludeColumnsFromSearch;

    /**
     * @var string
     */
    protected $appendToMasterQuery = "";

    /**
     * @var int
     */
    protected $limit = 5000;

    /**
     * @var int
     */
    protected $endLimit = 0;

     /**
     * @var int
     */
    protected $offset = 0;

    /**
     * @var string
     */
    protected $tableName;

    /**
     * @var array
     */
    protected $columns;

    /**
     * @var bool
     */
    protected $delete = false;

    /**
     * @var bool
     */
    protected $update = false;

    /**
     * @var array
     */
    protected $where;

    /**
     * @var array
     */
    protected $joins;

    /**
     * @var array|QueryBuilder[]
     */
    protected $union;

    /**
     * @var string
     */
    protected $appendToQuery = "";

    const ASC = "ASC";
    const DESC = "DESC";

    /**
     * @var array
     */
    protected $orderBy;


    /**     ADVANCED PARAMS OPTIONS     */

    /**
     * @var array
     */
    private $tableList = array();

    /**
     * @var array
     */
    private $fieldList = array();

    /**
     * @return array
     */
    public function getExcludeColumnsFromSearch()
    {
        return $this->excludeColumnsFromSearch;
    }

    /**
     * @param array $excludeColumnsFromSearch
     * @return $this
     */
    public function setExcludeColumnsFromSearch(array $excludeColumnsFromSearch = array())
    {
        $this->excludeColumnsFromSearch = $excludeColumnsFromSearch;
        return $this;
    }

    /**
     * @param $excludeColumn
     * @return $this
     */
    public function excludeColumnFromSearch($excludeColumn = "table_name.column_name")
    {
        $this->excludeColumnsFromSearch[] = $excludeColumn;
        return $this;
    }

    /**
     * @param $string
     * @return $this
     */
    public function appendToSubQuery($string)
    {
        $this->appendToQuery .= $string;
        return $this;
    }

    /**
     * @return string
     */
    public function getAppendToMasterQuery()
    {
        return $this->appendToMasterQuery;
    }

    /**
     * @param string $appendToMasterQuery
     * @return $this
     */
    public function setAppendToMasterQuery($appendToMasterQuery)
    {
        $this->appendToMasterQuery = $appendToMasterQuery;
        return $this;
    }

    /**
     * @param $string
     * @return $this
     */
    public function appendToMasterQuery($string)
    {
        $this->appendToMasterQuery .= $string;
        return $this;
    }

    /**
     * @return PDOConnection
     */
    public function getConnection()
    {
        return $this->connection;
    }

    /**
     * @param PDOConnection $connection
     * @return QueryBuilder
     */
    public function setConnection($connection)
    {
        $this->connection = $connection;
        return $this;
    }

    /**
     * @param $tableName
     * @param $alias
     * @return QueryBuilder
     */
    public function from($tableName, $alias = "")
    {
        return $this->setTableName($tableName, $alias);
    }

    /**
     * @return string
     */
    public function getTableName()
    {
        return $this->tableName;
    }

    /**
     * @param string $tableName
     * @param $alias
     * @return $this
     */
    public function setTableName($tableName, $alias = "")
    {
        $this->tableName = (empty($alias)) ? $tableName : $tableName . " AS " . $alias;
        $this->tableList[] = $tableName;
        return $this;
    }

    /**
     * @param array $columns_to_alias
     * @return QueryBuilder
     */
    public function select($columns_to_alias = array())
    {
        foreach ((array)$columns_to_alias as $field => $value) {
            if (is_int($field)) {
                $this->columns[] = $value;
                $this->fieldList[] = $value;
            } else {
                $this->columns[] = "$field AS '$value'";
                $this->fieldList[] = $field;
            }
        }
        return $this;
    }

    /**
     * @return array
     */
    public function getColumns()
    {
        return $this->columns;
    }

    /**
     * @param string $tableColumnName
     * @param string $alias
     * @return $this
     */
    public function selectColumn($tableColumnName = "table_name.column_name", $alias = "")
    {
        $this->columns[] = (empty($alias)) ? $tableColumnName : "$tableColumnName AS '$alias' ";
        $this->fieldList[] = $tableColumnName;
        return $this;
    }

    /**
     * @param string $tableColumnName
     * @param string $value
     * @return $this
     */
    public function updateColumn($tableColumnName = "table_name.column_name", $value = "?")
    {
        $this->columns[] = $tableColumnName . " = " . $value;
        $this->fieldList[] = $tableColumnName;
        $this->update = true;
        return $this;
    }

    /**
     * @param array $column_value
     * @return $this
     */
    public function update($column_value = array("table_name.column_name" => "?"))
    {
        foreach ((array)$column_value as $field => $value) {
            $this->updateColumn($field, $value);
        }
        return $this;
    }

    /**
     * @return boolean
     */
    public function isUpdate()
    {
        return $this->update;
    }

    /**
     * @return boolean
     */
    public function isDelete()
    {
        return $this->delete;
    }

    /**
     * @return $this
     */
    public function delete()
    {
        $this->delete = true;
        return $this;
    }

    /**
     * @return array
     */
    public function getWhere()
    {
        return $this->where;
    }

    /**
     * @param string $expression
     * @param string $operator
     * @return $this
     */
    public function where($expression, $operator = null)
    {
        $this->where[] = $operator . ' ' . (string)$expression;
        return $this;
    }

    /**
     * @param string $tableColumn
     * @param string $operator
     * @param string $value
     * @return $this
     */
    public function andWhere($expression)
    {
        return $this->where($expression, " AND ");
    }

    /**
     * @param string $expression
     * @return $this
     */
    public function orWhere($expression)
    {
        return $this->where($expression, " OR ");
    }

    /**
     * @return array
     */
    public function getJoins()
    {
        return $this->joins;
    }

    /**
     * @param $tableName
     * @param $condition
     * @param string $joinType
     * @return $this
     */
    public function join($tableName, $condition, $joinType = "INNER JOIN")
    {
        $this->joins[] = $joinType . " " . $tableName . " ON " . $condition;
        $this->tableList[] = $tableName;
        return $this;
    }

    /**
     * @param $tableName
     * @param $condition
     * @return $this
     */
    public function innerJoin($tableName, $condition)
    {
        return $this->join($tableName, $condition);
    }

    /**
     * @param $tableName
     * @param $condition
     * @return $this
     */
    public function outerJoin($tableName, $condition)
    {
        return $this->join($tableName, $condition, "OUTER JOIN");
    }

    /**
     * @param $tableName
     * @param $condition
     * @return $this
     */
    public function leftJoin($tableName, $condition)
    {
        return $this->join($tableName, $condition, "LEFT JOIN");
    }

    /**
     * @param $tableName
     * @param $condition
     * @return $this
     */
    public function rightJoin($tableName, $condition)
    {
        return $this->join($tableName, $condition, "RIGHT JOIN");
    }

    /**
     * @return $this
     */
    public function union()
    {
        $className = get_class($this);
        $QueryBuilder = new $className();
        $this->union['UNION'][] = $QueryBuilder;
        return $QueryBuilder;
    }

    /**
     * @return $this
     */
    public function unionAll()
    {
        $className = get_class($this);
        $QueryBuilder = new $className();
        $this->union['UNION ALL'][] = $QueryBuilder;
        return $QueryBuilder;
    }

    /**
     * @return array|$this[]
     */
    public function getUnion()
    {
        return $this->union;
    }

    /**
     * @return array
     */
    public function getOrderBy()
    {
        return $this->orderBy;
    }

    /**
     * @param array $orderBy
     * @return QueryBuilder
     */
    public function setOrderBy($orderBy)
    {
        $this->orderBy = $orderBy;
        return $this;
    }


    /**
     * @param $tableColumn
     * @param string $sort
     * @return $this
     */
    public function orderBy($tableColumn, $sort = self::ASC)
    {
        $this->orderBy[] = $tableColumn . " " . $sort;
        return $this;
    }

    
    /**
     * @return int
     */
    public function getLimit()
    {
        return $this->limit;
    }

    /**
     * @param int $limit
     * @return QueryBuilder
     */
    public function setLimit($limit = 0)
    {
        $this->limit = $limit;
        return $this;
    }

    /**
     * @return int
     */
    public function getOffset()
    {
        return $this->offset;
    }

    /**
     * @param int $offset
     * @return QueryBuilder
     */
    public function setOffset($offset)
    {
        $this->offset = $offset;
        return $this;
    }

    /**
     * @return int
     */
    public function getEndLimit()
    {
        return $this->endLimit;
    }

    /**
     * @param int $endLimit
     * @return QueryBuilder
     */
    public function setEndLimit($endLimit)
    {
        $this->endLimit = $endLimit;
        return $this;
    }

    /**
     * @return string
     */
    public function getAppendToQuery()
    {
        return $this->appendToQuery;
    }

    /**
     * @param string $appendToQuery
     * @return QueryBuilder
     */
    public function setAppendToQuery($appendToQuery = "")
    {
        $this->appendToQuery = $appendToQuery;
        return $this;
    }

    /**
     * @param $string
     * @return $this
     */
    public function appendToQuery($string = "")
    {
        $this->appendToQuery .= $string;
        return $this;
    }

    /**
     * @return array
     */
    public function getTableList()
    {
        return $this->tableList;
    }

    /**
     * @return array
     */
    public function getFieldList()
    {
        return $this->fieldList;
    }

    /**
     * @return string
     */
    public function generateQuery()
    {
        if ($this->isDelete()) {
            $query = $this->deleteStatement();
        } else if ($this->isUpdate()) {
            $query = $this->updateStatement();
        } else {
            $query = $this->selectStatement();
        }

        $joins = $this->getJoins();
        if (count($joins) > 0) {
            $query .= " " . implode(" ", $joins);
        }

        $where = $this->getWhere();
        if (count($where) > 0) {
            $query .= " WHERE " . implode(" ", $where);
        }

        $append = $this->getAppendToQuery();
        if (!empty($append)) {
            $query .= " " . addslashes($append);
        }

        $orderBy = $this->getOrderBy();
        if (!empty($orderBy)) {
            $query .= " " . implode(", ", $orderBy);
        }

        $limit = $this->getLimit();
        if (is_int($limit) && $limit > 0 && stripos($query, "LIMIT") === false) {
            $query .= " LIMIT $limit ";
        }

        $union = $this->getUnion();
        if (count($union) > 0) {
            foreach ($union as $unionType => $QBs) {
                /** @var QueryBuilder $QueryBuilder */
                foreach ($QBs as $QueryBuilder) {
                    $query .= " $unionType " . $QueryBuilder->generateQuery();
                }
            }
        }

        return $query;
    }

    /**
     * @return string
     */
    protected function selectStatement()
    {
        $tableColumns = $this->getColumns();
        $query = "SELECT " . (count($tableColumns) > 0) ? implode(", ", $tableColumns) : "*";
        $query .= " FROM " . $this->getTableName();
        return $query;
    }

    /**
     * @return string
     */
    protected function updateStatement()
    {
        return "UPDATE " . $this->getTableName() . " SET " . implode(", ", $this->getColumns());
    }

    /**
     * @return string
     */
    protected function deleteStatement()
    {
        return "DELETE FROM " . $this->getTableName();
    }

    /**
     * @return SentenceAnalyzer
     */
    public function getSentenceAnalyzer()
    {
        return $this->sentenceAnalyzer;
    }

    /**
     * @param SentenceAnalyzer $sentenceAnalyzer
     * @return QueryBuilder
     */
    public function setSentenceAnalyzer($sentenceAnalyzer)
    {
        $this->sentenceAnalyzer = $sentenceAnalyzer;
        return $this;
    }

    /**
     * @return string
     */
    public function getMasterColumn()
    {
        return $this->masterColumn;
    }

    /**
     * @param string $masterColumn
     * @return QueryBuilder
     */
    public function setMasterColumn($masterColumn)
    {
        $this->masterColumn = $masterColumn;
        return $this;
    }

    /**
     * @return string
     */
    public function generateFullTextSearchQuery()
    {
        $searchQuery = new SearchQuery($this);
        return $searchQuery->generateFullTextSearchQuery();
    }

    /**
     * @return string
     */
    public function generateMysqlLikeOrRegexSearchQuery()
    {
        $searchQuery = new SearchQuery($this);
        return $searchQuery->generateMysqlLikeOrRegexSearchQuery();
    }

    /**
     * @return string
     */
    public function __toString()
    {
        if ($this->getSentenceAnalyzer() instanceof MysqlFullText) {
            return $this->generateFullTextSearchQuery();
        } else {
            return $this->generateMysqlLikeOrRegexSearchQuery();
        }
    }

    /**
     * @param array $realFieldNames
     * @return array
     */
    public static function getFieldNamesWithoutTableName($realFieldNames = array())
    {
        foreach ($realFieldNames as $i => $field) {
            $f = explode(".", $field);
            $realFieldNames[$i] = end($f);
        }
        return $realFieldNames;
    }

}