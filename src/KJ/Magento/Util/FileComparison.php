<?php

namespace KJ\Magento\Util;

class FileComparison extends AbstractComparison
{
    protected $_magentoInstanceRootDirectory;

    /** @var  \KJ\Magento\Util\MagentoVersion */
    protected $_magentoVersion;

    protected $_changedFiles = array();

    public function setMagentoVersion($version)
    {
        $this->_magentoVersion = $version;
        return $this;
    }

    public function getMagentoVersion()
    {
        return $this->_magentoVersion;
    }

    public function getChangedFiles()
    {
        return $this->_changedFiles;
    }

    public function compare()
    {
        $fromDirectory = $this->_magentoVersion->getBaseDir();
        $toDirectory = $this->_magentoInstanceRootDirectory;

        $this->_collectChangedFiles($fromDirectory, $toDirectory, '\KJ\Magento\Util\Comparison\Item');
    }

    protected function _collectChangedFiles($fromDirectory, $toDirectory, $itemClassName)
    {
        $this->_diffOutput = $this->_executeShellCommand(sprintf('LANG=en_US diff -w -x "var" -qrbB %s %s', $fromDirectory, $toDirectory));
        foreach ($this->_diffOutput as $line) {
            $comparisonItem = new $itemClassName($line);
            $comparisonItem->setComparison($this);
            if ($comparisonItem->isDifference()) {
                $this->_changedFiles[] = $comparisonItem;
            }
        }

        return $this;
    }

    public function getSummary()
    {
        $filenames = array();

        foreach ($this->_changedFiles as $comparisonItem) {
            $filenames[] = array(
                'file' => $comparisonItem->getFileName()
            );
        }

        return $filenames;
    }
}
