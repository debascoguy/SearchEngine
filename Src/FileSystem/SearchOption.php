<?php

/**
 * Class SearchEngine_Src_FileSystem_SearchOption
 */
class SearchEngine_Src_FileSystem_SearchOption implements SearchEngine_Interface_FileSystemDataSource
{
    /**
     * @var string
     */
    protected $filePath;

    /**
     * @var string
     */
    protected $searchString;

    /**
     * @var bool
     */
    protected $caseSensitive = false;

    /**
     * @var bool
     */
    protected $highlightResult = true;

    /**
     * @var string
     */
    protected $highlightResultColor = "0000FF";

    /**
     * @var bool
     */
    protected $groupResultByFilePath = false;

    /**
     * SearchEngine_Src_FileSystem_SearchOption constructor.
     * @param string $filePath
     * @param string $searchString
     * @param bool|false $caseSensitive
     * @param bool|true $highlightResult
     * @param bool|false $groupResultByFilePath
     */
    public function __construct($filePath,
                                $searchString,
                                $caseSensitive = false,
                                $highlightResult = true,
                                $groupResultByFilePath = false
    )
    {
        $this->setFilePath($filePath)
            ->setSearchString($searchString)
            ->setCaseSensitive($caseSensitive)
            ->setHighlightResult($highlightResult)
            ->setGroupResultByFilePath($groupResultByFilePath);
    }


    /**
     * @return string
     */
    public function getFilePath()
    {
        return $this->filePath;
    }

    /**
     * @param string $filePath
     * @return SearchEngine_Src_FileSystem_SearchOption
     */
    public function setFilePath($filePath)
    {
        $this->filePath = $filePath;
        return $this;
    }

    /**
     * @return string
     */
    public function getSearchString()
    {
        return $this->searchString;
    }

    /**
     * @param string $searchString
     * @return SearchEngine_Src_FileSystem_SearchOption
     */
    public function setSearchString($searchString)
    {
        $this->searchString = $searchString;
        return $this;
    }

    /**
     * @return boolean
     */
    public function isCaseSensitive()
    {
        return $this->caseSensitive;
    }

    /**
     * @param boolean $caseSensitive
     * @return SearchEngine_Src_FileSystem_SearchOption
     */
    public function setCaseSensitive($caseSensitive)
    {
        $this->caseSensitive = $caseSensitive;
        return $this;
    }

    /**
     * @return boolean
     */
    public function isHighlightResult()
    {
        return $this->highlightResult;
    }

    /**
     * @param boolean $highlightResult
     * @return SearchEngine_Src_FileSystem_SearchOption
     */
    public function setHighlightResult($highlightResult)
    {
        $this->highlightResult = $highlightResult;
        return $this;
    }

    /**
     * @return string
     */
    public function getHighlightResultColor()
    {
        return $this->highlightResultColor;
    }

    /**
     * @param string $highlightResultColor
     * @return SearchEngine_Src_FileSystem_SearchOption
     */
    public function setHighlightResultColor($highlightResultColor)
    {
        $this->highlightResultColor = $highlightResultColor;
        return $this;
    }

    /**
     * @return boolean
     */
    public function isGroupResultByFilePath()
    {
        return $this->groupResultByFilePath;
    }

    /**
     * @param boolean $groupResultByFilePath
     * @return SearchEngine_Src_FileSystem_SearchOption
     */
    public function setGroupResultByFilePath($groupResultByFilePath)
    {
        $this->groupResultByFilePath = $groupResultByFilePath;
        return $this;
    }

}