<?php

/**
 * Created by PhpStorm.
 * User: element
 * Date: 2/2/2018
 * Time: 4:13 AM
 */
interface SearchEngine_Interface_Search extends IteratorAggregate
{
    /**
     * @return self
     */
    public function search();

    /**
     * @return array
     */
    public function getResult();

}