<?php

namespace KJ\Magento\Util;

class ThemeComparison extends AbstractComparison
{
    protected $_currentTheme;

    protected $_themeToCompare;

    protected $_changedLayoutFiles = array();

    protected $_changedTemplateFiles = array();

    public function setCurrentTheme($theme)
    {
        $this->_currentTheme = $theme;
        return $this;
    }

    public function setThemeToCompare($theme)
    {
        $this->_themeToCompare = $theme;
        return $this;
    }

    public function compare()
    {
        $this->_compareLayoutFiles();

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
            $this->_changedLayoutFiles[] = $comparisonItem;
        }

        return $layoutFiles;
    }
}