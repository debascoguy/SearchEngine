<?php

namespace SearchEngine\Src\Ranking;

use SearchEngine\Interfaces\CompareString;
use SearchEngine\Interfaces\EditDistance as InterfacesEditDistance;

/**
 * Class SearchEngine_Src_Ranking_EditDistance
 *
 * Calculate the modified Levenshtein Distance of the two strings
 * Using this algorithm eliminates the MySQL LIKE statement in your query.
 */
class EditDistance implements CompareString, InterfacesEditDistance
{
    private $string1, $string2, $string1Length, $string2Length, $LevenshteinDistanceArray = array();

    public function __construct($str1, $str2)
    {
        $this->setString1(strtoupper($str1));
        $this->setString2(strtoupper($str2));
        $this->setString1Length(strlen($str1));
        $this->setString2Length(strlen($str2));
        $this->initializeLevenshteinDistanceArray();
    }

    /**
     * @return mixed
     */
    public function getString1()
    {
        return $this->string1;
    }

    /**
     * @param mixed $string1
     */
    public function setString1($string1)
    {
        $this->string1 = $string1;
    }

    /**
     * @return mixed
     */
    public function getString2()
    {
        return $this->string2;
    }

    /**
     * @param mixed $string2
     */
    public function setString2($string2)
    {
        $this->string2 = $string2;
    }

    /**
     * @return mixed
     */
    public function getString1Length()
    {
        return $this->string1Length;
    }

    /**
     * @param mixed $string1Length
     */
    public function setString1Length($string1Length)
    {
        $this->string1Length = $string1Length;
    }

    /**
     * @return mixed
     */
    public function getString2Length()
    {
        return $this->string2Length;
    }

    /**
     * @param mixed $string2Length
     */
    public function setString2Length($string2Length)
    {
        $this->string2Length = $string2Length;
    }

    /**
     * @return array
     */
    public function getLevenshteinDistanceArray()
    {
        return $this->LevenshteinDistanceArray;
    }

    /**
     * @Initialize Levenshtein Matrix Array
     */
    protected function initializeLevenshteinDistanceArray()
    {
        $len1 = $this->getString1Length();
        $len2 = $this->getString2Length();
        $this->LevenshteinDistanceArray = array();
        for ($i = 0; $i <= $len1; $i++) {
            $this->LevenshteinDistanceArray[$i][0] = $i;
        }
        for ($j = 0; $j <= $len2; $j++) {
            $this->LevenshteinDistanceArray[0][$j] = $j;
        }
    }

    /**
     * @return mixed
     */
    public function findDistance()
    {
        $len1 = $this->getString1Length();
        $len2 = $this->getString2Length();
        $str1 = $this->getString1();
        $str2 = $this->getString2();
        $d = $this->getLevenshteinDistanceArray();

        //Find initial Levenshtein Distance
        for ($i = 1; $i <= $len1; $i++) {
            for ($j = 1; $j <= $len2; $j++) {
                $c = ($str1[$i - 1] == $str2[$j - 1]) ? 0 : 1;
                $d[$i][$j] = min($d[$i - 1][$j] + 1, $d[$i][$j - 1] + 1, $d[$i - 1][$j - 1] + $c);
                if (($i > 1) && ($j > 1) && ($str1[$i] == $str2[$j - 1]) && ($str1[$i - 1] == $str2[$j])) {
                    $d[$i][$j] = min($d[$i][$j], $d[$i - 2][$j - 2] + $c);
                }
            }
        }
        $dist = $d[$len1][$len2];
        return $dist;
    }


    /**
     * @return float|mixed
     */
    public function result()
    {
        $len1 = $this->getString1Length();
        $len2 = $this->getString2Length();
        $str1 = $this->getString1();
        $str2 = $this->getString2();
        $LavenshteinDistance = $this->findDistance();

        //Check if Shorter is a Sub-String of the other
        //If true, Apply a factor of 2
        if ($LavenshteinDistance > 1 && $len1 != $len2) {
            if ($len1 > $len2) {
                $short = $str2;
                $long = $str1;
            } else {
                $short = $str1;
                $long = $str2;
            }

            if (stripos($long, $short) !== false) {
                $factor = $LavenshteinDistance / substr_count($long, $short);
                $LavenshteinDistance -= $factor;
            }
        }
        return $LavenshteinDistance;
    }

    /**
     * @return string
     */
    public function __toString()
    {
        return (string)$this->result();
    }

}