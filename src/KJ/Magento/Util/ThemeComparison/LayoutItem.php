<?php

namespace KJ\Magento\Util\ThemeComparison;

class LayoutItem extends \KJ\Magento\Util\AbstractUtil
{
    /**
     * @var \Symfony\Component\Finder\SplFileInfo
     */
    protected $_file;

    protected $_numberOfDifferences = 0;

    /** @var  \KJ\Magento\Util\Comparison */
    protected $_comparison;

    /**
     * @param $file \Symfony\Component\Finder\SplFileInfo
     */
    public function __construct($file)
    {
        $this->_file = $file;
        return $this;
    }

    public function isDifference()
    {
        if (strpos($this->_rawLine, 'Files ') === 0) {
            return true;
        }

        return false;
    }

    public function getFileName()
    {
        return 'layout/' . $this->_file->getRelativePathname();
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
        $lines = $this->_executeShellCommand(sprintf('diff -U%s -w %s %s', $context, $fromFileFullPath, $toFileFullPath));

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