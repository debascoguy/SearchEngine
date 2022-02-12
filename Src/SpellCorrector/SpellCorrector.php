<?php

namespace SearchEngine\SpellCorrector;

/**
 * This class implements the Spell correcting feature, useful for the
 * "Did you mean" functionality on the search engine. Using a dictionary of words
 * extracted from the product catalog.
 *
 * Based on the concepts of Peter Norvig: http://norvig.com/spell-correct.html
 *
 */
class SpellCorrector
{
    private static $NWORDS;

    /**
     * Reads a text and extracts the list of words
     *
     * @param string $text
     * @return array The list of words
     */
    private static function  words($text)
    {
        $matches = array();
        preg_match_all("/[a-z]+/", strtolower($text), $matches);
        return $matches[0];
    }

    /**
     * Creates a table (dictionary) where the word is the key and the value is it's relevance
     * in the text (the number of times it appear)
     *
     * @param array $features
     * @return array
     */
    private static function train(array $features)
    {
        $model = array();
        $count = count($features);
        for ($i = 0; $i < $count; $i++) {
            $f = $features[$i];
            $model[$f] += 1;
        }
        return $model;
    }

    /**
     * Generates a list of possible "disturbances" on the passed string
     *
     * @param string $word
     * @return array
     */
    private static function edits1($word)
    {
        $alphabet = 'abcdefghijklmnopqrstuvwxyz';
        $alphabet = str_split($alphabet);
        $n = strlen($word);
        $edits = array();
        for ($i = 0; $i < $n; $i++) {
            $edits[] = substr($word, 0, $i) . substr($word, $i + 1);        //deleting one char
            foreach ($alphabet as $c) {
                $edits[] = substr($word, 0, $i) . $c . substr($word, $i + 1); //substituting one char
            }
        }
        for ($i = 0; $i < $n - 1; $i++) {
            $edits[] = substr($word, 0, $i) . $word[$i + 1] . $word[$i] . substr($word, $i + 2); //swapping chars order
        }
        for ($i = 0; $i < $n + 1; $i++) {
            foreach ($alphabet as $c) {
                $edits[] = substr($word, 0, $i) . $c . substr($word, $i); //inserting one char
            }
        }
        return $edits;
    }

    /**
     * Generate possible "disturbances" in a second level that exist on the dictionary
     *
     * @param string $word
     * @return array
     */
    private static function known_edits2($word)
    {
        $known = array();
        foreach (self::edits1($word) as $e1) {
            foreach (self::edits1($e1) as $e2) {
                if (array_key_exists($e2, self::$NWORDS)) $known[] = $e2;
            }
        }
        return $known;
    }

    /**
     * Given a list of words, returns the subset that is present on the dictionary
     *
     * @param array $words
     * @return array
     */
    private static function known(array $words)
    {
        $known = array();
        foreach ($words as $w) {
            if (array_key_exists($w, self::$NWORDS)) {
                $known[] = $w;
            }
        }
        return $known;
    }


    /**
     * Returns the word that is present on the dictionary that is the most similar (and the most relevant) to the
     * word passed as parameter,
     *
     * @param string $word
     * @return string
     */
    public static function correct($word)
    {
        $word = trim($word);
        if (empty($word)) return;

        $word = strtolower($word);

        $dirname = dirname(__FILE__);

        if (!file_exists($dirname . '/serialized_dictionary.txt')) {
            self::$NWORDS = self::train(self::words(file_get_contents($dirname . "/big.txt")));
            $fp = fopen($dirname . "/serialized_dictionary.txt", "w+");
            fwrite($fp, serialize(self::$NWORDS));
            fclose($fp);
        } else {
            self::$NWORDS = unserialize(file_get_contents($dirname . "/serialized_dictionary.txt"));
        }

        $candidates = array();
        if (self::known(array($word))) {
            return $word;
        } elseif (($tmp_candidates = self::known(self::edits1($word)))) {
            foreach ($tmp_candidates as $candidate) {
                $candidates[] = $candidate;
            }
        } elseif (($tmp_candidates = self::known_edits2($word))) {
            foreach ($tmp_candidates as $candidate) {
                $candidates[] = $candidate;
            }
        } else {
            return $word;
        }
        $max = 0;
        foreach ($candidates as $c) {
            $value = self::$NWORDS[$c];
            if ($value > $max) {
                $max = $value;
                $word = $c;
            }
        }
        return $word;
    }


}

