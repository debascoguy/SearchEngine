<?php

namespace SearchEngine\Interfaces;

interface Ranking
{
    public function setEditDist($editDist);

    public function getEditDist();

    public function result();
}