<?php

namespace KJ\Magento\Util;

class Comparison extends AbstractUtil
{
    /** @var InputInterface $input */
    protected $_input;

    /** @var OutputInterface $output  */
    protected $_output;

    /** @var  \KJ\Magento\Util\MagentoVersion */
    protected $_magentoVersion;

    protected $_magentoInstanceRootDirectory;

    protected $_changedFiles = array();

    protected $_linesOfContext;

    public function __construct($input, $output)
    {
        $this->_input = $input;
        $this->_output = $output;
    }

    public function setMagentoVersion($version)
    {
        $this->_magentoVersion = $version;
        return $this;
    }

    public function getMagentoVersion()
    {
        return $this->_magentoVersion;
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

    public function getChangedFiles()
    {
        return $this->_changedFiles;
    }

    public function compare()
    {
        $fromDirectory = $this->_magentoVersion->getBaseDir();
        $toDirectory = $this->_magentoInstanceRootDirectory;

        $this->_diffOutput = $this->_executeShellCommand(sprintf('diff -w -x "var" -qrbB %s %s', $fromDirectory, $toDirectory));

        foreach ($this->_diffOutput as $line) {
            $comparisonItem = new \KJ\Magento\Util\Comparison\Item($line);
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

    public function setLinesOfContext($lines)
    {
        $this->_linesOfContext = $lines;
    }

    public function getLinesOfContext()
    {
        if (isset($this->_linesOfContext)) {
            return $this->_linesOfContext;
        }

        return 3;
    }
}