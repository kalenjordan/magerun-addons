<?php

namespace KJ\Magento\Command\Developer\Module;

use N98\Magento\Application;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class RemoveCommand extends \N98\Magento\Command\AbstractMagentoCommand
{
    /** @var InputInterface $input */
    protected $_input;

    /** @var OutputInterface $output */
    protected $_output;

    protected function configure()
    {
        $this
            ->setName('dev:module:remove')
            ->addArgument('module-name', InputArgument::REQUIRED, 'The name of the module to remove')
            ->addOption('live-run', null, InputOption::VALUE_REQUIRED, 'Set this to 1 to actually remove module and drop DB tables', false)
            ->setDescription('Uninstall a module, remove template, layout, skin files and dropping DB tables')
        ;
    }

    /**
     * @param \Symfony\Component\Console\Input\InputInterface $input
     * @param \Symfony\Component\Console\Output\OutputInterface $output
     * @return int|void
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->_input = $input;
        $this->_output = $output;
        $this->detectMagento($output, true);
        $this->initMagento();

        $this->_writeModuleDetails();
        $pathsToDelete = $this->_getPathsToDelete();

        $this->getHelper('table')
            ->setHeaders(array('File'))
            ->setRows($pathsToDelete)
            ->render($this->_output);

        if ($this->_isLiveRun()) {
            foreach ($pathsToDelete as $path) {
                $this->_deletePath($path);
            }
        }
    }

    protected function _isLiveRun()
    {
        return ($this->_input->getOption('live-run') == true);
    }

    protected function _getModuleName()
    {
        $moduleName = $this->_input->getArgument('module-name');
        if (!(string)\Mage::getConfig()->getModuleConfig($moduleName)->active) {
            throw new \Exception("Couldn't find module: $moduleName.  Is that a typo?");
        }

        return $moduleName;
    }

    protected function _writeModuleDetails()
    {
        $moduleName = $this->_getModuleName();

        $this->_output->writeln("<info>Remove module $moduleName</info>");
        if ($this->_isLiveRun()) {
            $this->_output->writeln("<info>This is a LIVE run.  Actually removing files and dropping DB tables.</info>");
        } else {
            $this->_output->writeln("<comment>This is just a dry run.  Use --live-run=1 to actually remove files and DB tables.</comment>");
        }

        return $this;
    }

    protected function _getPathsToDelete()
    {
        $paths[] = array(
            'File' => $this->_getPathToModuleConfig(),
        );
        $paths[] = array(
            'File' => $this->_getPathToCode(),
        );

        return $paths;
    }

    protected function _getPathToModuleConfig()
    {
        return 'app/etc/modules/' . $this->_getModuleName() . '.xml';
    }

    protected function _getPathToCode()
    {
        $moduleName = $this->_getModuleName();
        $codePool = (string)\Mage::getConfig()->getModuleConfig($moduleName)->codePool;

        $parts = explode('_', $moduleName);
        if (sizeof($parts) != 2) {
            throw new \Exception("Problem determining namespace and modulename for module: $moduleName");
        }

        $namespace = $parts[0];
        $module = $parts[1];
        $path = "app/code/$codePool/$namespace/$module";

        return $path;
    }

    protected function _deletePath($pathArray)
    {
        $path = $pathArray['File'];
        $absolutePath = $this->_magentoRootFolder . '/' . $path;

        if (!file_exists($absolutePath)) {
            $this->_output->writeln("<comment>This file doesn't exist: $absolutePath - maybe it was already deleted?</comment>");
            return $this;
        }

        if (strlen($absolutePath) < 10) {
            throw new \Exception("This path seems too small: $absolutePath - this is dangerous because rm -rf will be used");
        }

        $this->_output->writeln("<info>Deleting $absolutePath");
        $util = new \KJ\Magento\Util\Shell($this->_input, $this->_output);
        $output = $util->executeShellCommand("rm -rf $absolutePath");

        return $this;
    }
}