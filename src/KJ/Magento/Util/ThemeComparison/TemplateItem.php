<?php

namespace KJ\Magento\Util\ThemeComparison;

class TemplateItem extends \KJ\Magento\Util\AbstractUtil
{
    /**
     * @var \Symfony\Component\Finder\SplFileInfo
     */
    protected $_file;

    protected $_numberOfDifferences = 0;

    /** @var  \KJ\Magento\Util\ThemeComparison */
    protected $_comparison;

    /**
     * @param $file \Symfony\Component\Finder\SplFileInfo
     */
    public function __construct($file)
    {
        $this->_file = $file;
        return $this;
    }

    public function getFileName()
    {
        return 'template/' . $this->_file->getRelativePathname();
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

    protected function _getAbsoluteFilePathToCompareAgainst()
    {
        // todokj The 'enterprise' needs to be determined dynamically
        $design = \Mage::getSingleton('core/design_package');
        $filename = $design->getTemplateFilename($this->_file->getRelativePathname(), array(
            '_area'    => 'frontend',
            '_package' => 'enterprise',
            '_theme'   => 'default',
        ));

        return $filename;
    }

    protected function _getAbsoluteFilePath()
    {
        $path = $this->_comparison->getMagentoInstanceRootDirectory()
            . '/app/design/frontend/' . $this->_comparison->getCurrentTheme()
            . '/' . $this->getFileName();

        return $path;
    }

    public function fileToCompareAgainstExists()
    {
        $fromFileFullPath = $this->_getAbsoluteFilePathToCompareAgainst();
        if (!file_exists($fromFileFullPath)) {
            return false;
        }

        return true;
    }

    public function getDiff()
    {
        $fromFileFullPath = $this->_getAbsoluteFilePathToCompareAgainst();
        $toFileFullPath = $this->_getAbsoluteFilePath();

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