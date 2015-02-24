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

        if ($this->_isFileNameLine($this->_rawLine)) {
            return false;
        }

        return true;
    }

    public function isRemovalLine()
    {
        if (substr($this->_rawLine, 0, 1) != '-') {
            return false;
        }

        if ($this->_isFileNameLine($this->_rawLine)) {
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

}