<?php

/**
 * Created by PhpStorm.
 * User: ademola.aina
 * Date: 11/18/2016
 * Time: 8:19 AM
 */
interface SearchEngine_Interface_Ranking
{
    public function setEditDist($editDist);

    public function getEditDist();

    public function result();
}