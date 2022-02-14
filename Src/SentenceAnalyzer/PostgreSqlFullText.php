<?php

namespace SearchEngine\SentenceAnalyzer;

use SearchEngine\SentenceAnalyzer\SqlFullText;

class PostgreSqlFullText extends SqlFullText
{
    const SEARCH_NOT_INDEXED = "SEARCH_NOT_INDEXED";
    /** Default Mode */
    const SEARCH_INDEXED = "SEARCH_INDEXED";

    /**
     * @param string $terms
     * @param string $searchMode
     * @param array $fullTextIndexedColumns
     * NOTE: $fullTextIndexedColumns MUST be in the list of your DataSource::ColumnsNeeded
     */
    public function __construct($terms = "", 
                                array $fullTextIndexedColumns = [],
                                $searchMode = PostgresqlFullText::SEARCH_NOT_INDEXED
    )
    {
        parent::__construct($terms, $fullTextIndexedColumns, $searchMode);
        $this->setOperators(array(
            " AND " => " & ",
            " OR " => "  | ",
            " NOT " => " !"
        ));
    }
    
    /**
     * @return string
     */
    public function __sentenceToSqlExpression()
    {
        if ($this->searchMode == PostgresqlFullText::SEARCH_NOT_INDEXED) {
            foreach($this->fullTextIndexedColumns as $index => $value) {
                $this->fullTextIndexedColumns[$index] = stripos($value, "::varchar(255)")===false ? $value."::varchar(255)" : $value;
            }
        }
        return parent::__sentenceToSqlExpression();
    }
}