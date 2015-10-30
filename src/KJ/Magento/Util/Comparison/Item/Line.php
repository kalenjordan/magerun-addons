<?php

namespace KJ\Magento\Util\Comparison\Item;

class Line extends \KJ\Magento\Util\AbstractUtil
{
    protected $_rawLine;

    /** @var  \KJ\Magento\Util\Comparison\Item */
    protected $_comparisonItem;

    public function __construct($rawLine)
    {
        $this->_rawLine = $rawLine;
        return $this;
    }

    public function isAdditionLine()
    {
        if (substr($this->_rawLine, 0, 1) != '+') {
            return false;
        }

        if ($this->_isFileNameLine()) {
            return false;
        }

        return true;
    }

    public function isRemovalLine()
    {
        if (substr($this->_rawLine, 0, 1) != '-') {
            return false;
        }

        if ($this->_isFileNameLine()) {
            return false;
        }

        return true;
    }

    protected function _isFileNameLine()
    {
        if (substr($this->_rawLine, 0, 3) == '+++') {
            return true;
        }

        if (substr($this->_rawLine, 0, 3) == '---') {
            return true;
        }

        return false;
    }

    /**
     * If the line of the diff is a line number, it parses that out.
     *
     * Example line:
     *
     *     @@ -207,3 +207,5 @@
     *
     * @param $lines
     * @throws \Exception
     */
    public static function getLineNumber($line)
    {
        if (substr($line, 0, 2) != '@@') {
            return null;
        }

        $line = substr($line, 2);

        $parts = explode(",", $line);
        if (! isset($parts[0])) {
            throw new \Exception("Problem splitting the line number line on a comma");
        }

        $lineNumber = trim($parts[0]);
        $lineNumber = -1 * $lineNumber;

        return $lineNumber;
    }

    /**
     * Check whether to see this line represents a change (addition
     * or removal).
     *
     * By this point, the line has already been modified so that if
     * it's an addition it gets wrapped in <info> and if it's a removal
     * it gets wrapped in <comment> (this is because of how color coding
     * works - green vs. red).
     *
     * @param $line
     */
    public static function isChange($line)
    {
        if (substr($line, 0, 5) == "<info") {
            return true;
        }

        if (substr($line, 0, 5) == "<comm") {
            return true;
        }

        return false;
    }
}