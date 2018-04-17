<?php

/**
 * Created by PhpStorm.
 * User: element
 * Date: 1/30/2018
 * Time: 10:55 AM
 */
class SearchEngine_SearchEngine implements
    ElementMvc_ServiceManager_ServiceLocatorInterface,
    SearchEngine_Interface_Search
{
    /**
     * @var SearchEngine_Interface_Search[]|array
     */
    protected $loadDB;

    /**
     * @var array
     */
    protected $result = array();

    /**
     * @var SearchEngine_Services_CallBackHandler[]|array
     */
    private $resultCallBacks  = null;

    /**
     * @var SearchEngine_Src_DictionaryManager
     */
    protected $DictionaryManager;

    const AUTO_SUGGEST = 1;
    const AUTO_SPELL_CHECK = 2;


    /**
     * @self
     */
    public function __construct()
    {
        $this->setDictionaryManager(new SearchEngine_Src_DictionaryManager());
    }

    /**
     * @return $this
     */
    public function search()
    {
        $searchResult = array();
        $LoadDBs = $this->getLoadDB();
        $count = count($LoadDBs);
        switch($count)
        {
            case 0 :    $searchResult =    array("No Search Result!");
                        break;
            case 1 :    $searchResult =    $LoadDBs[0]->search()->getResult();
                        break;
            case 2 :    $searchResult =    array_merge($LoadDBs[0]->search()->getResult(),
                                                    $LoadDBs[1]->search()->getResult()
                                        );
                        break;
            case 3 :    $searchResult =    array_merge($LoadDBs[0]->search()->getResult(),
                                                    $LoadDBs[1]->search()->getResult(),
                                                    $LoadDBs[2]->search()->getResult()
                                        );
                        break;
            default: /** @Multiple LoadDB Sources */
                        foreach($LoadDBs as $loadDB){
                            $searchResult = array_merge($searchResult, $loadDB->search()->getResult());
                        }
        }

        if (!empty($this->resultCallBacks) && !empty($searchResult) ){
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
        $editDistance = new SearchEngine_Src_Ranking_EditDistance($str1, $str2);
        return $editDistance->result();
    }

    /**
     * @param $string1
     * @param $string2
     * @param $editDist
     * @return float|int of Final Ranking Result.
     */
    public static function getRanking($string1, $string2, $editDist=0)
    {
        $rankingSystem = new SearchEngine_Src_Ranking_RankingData($string1, $string2, $editDist);
        return $rankingSystem->result();
    }

    /**
     * @param $term
     * @param $action
     * @return ElementMvc_Http_JsonResponse
     */
    public function autoSuggest($term, $action)
    {
        $dictionary = $this->getDictionaryManager();
        switch($action)
        {
            case self::AUTO_SUGGEST :
                $chunkOfTerms = explode(" ", $term);
                $word = array_pop($chunkOfTerms);
                $str_concat = implode(" ", $chunkOfTerms);
                $result = $dictionary->autoSuggest($word, $str_concat);
                return new ElementMvc_Http_JsonResponse($result);

            case self::AUTO_SPELL_CHECK :
                $result = $dictionary->autoSpellCheck($term);
                return new ElementMvc_Http_JsonResponse(array("correction" => $result));

            default :
                $chunkOfTerms = explode(" ", $term);
                $word = array_pop($chunkOfTerms);
                $str_concat = implode(" ", $chunkOfTerms);
                $result = $dictionary->autoSuggest($word, $str_concat);
                $sentenceCorrection = array("correction" => $dictionary->autoSpellCheck($term));
                return new ElementMvc_Http_JsonResponse(array_unshift($result, $sentenceCorrection));
        }
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
     * @return SearchEngine_SearchEngine
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
        if (!empty($searchResult))
        {
            foreach($this->resultCallBacks as $callable)
            {
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
     * @return ArrayIterator
     */
    public function getIterator()
    {
        return new ArrayIterator((array)$this->result);
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
     * @return null|SearchEngine_Interface_Search|SearchEngine_Src_FileSystem_CtrlF
     */
    public function get($IndexPointerToLoadDB)
    {
        if ($this->has($IndexPointerToLoadDB)){
            return $this->loadDB[$IndexPointerToLoadDB];
        }
        return null;
    }


    /**
     * @param SearchEngine_Interface_Search $LoadDB
     * @return $this
     */
    public function add(SearchEngine_Interface_Search $LoadDB)
    {
        $this->loadDB[] = $LoadDB;
        return $this;
    }

    /**
     * @return SearchEngine_Interface_Search[]
     */
    public function getLoadDB()
    {
        return $this->loadDB;
    }

    /**
     * @param SearchEngine_Interface_Search[] $loadDB
     * @return $this
     */
    public function setLoadDB($loadDB)
    {
        $this->loadDB = $loadDB;
        return $this;
    }

    /**
     * @param bool|true $resetCallBack
     * @return $this
     */
    public function reset($resetCallBack = true)
    {
        $this->loadDB = array();
        if ($resetCallBack) $this->resetCallBacks();
        return $this;
    }

    /**
     * @return SearchEngine_Src_DictionaryManager
     */
    public function getDictionaryManager()
    {
        return $this->DictionaryManager;
    }

    /**
     * @param SearchEngine_Src_DictionaryManager $DictionaryManager
     * @return SearchEngine_SearchEngine
     */
    public function setDictionaryManager($DictionaryManager)
    {
        $this->DictionaryManager = $DictionaryManager;
        return $this;
    }

}