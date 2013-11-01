<?php

namespace KJ\Magento\Command\Diff;

use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class FileCommand extends AbstractCommand
{
    protected $_version = null;

    protected function configure()
    {
        parent::configure();
        $this
            ->setName('diff:files')
            ->addArgument('pattern', InputArgument::OPTIONAL, 'List details for any file with a diff that matches on this pattern')
            ->addOption('lines', null, InputOption::VALUE_OPTIONAL, 'The number of lines of context in the diff', 3)
            ->addOption('pro', null, InputOption::VALUE_OPTIONAL, 'If this parameter is passed, it will check pro instead of ee')
            ->setDescription('Diff the core to see if anything files have been modified.');
    }

    public function execute(InputInterface $input, OutputInterface $output)
    {
        $this->_input = $input;
        $this->_output = $output;

        $version = $this->getCurrentVersion();
        $this->_info(sprintf('Magento Version is %s', $version));

        $comparison = new \KJ\Magento\Util\Comparison($input, $output);

        if ($this->_input->getOption('lines')) {
            $comparison->setLinesOfContext($this->_input->getOption('lines'));
        }

        $comparison->setMagentoVersion($version)
            ->setMagentoInstanceRootDirectory($this->_magentoRootFolder)
            ->compare();

        if ($input->getArgument('pattern')) {
            $this->_outputComparisonDetails($comparison);
        } else {
            $this->_outputComparisonSummary($comparison);
        }
    }

    /**
     * @param $comparison \KJ\Magento\Util\Comparison
     */
    protected function _outputComparisonSummary($comparison)
    {
        $this->getHelper('table')
            ->setHeaders(array('File'))
            ->setRows($comparison->getSummary())
            ->render($this->_output);
    }

    /**
     * @param $comparison \KJ\Magento\Util\Comparison
     */
    protected function _outputComparisonDetails($comparison)
    {
        /** @var $comparisonItem \KJ\Magento\Util\Comparison\Item */
        foreach ($comparison->getChangedFiles() as $comparisonItem) {
            if ($comparisonItem->matchPattern($this->_input->getArgument('pattern'))) {
                $this->writeSection($this->_output, $comparisonItem->getFileName());
                $this->_output->write($comparisonItem->getDiff(), true);
            }
        }
    }
}