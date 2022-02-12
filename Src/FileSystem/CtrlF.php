<?php

namespace SearchEngine\Src\FileSystem;

use SearchEngine\Interfaces\FileSystemDataSource;

/**
 * Class CtrlF
 */
class CtrlF extends SearchDirectory
{
    /**
     * @param FileSystemDataSource $SearchOption
     */
    public function __construct(FileSystemDataSource $SearchOption)
    {
        parent::__construct($SearchOption);
    }

    /**
     * @return $this
     * @throws \InvalidArgumentException
     */
    public function search()
    {
        $filePath = $this->getSearchOption()->getFilePath();
        if (is_dir($filePath)) {
            parent::search();
        } else if (is_file($filePath)) {
            $searchFile = new SearchFile($this->getSearchOption());
            $this->count = $searchFile->search()->count();
            if ($this->count != 0) {
                $this->result = $searchFile->getResult();
            }
        } else {
            throw new \InvalidArgumentException("Invalid FilePath for Search Operation: $filePath");
        }
        return $this;
    }

}