<?php

namespace SearchEngine\Ranking;

use SearchEngine\Interfaces\CompareString;
use SearchEngine\Interfaces\Ranking;

/**
 * Class SearchEngine_Src_Ranking_RankingData
 */
class RankingData implements CompareString, Ranking
{

    /**
     * @var string
     */
    private $string1,
        $string2;

    /**
     * @var
     */
    private $editDist;

    function __construct($string1, $string2, $editDist = "")
    {
        $this->setString1($string1);
        $this->setString2($string2);
        $this->setEditDist($editDist);

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
    public function getEditDist()
    {
        return $this->editDist;
    }

    /**
     * @param mixed $editDist
     */
    public function setEditDist($editDist = "")
    {
        if (empty($editDist)) {
            $editDistance = new EditDistance($this->getString1(), $this->getString2());
            $editDist = $editDistance->result();
        }
        $this->editDist = $editDist;
    }

    /**
     * @return int|float : Final Ranking Result.
     */
    public function result()
    {
        $string1 = trim($this->getString1());
        $string2 = trim($this->getString2());
        $editDist = $this->getEditDist();

        if (strlen($string1) > strlen($string2)) {
            $short = $string2;
            $long = $string1;
        } else {
            $short = $string1;
            $long = $string2;
        }
        similar_text($long, $short, $similarTextPercentage);
        $substrCount = substr_count($long, $short);
        if ($substrCount > 0) {
            $similarTextPercentage = $substrCount * $similarTextPercentage;
        }

        return round($editDist + $similarTextPercentage, 3);
    }

    /**
     * @return string
     */
    public function __toString()
    {
        return (string)$this->result();
    }
}