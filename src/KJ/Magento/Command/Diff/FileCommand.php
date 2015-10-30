<?php

namespace KJ\Magento\Command\Diff;

use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use KJ\Magento\Util\Comparison\Item;

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
            ->addOption('line-numbers', null, InputOption::VALUE_OPTIONAL, 'Include the line numbers in diff summary', false)
            ->addOption('pro', null, InputOption::VALUE_OPTIONAL, 'If this parameter is passed, it will check pro instead of ee')
            ->setDescription('Diff the core to see if anything files have been modified.');
    }

    public function execute(InputInterface $input, OutputInterface $output)
    {
        $this->_input = $input;
        $this->_output = $output;

        $version = $this->getCurrentVersion();
        $this->_info(sprintf('Magento Version is %s', $version));

        $comparison = new \KJ\Magento\Util\FileComparison($input, $output);

        if ($this->_input->getOption('lines')) {
            $comparison->setLinesOfContext($this->_input->getOption('lines'));
        }

        $comparison->setMagentoVersion($version)
            ->setMagentoInstanceRootDirectory($this->_magentoRootFolder)
            ->compare();

        if ($input->getArgument('pattern')) {
            $this->_outputComparisonDetails($comparison);
        } else {
            if ($input->getOption('line-numbers')) {
                $this->_outputComparisonSummaryWithLineNumbers($comparison);
            } else {
                $this->_outputComparisonSummary($comparison);
            }
        }
    }

    /**
     * @param $comparison \KJ\Magento\Util\FileComparison
     */
    protected function _outputComparisonSummary($comparison)
    {
        $this->getHelper('table')
            ->setHeaders(array('File'))
            ->setRows($comparison->getSummary())
            ->render($this->_output);
    }

    /**
     * @param $comparison \KJ\Magento\Util\FileComparison
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

    /**
     * @param $comparison \KJ\Magento\Util\FileComparison
     */
    protected function _outputComparisonSummaryWithLineNumbers($comparison)
    {
        /** @var $comparisonItem \KJ\Magento\Util\Comparison\Item */
        foreach ($comparison->getChangedFiles() as $comparisonItem) {
            if ($comparisonItem->isTextFile()) {
                $this->_outputComparisonForFileWithLineNumber($comparisonItem);
            }
        }
    }

    /**
     * @param $comparisonItem \KJ\Magento\Util\Comparison\Item
     * @throws \Exception
     */
    protected function _outputComparisonForFileWithLineNumber($comparisonItem)
    {
        // Will get filled in with a number
        $lineNumber = "XX";

        $lines = $comparisonItem->getDiff();

        foreach ($lines as $line) {
            if (Item\Line::getLineNumber($line)) {
                $lineNumber = Item\Line::getLineNumber($line);
            }

            if (Item\Line::isChange($line)) {
                if (! isset($haveStartedAChangeBlock)) {
                    $this->_output->writeln($comparisonItem->getFileName() . ":" . $lineNumber);
                }
                $haveStartedAChangeBlock = true;
            } else {
                unset($haveStartedAChangeBlock);
            }
        }
    }
}