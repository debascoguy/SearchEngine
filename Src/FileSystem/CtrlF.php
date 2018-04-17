<?php

/**
 * Class SearchEngine_Src_FileSystem_CtrlF
 */
class SearchEngine_Src_FileSystem_CtrlF extends SearchEngine_Src_FileSystem_SearchDirectory
{
    /**
     * @param SearchEngine_Interface_FileSystemDataSource $SearchOption
     */
    public function __construct(SearchEngine_Interface_FileSystemDataSource $SearchOption)
    {
        parent::__construct($SearchOption);
    }

    /**
     * @return $this
     */
    public function search()
    {
        $filePath = $this->getSearchOption()->getFilePath();
        if (is_dir($filePath))
        {
            parent::search();
        }
        else if (is_file($filePath))
        {
            $searchFile = new SearchEngine_Src_FileSystem_SearchFile($this->getSearchOption());
            $this->count  = $searchFile->search()->count();
            if ($this->count != 0) {
                $this->result = $searchFile->getResult();
            }
        }
        else
        {
            throw new InvalidArgumentException("Invalid FilePath for Search Operation: $filePath");
        }
        return $this;
    }

}