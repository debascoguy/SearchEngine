<?php

namespace SearchEngine\Interfaces;

interface CompareString
{
    public function setString1($string);

    public function getString1();

    public function setString2($string);

    public function getString2();
}