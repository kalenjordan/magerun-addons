<?php

namespace KJ\Magento\Util\Comparison;

class Item extends \KJ\Magento\Util\AbstractUtil
{
    protected $_rawLine;
    protected $_numberOfDifferences = 0;

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

    public function matchPattern($pattern)
    {
        $haystack = $this->getFileName();

        if (strpos($pattern, '*') === false) {
            return ($haystack == $pattern);
        }

        $pattern = str_replace('*', '.*', $pattern);
        $pattern = str_replace('/', '\/', $pattern);
        $result = preg_match('/' . $pattern . '/', $haystack);

        return $result;
    }

    public function getDiff()
    {
        $fromFileFullPath = $this->_comparison->getMagentoVersion()->getBaseDir() . '/' . $this->getFileName();
        $toFileFullPath = $this->_comparison->getMagentoInstanceRootDirectory() . '/' . $this->getFileName();

        $context = $this->_comparison->getLinesOfContext();
        $lines = $this->_executeShellCommand(sprintf('LANG=en_US diff -U%s -w %s %s', $context, $fromFileFullPath, $toFileFullPath));
var_dump($lines);

        foreach ($lines as & $line) {
            $comparisonItemLine = new \KJ\Magento\Util\Comparison\Item\Line($line);

            if ($comparisonItemLine->isAdditionLine()) {
                $line = "<info>" . $line . "</info>";
                $this->_numberOfDifferences++;
            }

            if ($comparisonItemLine->isRemovalLine()) {
                $line = "<comment>" . $line . "</comment>";
                $this->_numberOfDifferences++;
            }
        }

        return $lines;
    }
}
