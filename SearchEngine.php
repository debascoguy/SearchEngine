<?php

namespace SearchEngine;

use SearchEngine\Interfaces\Search;
use SearchEngine\Services\CallBackHandler;
use SearchEngine\Src\DictionaryManager;
use SearchEngine\Src\FileSystem\CtrlF;
use SearchEngine\Src\Ranking\EditDistance;
use SearchEngine\Src\Ranking\RankingData;

/**
 * @Author: Ademola Aina
 * Email: debascoguy@gmail.com
 * Date: 1/30/2018
 * Time: 10:55 AM
 */
class SearchEngine implements Search
{
    /**
     * @var Search[]|array
     */
    protected $loadDB;

    /**
     * @var array
     */
    protected $result = [];

    /**
     * @var CallBackHandler[]|array
     */
    private $resultCallBacks = null;

    /**
     * @var DictionaryManager
     */
    protected $DictionaryManager;

    const AUTO_SUGGEST = 1;
    const AUTO_SPELL_CHECK = 2;


    /**
     * @self
     */
    public function __construct()
    {
        $this->setDictionaryManager(new DictionaryManager());
    }

    /**
     * @return $this
     */
    public function search()
    {
        $searchResult = [];
        $LoadDBs = $this->getLoadDB();
        $count = count($LoadDBs);
        switch ($count) {
            case 0 :
                $searchResult = array("No Search Result!");
                break;
            case 1 :
                $searchResult = $LoadDBs[0]->search()->getResult();
                break;
            case 2 :
                $searchResult = array_merge($LoadDBs[0]->search()->getResult(),
                    $LoadDBs[1]->search()->getResult()
                );
                break;
            case 3 :
                $searchResult = array_merge($LoadDBs[0]->search()->getResult(),
                    $LoadDBs[1]->search()->getResult(),
                    $LoadDBs[2]->search()->getResult()
                );
                break;
            default:
                /** @Multiple LoadDB Sources */
                foreach ($LoadDBs as $loadDB) {
                    $searchResult = array_merge($searchResult, $loadDB->search()->getResult());
                }
        }

        if (!empty($this->resultCallBacks) && !empty($searchResult) && $count > 0) {
            $searchResult = $this->handleResultCallBacks($searchResult);
        }
        return $this->setResult($searchResult);
    }

    /**
     * @param $str1
     * @param $str2
     * @return float
     * Calculate the modified Levenshtein Distance of the two strings
     * Using this algorithm eliminates the MySQL LIKE statement in your query.
     */
    public static function calculateEditDistance($str1, $str2)
    {
        $editDistance = new EditDistance($str1, $str2);
        return $editDistance->result();
    }

    /**
     * @param $string1
     * @param $string2
     * @param $editDist
     * @return float|int of Final Ranking Result.
     */
    public static function getRanking($string1, $string2, $editDist = 0)
    {
        $rankingSystem = new RankingData($string1, $string2, $editDist);
        return $rankingSystem->result();
    }

    /**
     * @param $term
     * @param $action
     * @return String
     */
    public function autoSuggest($term, $action = null)
    {
        $dictionary = $this->getDictionaryManager();
        switch ($action) {
            case self::AUTO_SUGGEST :
                $chunkOfTerms = explode(" ", $term);
                $word = array_pop($chunkOfTerms);
                $str_concat = implode(" ", $chunkOfTerms);
                $result = $dictionary->autoSuggest($word, $str_concat);
                return json_encode($result);

            case self::AUTO_SPELL_CHECK :
                $result = $dictionary->autoSpellCheck($term);
                return json_encode(array("correction" => $result));

            default :
                $chunkOfTerms = explode(" ", $term);
                $word = array_pop($chunkOfTerms);
                $str_concat = implode(" ", $chunkOfTerms);
                $result = $dictionary->autoSuggest($word, $str_concat);
                $sentenceCorrection = array("correction" => $dictionary->autoSpellCheck($term));
                return json_encode(array_unshift($result, $sentenceCorrection));
        }
    }

    /**
     * @param $term
     * @return String
     */
    public function autoSpellCheck($term)
    {
        return $this->autoSuggest($term, self::AUTO_SPELL_CHECK);
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
     * @return SearchEngine
     */
    public function setResult($result)
    {
        $this->result = $result;
        return $this;
    }

    /**
     * @param $searchResult
     * @return mixed
     */
    public function handleResultCallBacks($searchResult)
    {
        if (!empty($searchResult)) {
            foreach ($this->resultCallBacks as $callable) {
                $arguments = array_merge(array($searchResult), (array)$callable->getMetadata());
                $searchResult = $callable->call($arguments);
            }
        }
        return $searchResult;
    }

    /**
     * @param callback $callable [Also, Accepts Anonymous function]
     * @param array $arguments
     * @return $this
     */
    public function registerResultCallBack($callable, $arguments = [])
    {
        $this->resultCallBacks[] = new CallBackHandler($callable, $arguments);
        return $this;
    }

    /**
     * @return array|CallBackHandler[]
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
        $this->resultCallBacks = [];
        return $this;
    }

    /**
     * @return \ArrayIterator
     */
    public function getIterator(): \ArrayIterator
    {
        return new \ArrayIterator((array)$this->result);
    }

    /**
     * @param $IndexPointerToLoadDB
     * @return bool
     */
    public function has($IndexPointerToLoadDB)
    {
        return (isset($this->loadDB[$IndexPointerToLoadDB]));
    }

    /**
     * @param $IndexPointerToLoadDB
     * @return null|Search|CtrlF
     */
    public function get($IndexPointerToLoadDB)
    {
        if ($this->has($IndexPointerToLoadDB)) {
            return $this->loadDB[$IndexPointerToLoadDB];
        }
        return null;
    }


    /**
     * @param Search $LoadDB
     * @return $this
     */
    public function add(Search $LoadDB)
    {
        $this->loadDB[] = $LoadDB;
        return $this;
    }

    /**
     * @return Search[]
     */
    public function getLoadDB()
    {
        return $this->loadDB;
    }

    /**
     * @param Search[] $loadDB
     * @return $this
     */
    public function setLoadDB($loadDB)
    {
        $this->loadDB = $loadDB;
        return $this;
    }

    /**
     * @param bool|true $resetCallBack
     * @return SearchEngine
     */
    public function reset($resetCallBack = true)
    {
        $this->loadDB = [];
        $this->result = [];
        if ($resetCallBack) $this->resetCallBacks();
        return $this;
    }

    /**
     * @return DictionaryManager
     */
    public function getDictionaryManager()
    {
        return $this->DictionaryManager;
    }

    /**
     * @param DictionaryManager $DictionaryManager
     * @return SearchEngine
     */
    public function setDictionaryManager($DictionaryManager)
    {
        $this->DictionaryManager = $DictionaryManager;
        return $this;
    }

}