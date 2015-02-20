<?php

namespace KJ\Magento\Util;

use KJ\Magento\Util\ThemeComparison\LayoutItem;
use KJ\Magento\Util\ThemeComparison\TemplateItem;

class ThemeComparison extends AbstractComparison
{
    protected $_currentTheme;

    protected $_themeToCompareAgainst;

    /**
     * @var LayoutItem[]
     */
    protected $_changedLayoutFiles = array();
    /**
     * @var TemplateItem[]
     */
    protected $_changedTemplateFiles = array();

    protected $_magentoInstanceRootDirectory;

    public function getCurrentTheme()
    {
        return $this->_currentTheme;
    }

    public function setCurrentTheme($theme)
    {
        $this->_currentTheme = $theme;
        return $this;
    }

    public function setThemeToCompareAgainst($theme)
    {
        $this->_themeToCompareAgainst = $theme;
        return $this;
    }

    public function getThemeToCompareAgainst()
    {
        return $this->_themeToCompareAgainst;
    }

    public function getMagentoInstanceRootDirectory()
    {
        return $this->_magentoInstanceRootDirectory;
    }

    public function setMagentoInstanceRootDirectory($directory)
    {
        $this->_magentoInstanceRootDirectory = $directory;
        return $this;
    }

    public function compare()
    {
        $this->_compareLayoutFiles();
        $this->_compareTemplateFiles();

        return $this;
    }

    protected function _compareLayoutFiles()
    {
        $themeLayoutDirectory = $this->_magentoInstanceRootDirectory . '/app/design/frontend/' . $this->_currentTheme . '/layout';
        $finder = new \Symfony\Component\Finder\Finder();

        $iterator = $finder->files()
            ->name('*.xml')
            ->in($themeLayoutDirectory);

        foreach ($iterator as $file) {
            $comparisonItem = new \KJ\Magento\Util\ThemeComparison\LayoutItem($file);
            $comparisonItem->setComparison($this);
            if ($comparisonItem->fileToCompareAgainstExists()) {
                $this->_changedLayoutFiles[] = $comparisonItem;
            }
        }

        return $this;
    }

    protected function _compareTemplateFiles()
    {
        $themeTemplateDirectory = $this->_magentoInstanceRootDirectory
            . '/app/design/frontend/' . $this->_currentTheme . '/template';
        $finder = new \Symfony\Component\Finder\Finder();

        $iterator = $finder->files()
            ->name('*.phtml')
            ->in($themeTemplateDirectory);

        foreach ($iterator as $file) {
            $comparisonItem = new \KJ\Magento\Util\ThemeComparison\TemplateItem($file);
            $comparisonItem->setComparison($this);
            if ($comparisonItem->fileToCompareAgainstExists()) {
                $this->_changedTemplateFiles[] = $comparisonItem;
            }
        }

        return $this;
    }

    public function getChangedFiles()
    {
        return array_merge($this->_changedLayoutFiles, $this->_changedTemplateFiles);
    }

    public function getSummary()
    {
        $filenames = array();

        foreach ($this->_changedLayoutFiles as $comparisonItem) {
            $filenames[] = array(
                'file'        => $comparisonItem->getFileName(),
                'differences' => $comparisonItem->getNumberOfDifferences()
            );
        }

        foreach ($this->_changedTemplateFiles as $comparisonItem) {
            $filenames[] = array(
                'file'        => $comparisonItem->getFileName(),
                'differences' => $comparisonItem->getNumberOfDifferences()
            );
        }

        return $filenames;
    }
}