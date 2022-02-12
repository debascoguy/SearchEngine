<?php

namespace SearchEngine\Src\FileSystem;

use SearchEngine\Interfaces\FileSystemDataSource;

/**
 * Class SearchFile
 */
class SearchFile implements \Countable
{
    /**
     * @var FileSystemDataSource
     */
    protected $SearchOption;

    /**
     * @var array
     */
    protected $result;

    /**
     * @var int
     */
    protected $count = 0;

    /**
     * SearchFile constructor.
     * @param FileSystemDataSource $SearchOption
     */
    public function __construct(FileSystemDataSource $SearchOption = null)
    {
        $this->SearchOption = $SearchOption;
    }

    /**
     * @return FileSystemDataSource
     */
    public function getSearchOption()
    {
        return $this->SearchOption;
    }

    /**
     * @param FileSystemDataSource $SearchOption
     * @return $this
     */
    public function setSearchOption($SearchOption)
    {
        $this->SearchOption = $SearchOption;
        return $this;
    }

    /**
     * @return array
     */
    public function getResult()
    {
        return $this->result;
    }

    /**
     * @param array $result
     * @return $this
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
    public function count(): int
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
     * @return $this
     */
    public function setCount($count)
    {
        $this->count = $count;
        return $this;
    }


    /**
     * @return $this
     * @throws \InvalidArgumentException
     */
    public function search()
    {
        $result = array();
        $count = 0;
        $SearchOption = $this->getSearchOption();
        $filePath = $SearchOption->getFilePath();
        $searchString = $SearchOption->getSearchString();
        $isCaseSensitive = $SearchOption->isCaseSensitive();
        $highlightResult = $SearchOption->isHighlightResult();
        $highlightResultColor = $SearchOption->getHighlightResultColor();
        if ($handle = fopen($filePath, "r")) {
            $line_number = 0;
            while (($lineString = fgets($handle, 4096)) !== false) {
                /** Total number of sub-string occurrence on that line */
                $lastPositionOfOccurrence = ($isCaseSensitive) ? strpos($lineString, $searchString) : stripos($lineString, $searchString);

                /** Highlight Text/Keyword if found */
                if ($lastPositionOfOccurrence !== false) {
                    $totalResult = ($isCaseSensitive) ? substr_count($lineString, $searchString) : substr_count(strtolower($lineString), strtolower($searchString));
                    $found = $highlightResult ? $this->highlightKeyword($lineString, $searchString, $highlightResultColor) : $lineString;

                    if ($SearchOption->isGroupResultByFilePath()) {
                        $result[$filePath]["found"] .= trim(preg_replace('/\s+/', ' ', $found)) . "/n";
                        $result[$filePath]["count"] += $totalResult;
                        $result[$filePath]["filePath"] = $filePath;
                    } else {
                        $result[] = array("found" => $found,
                            "line" => $line_number,
                            "count" => $totalResult,
                            "filePath" => $filePath,
                        );
                    }
                    $count += $totalResult;
                }
                $line_number++;
            }
            fclose($handle);
        } else {
            throw new \InvalidArgumentException("Invalid File for Search Operation: $filePath");
        }

        return $this->setResult($result)->setCount($count);
    }

    /**
     * @param $haystack
     * @param $needle
     * @param string $color
     * @return mixed
     */
    public function highlightKeyword($haystack, $needle, $color = "000000")
    {
        return preg_replace("/($needle)/i", sprintf("<b><span style='color:%s'>$1</span></b>", $color), $haystack);
    }

}