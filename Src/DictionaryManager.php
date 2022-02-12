<?php

namespace SearchEngine;

use SearchEngine\SpellCorrector\SpellCorrector;

/**
 * Class SearchEngine_Src_DictionaryManager
 */
class DictionaryManager
{
    /**
     * @var \ArrayIterator
     */
    protected $searchEngineConfig;

    /**
     * @var string
     */
    private $directory;

    /**
     * @var string
     */
    private $extension = "txt";

    /**
     * @method pspell_new_config
     */
    private $pspell_dictionary;

    public function __construct()
    {
        $this->setSearchEngineConfig(new \ArrayIterator((array)include_once dirname(dirname(__FILE__)) . "/config/config.php"));
        $this->setDirectory($this->getSearchEngineConfig()->offsetGet("dictionary"))->setExtension("txt");
    }

    /**
     * @return \ArrayIterator
     */
    public function getSearchEngineConfig()
    {
        return $this->searchEngineConfig;
    }

    /**
     * @param \ArrayIterator $searchEngineConfig
     * @return DictionaryManager
     */
    public function setSearchEngineConfig($searchEngineConfig)
    {
        $this->searchEngineConfig = $searchEngineConfig;
        return $this;
    }

    /**
     * @return string
     */
    public function getExtension()
    {
        return $this->extension;
    }

    /**
     * @param string $extension
     * @return DictionaryManager
     */
    public function setExtension($extension)
    {
        $this->extension = $extension;
        return $this;
    }

    /**
     * @return mixed
     */
    public function getDirectory()
    {
        return $this->directory;
    }

    /**
     * @param mixed $directory
     * @return DictionaryManager
     */
    public function setDirectory($directory)
    {
        $this->directory = $directory;
        return $this;
    }


    /**
     * @param int $type
     * @return array|null|string
     */
    public function load($type = 0)
    {
        $dictionary = null;
        switch ($type) {
            case 1:
                foreach (glob($this->getDirectory() . "/*." . $this->extension) as $file) {
                    $path_parts = pathinfo($file);
                    if (is_file($file)) {
                        $dictionary[$path_parts['filename']] = file_get_contents($file);
                    }
                }
                break;

            case 2:
                foreach (glob($this->getDirectory() . "/*." . $this->extension) as $file) {
                    if (is_file($file)) {
                        $dictionary .= file_get_contents($file) . " ";
                    }
                }
                break;

            default:
                $dictionary = array();
                foreach (glob($this->getDirectory() . "/*." . $this->extension) as $file) {
                    $path_parts = pathinfo($file);
                    if (is_file($file)) {
                        $dictionary[$path_parts['filename']] = file($file,
                            FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
                    }
                }
                break;
        }
        return $dictionary;
    }

    /**
     * @param $letter
     * @return mixed
     */
    public function loadByFirstLetter($letter)
    {
        $file = $this->getDirectory() . "/" . $letter . "." . $this->extension;
        if (file_exists($file)) {
            $dictionaryWords = file($file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
            return $dictionaryWords;
        }
        return null;
    }

    /**
     * @param $word
     * @return bool
     */
    public function addWord($word)
    {
        $content = $this->loadByFirstLetter($word[0]);
        if ($content != null) {
            /** if *word* already exist, return TRUE */
            if (in_array(strtolower($word), array_map('strtolower', $content))) {
                return true;
            }
            $content[] = $word;
            sort($content);
            $content = array_unique($content);
            $fullpath = $this->getDirectory() . "/" . $word[0] . "." . $this->extension;
            $fp = fopen($fullpath, "w");
            foreach ($content as $oWord) {
                fwrite($fp, $oWord . "\r\n");
            }
            return fclose($fp);
        }
        return false;
    }

    /**
     * @param array $words
     * @return bool
     */
    public function addWordsArray(array $words)
    {
        $words = array_filter($words);
        $words = array_unique($words);
        foreach ($words as $singleWord) {
            $this->addWord($singleWord);
        }
        return true;
    }

    /**
     * @param $word
     * @return bool
     */
    public function removeWord($word)
    {
        $content = $this->loadByFirstLetter($word[0]);
        if ($content != null) {
            if (($key = array_search($word, $content)) !== false) {
                unset($content[$key]);
            }
            $fullpath = $this->getDirectory() . "/" . $word[0] . "." . $this->extension;
            $fp = fopen($fullpath, "w");
            foreach ($content as $oWord) {
                fwrite($fp, $oWord . "\r\n");
            }
            return fclose($fp);
        }
        return false;
    }

    /**
     * @param array $words
     * @return bool
     */
    public function removeWordsArray(array $words)
    {
        $words = array_unique(array_filter($words));
        foreach ($words as $singleWord) {
            $this->removeWord($singleWord);
        }
        return true;
    }

    /**
     * Load from another source file and Add it to the Standard Dictionary.
     * @param null $filepath : Source File
     * @return bool
     */
    public function loadAndAdd($filepath = null)
    {
        if (!is_null($filepath) && file_exists($filepath)) {
            $content = file($filepath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
            return $this->addWordsArray($content);
        }
        return false;
    }

    /**
     * @param $word
     * @param string $str_concat
     * @param int $resultLimit
     * @return array
     */
    public function autoSuggest($word, $str_concat = "", $resultLimit = 3)
    {
        $found = array();
        $file = $this->getDirectory() . "/" . $word[0] . "." . $this->extension;
        $possible_keywords = file($file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        foreach ($possible_keywords as $line_number => $lineWord) {
            $value = substr($lineWord, 0, strlen($word));
            if (strcasecmp($value, $word) == 0) {
                for ($i = 0; $i < $resultLimit; $i++) {
                    $found[] = array(
                        "label" => $str_concat . " " . strtolower($possible_keywords[$line_number + $i])
                    );
                }
                break;
            }
        }
        return $found;
    }

    /**
     * @param $word
     * @return bool
     */
    public function wordExist($word)
    {
        $content = $this->loadByFirstLetter($word[0]);
        return in_array(strtolower($word), array_map('strtolower', $content));
    }

    /**
     * @param $word
     * @return string
     */
    public function autoCorrectWord($word)
    {
        return SpellCorrector::correct($word);
    }


    /**
     * @param $sentence
     * @return null|string
     */
    public function autoSpellCheck($sentence)
    {
        $replacement_suggest = false;
        $paragraph = explode(" ", trim(preg_replace('/\s+/', ' ', $sentence)));
        $checkedWords = array();
        foreach ($paragraph as $key => $word) {
            $word = trim($word);
            $word_exists = $this->wordExist($word);
            if ($word_exists) {
                $checkedWords[$key] = $word;
            } else {
                $new_word = trim($this->autoCorrectWord($word));
                $checkedWords[$key] = $new_word;
                if ($new_word != $word) {
                    $replacement_suggest = true;
                }
            }
        }

        if ($replacement_suggest) {
            // We have a suggestion, so we return to the data.
            return implode(' ', $checkedWords);
        } else {
            return null;
        }
    }

    /**
     * @param $string
     * @return null|string
     */
    public function pspell_orthograph($string)
    {
        $dictionary = $this->getPspellDictionary();

        // To find out if a replacement has been suggested
        $replacement_suggest = false;

        $string = explode(" ", trim(preg_replace('/\s+/', ' ', str_replace(',', ' ', $string))));
        foreach ($string as $key => $value) {
            if (!pspell_check($dictionary, $value)) {
                $suggestion = pspell_suggest($dictionary, $value);

                // Suggestions are case sensitive. Grab the first one.
                if (strtolower($suggestion [0]) != strtolower($value)) {
                    $string [$key] = $suggestion [0];
                    $replacement_suggest = true;
                }
            }
        }

        if ($replacement_suggest) {
            // We have a suggestion, so we return to the data.
            return implode(' ', $string);
        } else {
            return null;
        }
    }

    /**
     * @return int
     */
    public function pspell_config()
    {
        //Suggests possible words in case of misspelling
        $config_dic = pspell_config_create('en');

        //Add a Custom Dictionary
//    pspell_config_personal($config_dic, 'dictionary/en-US/en-US.dic');

        // Ignore words under 3 characters
        pspell_config_ignore($config_dic, 3);

        // Configure the dictionary
        pspell_config_mode($config_dic, PSPELL_FAST);

        return pspell_new_config($config_dic);
    }

    /**
     * @return mixed
     */
    public function getPspellDictionary()
    {
        if (empty($this->pspell_dictionary)) {
            $this->setPspellDictionary($this->pspell_config());
        }
        return $this->pspell_dictionary;
    }

    /**
     * @param mixed $pspell_dictionary
     * @return DictionaryManager
     */
    public function setPspellDictionary($pspell_dictionary)
    {
        $this->pspell_dictionary = $pspell_dictionary;
        return $this;
    }

}
