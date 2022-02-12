<?php
/**
 * @Author: Ademola Aina
 * User: ademola.aina
 * Date: 7/3/2015
 * Time: 3:51 PM
 */

/**
 * http://www.sitepoint.com/find-correct-misspelled-words-pspell/
 */

$dictionary = null;
function pspell_config()
{
    global $dictionary;

    //Suggests possible words in case of misspelling
    $config_dic = pspell_config_create('en');

    //Add a Custom Dictionary
//    pspell_config_personal($config_dic, 'dictionary/en-US/en-US.dic');

    // Ignore words under 3 characters
    pspell_config_ignore($config_dic, 3);

    // Configure the dictionary
    pspell_config_mode($config_dic, PSPELL_FAST);

    $dictionary = pspell_new_config($config_dic);
}


/**
 * =============================
 * TESTING PSPELL CONFIG.
 * =============================
 */
function test()
{
    global $dictionary;
    echo "Check Spelling Installation";
    if (pspell_check($dictionary, "colour")) {
        echo "This is a valid spelling";
    } else {
        echo "Sorry, wrong spelling";
    }
}


function test2()
{
    echo "Check Spelling Installation";
    if (function_exists('pspell_new')) {
        $pspell_link = pspell_new("en", null, null, PSPELL_FAST | PSPELL_RUN_TOGETHER);
        print_r($pspell_link);
        if (pspell_check($pspell_link, "colour")) {
            echo "This is a valid spelling";
        } else {
            echo "Sorry, wrong spelling";
        }
    } else {
        echo "pspell is not installed";
    }
}


function test3()
{
    include_once("SpellCorrector.php");

    echo SpellCorrector::correct('emplayee');
}
//test();
//test2();
//test3();


/*
 * QUICK NOTE:
 * ===========
 * Add to custom dictionary using:
 * pspell_add_to_personal($dic, "word");
 */