<?php

namespace SearchEngine\SentenceAnalyzer;

use SearchEngine\SentenceAnalyzer\SentenceAnalyzer;

class SqlFullText extends SentenceAnalyzer
{
    protected $operators;

    /**
     * @var string
     */
    protected $searchMode;

    /**
     * @var array
     * NOTE: $fullTextIndexedColumns MUST be in the list of your DataSource::ColumnsNeeded
     */
    protected $fullTextIndexedColumns;

    /**
     * @param string $terms
     * @param string $searchMode
     * @param array $fullTextIndexedColumns
     * NOTE: $fullTextIndexedColumns MUST be in the list of your DataSource::ColumnsNeeded
     */
    public function __construct($terms = "", array $fullTextIndexedColumns = [], $searchMode = null)
    {
        parent::__construct($terms);
        $this->setOperators(array(
            " AND " => " +",
            " OR " => "  ",
            " NOT " => " -"
        ));
        $this->setSearchMode($searchMode)->setFullTextIndexedColumns($fullTextIndexedColumns);
    }
    
    /**
     * @return array
     */
    public function getOperators()
    {
        return $this->operators;
    }

    /**
     * @param array $operators
     */
    public function setOperators($operators)
    {
        $this->operators = $operators;
    }

    /**
     * @return string
     */
    public function getSearchMode()
    {
        return $this->searchMode;
    }

    /**
     * @param string $searchMode
     * @return MysqlFullText
     */
    public function setSearchMode($searchMode)
    {
        $this->searchMode = $searchMode;
        return $this;
    }

    /**
     * @return array
     */
    public function getFullTextIndexedColumns()
    {
        return $this->fullTextIndexedColumns;
    }

    /**
     * @param array $fullTextIndexedColumns
     * @return MysqlFullText
     */
    public function setFullTextIndexedColumns($fullTextIndexedColumns)
    {
        $this->fullTextIndexedColumns = $fullTextIndexedColumns;
        return $this;
    }

    /**
     * @param $stringTerm
     * @return string
     */
    public function handles_Operators($stringTerm)
    {
        return str_replace(array_keys($this->operators), $this->operators, $stringTerm);
    }

    /**
     * @param $stringTerm
     * @return mixed
     */
    public function remove_Epsilon($stringTerm)
    {
        $stringTerm = preg_replace('!\s+!', ' ', trim(strip_tags($stringTerm)));
        $epsilonTransition = array(
            '--' => '-',
            "++" => "+",
            "+ " => "+",
            "- " => "-",
            "> " => ">",
            "< " => "<",
            "( " => "(",
            ") " => ")",
        );
        $stringTerm = str_replace(array_keys($epsilonTransition), $epsilonTransition, $stringTerm);
        return $stringTerm;
    }

    /**
     * @return string
     */
    public function __sentenceToSqlExpression()
    {
        $term = $this->getTerms();
        if ($this->getIsStringQuotesHandled() == false) {
            $term = $this->handlesStringQuotes($term, "", "");
        }
        $term = $this->handles_Operators($term);
        $this->setTerms($this->remove_Epsilon($term));
        return $this->getTerms();
    }
}