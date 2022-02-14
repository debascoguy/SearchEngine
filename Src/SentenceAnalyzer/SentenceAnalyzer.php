<?php

namespace SearchEngine\SentenceAnalyzer;

class SentenceAnalyzer
{
    /**
     * @var string
     */
    protected $originalSearchString;

    /**
     * @var string
     */
    protected $terms;

    /**
     * @var bool
     */
    protected $isStringQuotesHandled = false;


    const e_table_column = "[::table_column::]";

    const mysqlFullTextSearch = 1;
    const SqlLikeSearch = 2;
    const mysqlRegexSearch = 3;

    /**
     * @return mixed
     */
    public function getOriginalSearchString()
    {
        return $this->originalSearchString;
    }

    /**
     * @param mixed $originalSearchString
     * @return SentenceAnalyzer
     */
    public function setOriginalSearchString($originalSearchString)
    {
        $this->originalSearchString = $originalSearchString;
        return $this;
    }

    /**
     * @return bool
     */
    public function getIsStringQuotesHandled()
    {
        return $this->isStringQuotesHandled;
    }

    /**
     * @param bool $isStringQuotesHandled
     */
    public function setIsStringQuotesHandled($isStringQuotesHandled)
    {
        $this->isStringQuotesHandled = $isStringQuotesHandled;
    }

    /**
     * @param string $terms
     */
    public function __construct($terms = "")
    {
        $this->terms = $terms;
        $this->originalSearchString = $terms;
    }

    /**
     * @param $terms
     * @param int $analyzer
     * @return MysqlFullText|SqlLike|MysqlRegex
     */
    public static function getAnalyzer($terms, $analyzer = null)
    {
        switch ($analyzer) {
            case self::mysqlRegexSearch     :
                return new MysqlRegex($terms);
            case self::SqlLikeSearch      :
                return new SqlLike($terms);
            case self::mysqlFullTextSearch  :
                return new MysqlFullText($terms);
            default                         :
                $recommendedAnalyzer = 0;
                $terms = self::handlesStringQuotes($terms, "", "", $recommendedAnalyzer);
                /** @var sentenceAnalyzer $Analyzer */
                $Analyzer = self::getAnalyzer($terms, $recommendedAnalyzer);
                if ($Analyzer instanceof MysqlFullText) {
                    $Analyzer->setIsStringQuotesHandled(true);
                }
                return $Analyzer;
        }
    }

    /**
     * @param $stringTerm
     * @param string $replaceOpenQuote
     * @param string $replaceClosingQuote
     * @param null $recommendedAnalyzer
     * @return string
     */
    public function handlesStringQuotes($stringTerm, $replaceOpenQuote = "(", $replaceClosingQuote = ")", &$recommendedAnalyzer = null)
    {
        $stringTerm = stripslashes($stringTerm);

        /**
         * RegEx Matches::>>  Phone Number |OR| ZipCode |OR| String In Quotes |OR| Ordinary String separated with spaces
         */
        preg_match_all('/\([0-9]{3}\)[\s][0-9]{3}[\-][0-9]{4}|[0-9]{3}[\-][0-9]{6}|[0-9]{3}[\s][0-9]{6}|[0-9]{3}[\s][0-9]{3}[\s][0-9]{4}|[0-9]{10}|[0-9]{3}[\-][0-9]{3}[\-][0-9]{4}|d{5}(-\d{4})?|\("(?:\\\\.|[^\\\\"])*"\)|"(?:\\\\.|[^\\\\"])*"|\S+/',
            $stringTerm, $matches);
        $matches = $matches[0];
        $stringTerm = "";
        $recommendedAnalyzer = self::mysqlFullTextSearch;
        foreach ($matches as $key => $string) {
            //Format Phone to what we have in tables.
            preg_match('/\([0-9]{3}\)[\s][0-9]{3}[\-][0-9]{4}|[0-9]{3}[\-][0-9]{6}|[0-9]{3}[\s][0-9]{6}|[0-9]{3}[\s][0-9]{3}[\s][0-9]{4}|[0-9]{10}|[0-9]{3}[\-][0-9]{3}[\-][0-9]{4}/',
                $string, $subMatches);
            if (!empty($subMatches[0])) {
                $string = str_replace(array(" ", "-", "(", ")"), "", $string);
            }
            $checkEmail = trim(preg_replace("/(\(|\))/i", "", $string));
            if (filter_var($checkEmail, FILTER_VALIDATE_EMAIL) !== false) {
                $string = str_replace($checkEmail, '"' . $checkEmail . '"', $string);
            }
            if (is_numeric($string)) {
                $recommendedAnalyzer = self::SqlLikeSearch;
            }

            $quoteMatch = array();
            if (!empty($replaceOpenQuote) && !empty($replaceClosingQuote)) {
                preg_match("/\"(.*?)\"|'(.*?)'/", $string, $quoteMatch);
            }
            if (!empty($quoteMatch[0])) {
                $stringTerm .= preg_replace("/\"(.*?)\"|'(.*?)'/", "$replaceOpenQuote$1$replaceClosingQuote",
                        $string) . " ";
            } else {
                if (!in_array($string, array("AND", "OR", "NOT"))) {
                    $stringTerm .= $string . " OR ";
                } else {
                    $stringTerm = rtrim($stringTerm, " OR ");
                    $stringTerm .= " " . $string . " ";
                }
            }
        }
        $stringTerm = rtrim($stringTerm, " OR ");

        return $stringTerm;
    }

    /**
     * @return string
     */
    public function __sentenceToSqlExpression()
    {
        return $this->getTerms();
    }

    /**
     * @return string
     */
    public function __toString()
    {
        return $this->__sentenceToSqlExpression();
    }

    /**
     * @return string
     */
    public function getTerms()
    {
        return $this->terms;
    }

    /**
     * @param string $terms
     * @return SentenceAnalyzer
     */
    public function setTerms($terms)
    {
        $this->terms = $terms;
        return $this;
    }

    /**
     * @param $string
     * @return bool
     */
    public static function isMysqlRegEx($string)
    {
        return (substr_count($string, "REGEXP") > 0);
    }

    /**
     * @param $string
     * @return bool
     */
    public static function isSqlLikeEx($string)
    {
        return (substr_count($string, "LIKE") > 0);
    }

    /**
     * @param $string
     * @return bool
     */
    public static function isFulltext($string)
    {
        return ((substr_count($string, "REGEXP") == 0) && (substr_count($string, "LIKE") == 0));
    }

    /**
     * @param $field
     * @param $expression
     * @return string
     */
    public static function finalizeExpression($field, $expression)
    {
        return str_replace(self::e_table_column, $field, $expression);
    }

}