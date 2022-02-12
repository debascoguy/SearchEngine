<?php

function searchEngineAutoloader($class)
{
    $trimmedClass = substr($class, strlen("SearchEngine"));
    $fullPath = dirname(__FILE__) . DIRECTORY_SEPARATOR . str_replace(["\\", "/"], DIRECTORY_SEPARATOR, $trimmedClass) . '.php';
    if (file_exists($fullPath)) {
        include $fullPath;
        return true;
    }
    return false;
}


if (version_compare(PHP_VERSION, '5.1.2', '>=')) {
    //SPL autoloading was introduced in PHP 5.1.2
    if (version_compare(PHP_VERSION, '5.3.0', '>=')) {
        spl_autoload_register('searchEngineAutoloader', true, true);
    } else {
        spl_autoload_register('searchEngineAutoloader');
    }
} else {
    /**
     * Fall back to traditional autoload for old PHP versions
     * @param string $classname The name of the class to load
     */
    function __autoload($classname)
    {
        searchEngineAutoloader($classname);
    }
}