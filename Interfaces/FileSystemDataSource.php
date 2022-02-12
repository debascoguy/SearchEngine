<?php

namespace SearchEngine\Interfaces;

interface FileSystemDataSource
{
    /**
     * @return string
     */
    public function getFilePath();

    /**
     * @param string $filePath
     * @return $this
     */
    public function setFilePath($filePath);

    /**
     * @return boolean
     */
    public function isCaseSensitive();

    /**
     * @param boolean $case_sensitive
     * @return $this
     */
    public function setCaseSensitive($case_sensitive);


    /**
     * @return string
     */
    public function getSearchString();

    /**
     * @param string $searchString
     * @return $this
     */
    public function setSearchString($searchString);

    /**
     * @return boolean
     */
    public function isHighlightResult();

    /**
     * @param boolean $highlightResult
     * @return $this
     */
    public function setHighlightResult($highlightResult);

    /**
     * @return string
     */
    public function getHighlightResultColor();

    /**
     * @param string $highlightResultColor
     * @return $this
     */
    public function setHighlightResultColor($highlightResultColor);

    /**
     * @return boolean
     */
    public function isGroupResultByFilePath();

    /**
     * @param boolean $groupByFilePath
     * @return $this
     */
    public function setGroupResultByFilePath($groupByFilePath);

}