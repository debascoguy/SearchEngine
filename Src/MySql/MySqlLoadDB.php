<?php

/**
 * Created By: Ademola Aina
 * Email: debascoguy@gmail.com
 * Date: 1/29/2018
 * Time: 11:32 PM
 */
class SearchEngine_Src_MySql_MySqlLoadDB implements
    SearchEngine_Interface_MysqlLoadDB,
    SearchEngine_Interface_Search,
    Countable
{
    /**
     * @var SearchEngine_Src_MySql_Connection
     */
    private $connection;

    /**
     * @var SearchEngine_Src_MySql_QueryBuilder
     */
    private $QueryBuilder;

    /**
     * @var SearchEngine_Src_SentenceAnalyzer_SentenceAnalyzer
     */
    private $sentenceAnalyzer;

    /**
     * @var array
     */
    private $result = array();

    /**
     * @var SearchEngine_Services_CallBackHandler[]|array
     */
    private $resultCallBacks  = null;

    /**
     * @param SearchEngine_Src_MySql_Connection|null $connection
     * @param SearchEngine_Src_MySql_QueryBuilder|null $QueryBuilder
     * @param SearchEngine_Src_SentenceAnalyzer_SentenceAnalyzer|null $sentenceAnalyzer
     */
    public function __construct(SearchEngine_Src_MySql_Connection $connection = null,
                                SearchEngine_Src_MySql_QueryBuilder $QueryBuilder = null,
                                SearchEngine_Src_SentenceAnalyzer_SentenceAnalyzer $sentenceAnalyzer = null

    )
    {
        $this->connection = $connection;
        $this->QueryBuilder = $QueryBuilder;
        $this->sentenceAnalyzer = $sentenceAnalyzer;
    }

    /**
     * @return array
     */
    public function search()
    {
        $Connection = $this->getConnection();
        $sentenceAnalyzer = $this->getSentenceAnalyzer();

        $QueryBuilder = $this->getQueryBuilder();
        $QueryBuilder->setConnection($Connection);
        $QueryBuilder->setSentenceAnalyzer($sentenceAnalyzer);

        $query = $QueryBuilder->__toString();
        $result = $Connection->executeQuery($query);
        if ($result != false)
        {
            if (count($this->resultCallBacks) > 0)
            {
                $searchString = $sentenceAnalyzer->getOriginalSearchString();
                $rankingColumn = $QueryBuilder->getMasterColumn();
                while($row = $result->fetch_assoc()) {
                    $row = $this->handleCallBacks($row, $rankingColumn, $searchString);
                    $this->result[] = $row;
                }
            }
            else
            {
                while($row = $result->fetch_assoc()) {
                    $this->result[] = $row;
                }
            }
        }
        return $this;
    }

    /**
     * @param $row
     * @param $masterColumnName
     * @param $searchString
     * @return mixed
     */
    public function handleCallBacks($row, $masterColumnName, $searchString)
    {
        if (!empty($row))
        {
            foreach($this->resultCallBacks as $callable)
            {
                $arguments = array_merge(array($row, $masterColumnName, $searchString), (array)$callable->getMetadata());
                $row = $callable->call($arguments);
            }
        }
        return $row;
    }

    /**
     * @param callback $callable [Also, Accepts Anonymous function]
     * @param array $arguments
     * @return $this
     */
    public function registerResultCallBack($callable, $arguments = array())
    {
       $this->resultCallBacks[] = new SearchEngine_Services_CallBackHandler($callable, $arguments);
        return $this;
    }

    /**
     * @return array|SearchEngine_Services_CallBackHandler[]
     */
    public function getResultCallBacks()
    {
        return $this->resultCallBacks;
    }

    /**
     * @return $this
     */
    public function resetCallBacks()
    {
        $this->resultCallBacks = array();
        return $this;
    }

    /**
     * @return array
     */
    public function getResult()
    {
        return $this->result;
    }

    /**
     * @param array $result
     * @return SearchEngine_Src_MySql_MySqlLoadDB
     */
    public function setResult($result)
    {
        $this->result = $result;
        return $this;
    }

    /**
     * @return SearchEngine_Src_MySql_Connection
     */
    public function getConnection()
    {
        return $this->connection;
    }

    /**
     * @param SearchEngine_Src_MySql_Connection $connection
     * @return $this
     */
    public function setConnection($connection)
    {
        $this->connection = $connection;
        return $this;
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
     * @return SearchEngine_Src_MySql_MySqlLoadDB
     */
    public function setQueryBuilder($QueryBuilder)
    {
        $this->QueryBuilder = $QueryBuilder;
        return $this;
    }

    /**
     * @return SearchEngine_Src_SentenceAnalyzer_SentenceAnalyzer
     */
    public function getSentenceAnalyzer()
    {
        return $this->sentenceAnalyzer;
    }

    /**
     * @param $sentenceAnalyzer
     * @return $this
     */
    public function setSentenceAnalyzer($sentenceAnalyzer)
    {
        $this->sentenceAnalyzer = $sentenceAnalyzer;
        return $this;
    }

    /**
     * @return ArrayIterator
     */
    public function getIterator()
    {
        return new ArrayIterator((array)$this->result);
    }

    /**
     * @return int
     */
    public function count()
    {
        return count($this->result);
    }

}