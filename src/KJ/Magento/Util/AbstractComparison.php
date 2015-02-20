<?php

namespace KJ\Magento\Util;

class AbstractComparison extends AbstractUtil
{
    /** @var InputInterface $input */
    protected $_input;

    /** @var OutputInterface $output  */
    protected $_output;

    protected $_magentoInstanceRootDirectory;

    protected $_linesOfContext;

    protected $_additionalParameters = '';

    public function __construct($input, $output)
    {
        $this->_input = $input;
        $this->_output = $output;
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

    public function getSummary()
    {
        $filenames = array();

        foreach ($this->_changedLayoutFiles as $comparisonItem) {
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

    public function setAdditionalParameters($paramString)
    {
        $this->_additionalParameters = $paramString;
    }

    public function getAdditionalParameters()
    {
        return $this->_additionalParameters;
    }
}