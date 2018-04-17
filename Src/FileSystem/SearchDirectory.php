<?php

/**'
 * Class SearchEngine_Src_FileSystem_SearchDirectory
 */
class SearchEngine_Src_FileSystem_SearchDirectory implements SearchEngine_Interface_Search, Countable
{
    /**
     * @var SearchEngine_Interface_FileSystemDataSource
     */
    protected $SearchOption;

    /**
     * @var array
     */
    protected $result = array();

    /**
     * @var int
     */
    protected $count = 0;

    /**
     * @param SearchEngine_Interface_FileSystemDataSource $SearchOption
     */
    public function __construct(SearchEngine_Interface_FileSystemDataSource $SearchOption)
    {
        $this->SearchOption = $SearchOption;
    }

    /**
     * @return $this
     */
    public function search()
    {
        $searchOption = $this->getSearchOption();
        /**
         * @var RecursiveDirectoryIterator $recursiveDirectoryIterator
         * Create a recursive iterator that knows how to follow subdirectories.
         */
        $recursiveDirectoryIterator = new RecursiveDirectoryIterator($searchOption->getFilePath(), FilesystemIterator::SKIP_DOTS);
        /**
         * @var RecursiveIteratorIterator $recursiveIteratorIterator
         * Pass our RecursiveDirectoryIterator to the constructor of RecursiveIteratorIterator
         * to enable sub-directories iteration.
         * Also, pass in a 'mode', to specify whether parents should come before children, after children, or not at all.
         * We want parent first, so we just use SELF_FIRST
         */
        $recursiveIteratorIterator = new RecursiveIteratorIterator($recursiveDirectoryIterator, RecursiveIteratorIterator::SELF_FIRST);

        /** Use our RecursiveIteratorIterator as if it was a flat array */
        foreach($recursiveIteratorIterator as $filePath => $info)
        {
            if (is_file($filePath))
            {
                $searchOption->setFilePath($filePath);
                $searchFile = new SearchEngine_Src_FileSystem_SearchFile($searchOption);
                $searchFile->search();
                if ($searchFile->count()!=0)
                {
                    $this->result = array_merge($this->result,$searchFile->getResult());
                    $this->count += $searchFile->count();
                }
            }
        }
        return $this;
    }

    /**
     * @return $this
     */
    public function self_recursive_search()
    {
        $searchOption = $this->getSearchOption();
        $files_dir = glob($searchOption->getFilePath()."/*");
        foreach($files_dir as $file)
        {
            if (is_file($file)) {
                $searchOption2 = $this->getSearchOption();
                $searchOption2->setFilePath($file);
                $searchFile = new SearchEngine_Src_FileSystem_SearchFile($searchOption2);
                $searchFile->search();
                if ($searchFile->count()!=0) {
                    $this->result = array_merge($this->result,$searchFile->getResult());
                    $this->count += $searchFile->count();
                }
            }
            else if (is_dir($file)) {    /** Recursive into any sub-directory (if any). */
                $searchOption2 = $this->getSearchOption();
                $searchOption2->setFilePath($file);
                $searchDirectory = new self($searchOption2);
                $searchDirectory->search();
                $this->result = array_merge($this->result, $searchDirectory->getResult());
                $this->count += $searchDirectory->count();
            }
            else {
                throw new InvalidArgumentException("Invalid Directory for Search Operation: $file");
            }
        }
        return $this;
    }

    /**
     * @return SearchEngine_Interface_FileSystemDataSource
     */
    public function getSearchOption()
    {
        return $this->SearchOption;
    }

    /**
     * @param SearchEngine_Interface_FileSystemDataSource $SearchOption
     * @return SearchEngine_Src_FileSystem_SearchDirectory
     */
    public function setSearchOption($SearchOption)
    {
        $this->SearchOption = $SearchOption;
        return $this;
    }

    /**
     * @return array array
     */
    public function getResult()
    {
        return $this->result;
    }

    /**
     * @param array|ArrayIterator $result
     * @return SearchEngine_Src_FileSystem_SearchDirectory
     */
    public function setResult($result)
    {
        $this->result = $result;
        return $this;
    }

    /**
     * Count elements of an object
     * @link http://php.net/manual/en/countable.count.php
     * @return int The custom count as an integer.
     * </p>
     * <p>
     * The return value is cast to an integer.
     * @since 5.1.0
     */
    public function count()
    {
        return $this->count;
    }

    /**
     * @return int
     */
    public function getCount()
    {
        return $this->count;
    }

    /**
     * @param int $count
     * @return SearchEngine_Src_FileSystem_SearchDirectory
     */
    public function setCount($count)
    {
        $this->count = $count;
        return $this;
    }

    /**
     * @void
     */
    public function sortAsc()
    {
        uasort($this->result, function($a, $b){
            if ($a["count"] == $b["count"]) {
                return 0;
            }
            return ($a["count"] > $b["count"]) ? -1 : 1;
        });
    }

    /**
     * @void
     */
    public function sortDesc()
    {
        uasort($this->result, function($a, $b){
            if ($a["count"] == $b["count"]) {
                return 0;
            }
            return ($a["count"] > $b["count"]) ? 1 : -1;
        });
    }

    /**
     * @return ArrayIterator
     */
    public function getIterator()
    {
        return new ArrayIterator((array)$this->result);
    }

}