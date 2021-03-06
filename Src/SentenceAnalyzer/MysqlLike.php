<?php

/**
 * Created by PhpStorm.
 * User: ademola.aina
 * Date: 11/29/2016
 * Time: 8:50 AM
 */
class SearchEngine_Src_SentenceAnalyzer_MysqlLike extends SearchEngine_Src_SentenceAnalyzer_SentenceAnalyzer
{
    protected $operators;

    public function __construct($terms = "")
    {
        parent::__construct($terms);
        $this->setOperators(array(
            " AND " => " AND " . self::e_table_column . " LIKE ",
            " OR " => " OR  " . self::e_table_column . " LIKE ",
            " NOT " => " AND " . self::e_table_column . " NOT LIKE ",
            " AND(" => " AND (",
            "(" => "(". self::e_table_column . " LIKE ",
            ")" => " )",
            "<<&ob>>" => " ( ", //another type of ' ( ' That is, a Literal ' ( ' is needed.
            "<<&cb>>" => " ) ", //another type of ' ) ' That is, a Literal ' ) ' is needed.
            "<<--->>" => " OR ", //another type of ' OR ' That is, a Literal ' OR ' is needed.
            "<<+++>>" => " AND ", //another type of ' AND ' That is, a Literal ' AND ' is needed.
            "<<&&&>>" => " ". self::e_table_column . " NOT LIKE ",  //Another Type of ' NOT ' but without the ' AND ' operator
        ));
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
     * @param $stringTerm
     * @return string
     */
    public function handles_Operators($stringTerm)
    {
        //Handle Special Circumstances
        $stringTerm = " $stringTerm ";
        $stringTerm = str_replace(array(")", "%' '%"), array(" )", "%' OR '%"), $stringTerm);
        $stringTerm = preg_replace("/(\w+ )/i", " '%$1%' ", $stringTerm);
        $stringTerm = str_replace(array("'% ", " %'"), array("'%", "%'"), $stringTerm);
        $stringTerm = str_replace(array("'%OR%'", "'%AND%'", "'%NOT%'", "'%LIKE%'", "NOT("),
                                    array("OR", "AND", "NOT", "LIKE", " NOT ("), $stringTerm);


        /** >>>    NOT (ElementMvc_)       >>> Multiply every word by NOT inside the bracket.
         * and convert them to this class MySql Like operators
         */
        $stringTerm = preg_replace_callback("/(NOT \(ElementMvc_\))/", function($value){
            $value = str_replace("NOT ", " <<+++>> ", $value[0]);
            return str_replace(array("(", "OR", "AND"), array("<<&ob>> <<&&&>> ", "<<--->> <<&&&>> ", "<<+++>> <<&&&>> "), $value);
        }, $stringTerm);

        //replace operators where needed
        $stringTerm = self::e_table_column." LIKE ".str_replace(array_keys($this->operators), $this->operators, $stringTerm);

        //Clean Up Parenthesis jargon
        $stringTerm = trim(preg_replace('/\s+/', " ", $stringTerm));
        $stringTerm = str_replace(
            array("AND AND", self::e_table_column." LIKE (".self::e_table_column, self::e_table_column." LIKE AND"),
            array("AND", "(".self::e_table_column, ""),
            $stringTerm
        );

        return $stringTerm;
    }

    /**
     * @param $stringTerm
     * @return mixed
     */
    public function handles_Asterisk_Operator($stringTerm)
    {
        return str_replace("*", "%", $stringTerm);
    }

    /**
     * @param $stringTerm
     * @return mixed
     */
    public function remove_Epsilon($stringTerm)
    {
        $stringTerm = preg_replace('!\s+!', ' ', trim(strip_tags($stringTerm)));
        $epsilonTransition = array(
            ' \'%AND ' => ' AND ',
            ' \'%OR ' => ' OR ',
            ' \'%NOT ' => ' NOT ',
            ' AND AND ' => ' AND ',
            ' NOT NOT ' => ' NOT ',
            ' OR OR ' => ' OR ',
            "'%'%" => "'%",
            "'% '%" => "'%",
            "%'%'" => "%'",
            "%' %'" => "%'",
            "%%" => "%",
            "''" => "'",
        );
        $stringTerm = str_replace(array_keys($epsilonTransition), $epsilonTransition, $stringTerm);
        return $stringTerm;
    }

    /**
     * @return string
     */
    public function __sentenceToMysqlExpression()
    {
        $term = $this->getTerms();
        $term = $this->handlesStringQuotes($term, "'%", "%'");
        $term = $this->handles_Operators($term);
        $term = $this->handles_Asterisk_Operator($term);
        $this->setTerms($this->remove_Epsilon($term));
        return $this->getTerms();
    }

}