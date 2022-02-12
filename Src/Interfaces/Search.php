<?php

namespace SearchEngine\Interfaces;

/**
 * @Author: Ademola Aina
 * Email: debascoguy@gmail.com
 * Date: 2/2/2018
 * Time: 4:13 AM
 */
interface Search extends \IteratorAggregate
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