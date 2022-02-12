<?php

/**
 * @autosuggest ajax request
 */
if (isset($_GET['term'])) {
    $dictionary = new SearchEngine\SearchEngine();
    echo $dictionary->autoSuggest($_GET["term"], $_GET["action"]);
    exit();
}
