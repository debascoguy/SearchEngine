<?php

/**
 * Created by PhpStorm.
 * User: ademola.aina
 * Date: 11/22/2016
 * Time: 8:21 AM
 */
class SearchEngine_Src_SentenceAnalyzer_MysqlRegex extends SearchEngine_Src_SentenceAnalyzer_SentenceAnalyzer
{
    public function __construct($terms = "")
    {
        parent::__construct($terms);
    }

    /**
     * @param $stringTerm
     * @return string
     */
    public function handles_NOT_Operator($stringTerm)
    {
        if (strpos($stringTerm, "NOT") !== false) {
            $i = 0;
            $temp_terms = explode(" ", $stringTerm);
            $count = count($temp_terms);
            while ($i < $count) {
                $temp_terms[$i] = trim($temp_terms[$i]);
                if ($temp_terms[$i] == "NOT") {
                    if ($temp_terms[$i + 2] == "OR" || $temp_terms[$i + 2] == "AND" || $temp_terms[$i + 2] == "NOT" || !isset($temp_terms[$i + 2])) {
                        //Single word NOT
                        $temp_terms[$i + 1] = str_replace(array("(", ")"), "", $temp_terms[$i + 1]);
                        $temp_terms[$i + 1] = "[^" . $temp_terms[$i + 1] . "]";
                        unset($temp_terms[$i]);
                    } else {
                        //multiple word NOT
                        $openBracketCount = substr_count($temp_terms[$i + 1], "(");
                        if ($openBracketCount > 0) {
                            $j = $i + 1;
                            $size = count($temp_terms) - $j;
                            $openBracket = false;
                            $openBracketCount = $closeBracketCount = 0;
                            while ($j < $size) {
                                $temp = substr_count($temp_terms[$j], "(");
                                if ($temp > 0 && $openBracket == false) {
                                    $openBracket = true;
                                    $openBracketCount = $temp;
                                    $temp_terms[$j] = str_replace("(", "", $temp_terms[$j]);
                                    $temp_terms[$j] = "[^" . $temp_terms[$j];
                                    unset($temp_terms[$j - 1]);
                                }
                                $temp = substr_count($temp_terms[$j], ")");
                                if ($temp > 0 && $openBracket == true) {
                                    $closeBracketCount += $temp;
                                    if ($openBracketCount == $closeBracketCount) {
                                        $temp_terms[$j] = str_replace(")", "", $temp_terms[$j]);
                                        $temp_terms[$j] = $temp_terms[$j] . "]";
                                        break;
                                    } else {
                                        if ($closeBracketCount < $openBracketCount) {
                                            $temp_terms[$j] = str_replace(")", "", $temp_terms[$j]);
                                        }
                                    }
                                }
                                $j++;
                            }
                        }
                    }
                }
                $i++;
            }
            $stringTerm = implode(" ", $temp_terms);
        }
        return $stringTerm;
    }

    /**
     * @param $stringTerm
     * @return mixed
     */
    public function handles_OR_Operator($stringTerm)
    {
        return str_replace(" OR ", "|", $stringTerm);
    }

    /**
     * @param $stringTerm
     * @return string
     */
    public function handles_AND_Operator($stringTerm)
    {
        if (stripos($stringTerm, " AND ")) {
            $stringTerm = self::e_table_column . " REGEXP '" . str_replace(" AND ",
                    "' AND " . self::e_table_column . " REGEXP '", $stringTerm) . "'";
        } else {
            $stringTerm = self::e_table_column . " REGEXP '$stringTerm' ";
        }
        return $stringTerm;
    }

    /**
     * @param $stringTerm
     * @return mixed
     * handle extra spaces: remove epsilon grammars
     */
    public function remove_Epsilon($stringTerm)
    {
        $stringTerm = preg_replace('!\s+!', ' ', trim(strip_tags($stringTerm)));
        $epsilonTransition = array(
            '| ' => '|',
            ' |' => '|',
            ' ]' => '] ',
            ' ] ' => '] ',
            '[ ' => ' [',
            ' [ ' => ' [',
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
        $term = $this->handlesStringQuotes($term, "(", ")");
        $term = $this->handles_NOT_Operator($term);
        $term = $this->handles_OR_Operator($term);
        $term = $this->handles_AND_Operator($term);
        $this->setTerms($this->remove_Epsilon($term));
        return $this->getTerms();
    }

}