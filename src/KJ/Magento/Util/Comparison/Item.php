<?php

namespace KJ\Magento\Util\Comparison;

class Item extends \KJ\Magento\Util\AbstractUtil
{
    protected $_rawLine;

    /** @var  \KJ\Magento\Util\Comparison */
    protected $_comparison;

    public function __construct($rawLine)
    {
        $this->_rawLine = $rawLine;
        return $this;
    }

    public function isDifference()
    {
        if (strpos($this->_rawLine, 'Files ') === 0) {
            return true;
        }

        return false;
    }

    /**
     * Converts this
     *
     *     Files /Users/kalenj/.n98-magerun/version/ce-1.8.0.0/.htaccess and /kj/mageupdate/site/50/.htaccess differ
     *
     * into this:
     *
     *     .htaccess
     *
     * @return string
     */
    public function getFileName()
    {
        $magentoVersionBaseDirectory = $this->_comparison->getMagentoVersion()->getBaseDir();
        $fileNamePosition = strpos($this->_rawLine, $magentoVersionBaseDirectory);
        $fileNamePosition += strlen($magentoVersionBaseDirectory) + 1;

        $fileName = substr($this->_rawLine, $fileNamePosition);
        $fileName = substr($fileName, 0, strpos($fileName, ' and '));

        return $fileName;
    }

    public function setComparison($comparison)
    {
        $this->_comparison = $comparison;
    }
}