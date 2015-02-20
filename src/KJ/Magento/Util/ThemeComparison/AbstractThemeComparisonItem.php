<?php

namespace KJ\Magento\Util\ThemeComparison;

class AbstractThemeComparisonItem extends \KJ\Magento\Util\AbstractUtil
{
    /**
     * @var \Symfony\Component\Finder\SplFileInfo
     */
    protected $_file;

    protected $_numberOfDifferences = 0;

    /** @var  \KJ\Magento\Util\ThemeComparison */
    protected $_comparison;

    protected $_diffResult;

    /**
     * @param $file \Symfony\Component\Finder\SplFileInfo
     */
    public function __construct($file)
    {
        $this->_file = $file;
        return $this;
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

    public function getFileName()
    {
        return $this->_getType() . '/' . $this->_file->getRelativePathname();
    }

    protected function _getPackageToCompareAgainst()
    {
        $theme = $this->_comparison->getThemeToCompareAgainst();
        $parts = explode('/', $theme);
        return $parts[0];
    }

    protected function _getPackageThemeToCompareAgainst()
    {
        $theme = $this->_comparison->getThemeToCompareAgainst();
        $parts = explode('/', $theme);
        return $parts[1];
    }

    protected function _getAbsoluteFilePathToCompareAgainst()
    {
        // todokj The 'enterprise' needs to be determined dynamically
        $design = \Mage::getSingleton('core/design_package');
        $filename = $design->getFilename($this->_file->getRelativePathname(), array(
            '_area'    => 'frontend',
            '_package' => $this->_getPackageToCompareAgainst(),
            '_theme'   => $this->_getPackageThemeToCompareAgainst(),
            '_type'     => $this->_getType(),
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

    public function getDiff()
    {
        if ($this->_diffResult !== null) {
            return $this->_diffResult;
        }
        $fromFileFullPath = $this->_getAbsoluteFilePathToCompareAgainst();
        $toFileFullPath = $this->_getAbsoluteFilePath();

        $context = $this->_comparison->getLinesOfContext();
        $additionalParameters = $this->_comparison->getAdditionalParameters();
        $lines = $this->_executeShellCommand(sprintf('diff -U%s %s -w %s %s',
            $context, $additionalParameters, $fromFileFullPath, $toFileFullPath));

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
        $this->_diffResult = $lines;

        return $lines;
    }

    public function getNumberOfDifferences()
    {
        $this->getDiff();
        return $this->_numberOfDifferences;
    }

    public function fileToCompareAgainstExists()
    {
        $fromFileFullPath = $this->_getAbsoluteFilePathToCompareAgainst();
        if (!file_exists($fromFileFullPath)) {
            return false;
        }

        return true;
    }
}