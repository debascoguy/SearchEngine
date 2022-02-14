<?php

namespace SearchEngine\SentenceAnalyzer;

use SearchEngine\SentenceAnalyzer\SqlFullText;

class MysqlFullText extends SqlFullText
{
    const IN_BOOLEAN_MODE = "IN BOOLEAN MODE";
    /** Default Mode */
    const IN_NATURAL_LANGUAGE_MODE = "IN NATURAL LANGUAGE MODE";
    const IN_NATURAL_LANGUAGE_MODE_WITH_QUERY_EXPANSION_MODE = "IN NATURAL LANGUAGE MODE WITH QUERY EXPANSION";
    const IN_WITH_QUERY_EXPANSION_MODE = "WITH QUERY EXPANSION";

    /**
     * @param string $terms
     * @param string $searchMode
     * @param array $fullTextIndexedColumns
     * NOTE: $fullTextIndexedColumns MUST be in the list of your DataSource::ColumnsNeeded
     */
    public function __construct($terms = "", 
                                array $fullTextIndexedColumns = [],
                                $searchMode = MysqlFullText::IN_BOOLEAN_MODE
    )
    {
        parent::__construct($terms, $fullTextIndexedColumns, $searchMode);
        $this->setOperators(array(
            " AND " => " +",
            " OR " => "  ",
            " NOT " => " -"
        ));
    }

}