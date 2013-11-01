<?php

namespace KJ\Magento\Command\Diff;

use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class ThemeCommand extends AbstractCommand
{
    protected $_version = null;

    protected function configure()
    {
        parent::configure();
        $this
            ->setName('diff:theme')
            ->addArgument('pattern', InputArgument::OPTIONAL, 'List details for any file with a diff that matches on this pattern')
            ->addOption('lines', null, InputOption::VALUE_OPTIONAL, 'The number of lines of context in the diff', 3)
            ->addOption('pro', null, InputOption::VALUE_OPTIONAL, 'If this parameter is passed, it will check pro instead of ee')
            ->setDescription('Diff the theme to detect changes.');
    }

    public function execute(InputInterface $input, OutputInterface $output)
    {
        $this->_input = $input;
        $this->_output = $output;

        $version = $this->getCurrentVersion();
        $this->_info(sprintf('Magento Version is %s', $version));
        $this->_info(sprintf('Current theme is %s', $this->_getCurrentTheme()));

        $comparison = new \KJ\Magento\Util\ThemeComparison($input, $output);

        if ($this->_input->getOption('lines')) {
            $comparison->setLinesOfContext($this->_input->getOption('lines'));
        }

        $layoutFiles = $this->_getLayoutFiles();
        $design = \Mage::getSingleton('core/design_package');
        $filename = $design->getTemplateFilename('page/html/head.phtml', array(
            '_area'    => 'frontend',
            '_package' => 'clean',
            '_theme'   => 'default',
        ));

        //$comparison->setMagentoVersion($version)
        //    ->setMagentoInstanceRootDirectory($this->_magentoRootFolder)
        //    ->compare();

        $this->_outputComparisonSummary($layoutFiles);
    }

    protected function _getCurrentTheme()
    {
        return 'clean/default';
    }

    protected function _getLayoutFiles()
    {
        $themeLayoutDirectory = $this->_magentoRootFolder . '/app/design/frontend/' . $this->_getCurrentTheme() . '/layout';
        $finder = new \Symfony\Component\Finder\Finder();

        $iterator = $finder
            ->files()
            ->name('*.xml')
            ->in($themeLayoutDirectory);

        foreach ($iterator as $file) {
            $layoutFiles[] = array(
                'file' => 'layout/' . $file->getRelativePathname()
            );
        }

        return $layoutFiles;
    }

    /**
     * @param $comparison \KJ\Magento\Util\Comparison
     */
    protected function _outputComparisonSummary($layoutFiles)
    {
        $this->getHelper('table')
            ->setHeaders(array('File'))
            ->setRows($layoutFiles)
            ->render($this->_output);
    }
}