<?php

namespace KJ\Magento\Command\Diff;

use N98\Magento\Command\AbstractMagentoCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Lists out modules with some specific logic for community modules
 */
abstract class AbstractCommand extends \KJ\Magento\Command\AbstractCommand
{
    protected function getCurrentVersion()
    {
        $versionCode = $this->detectCurrentVersion();
        $version = new \KJ\Magento\Util\MagentoVersion($versionCode);

        return $version;
    }

    protected function detectCurrentVersion()
    {
        $this->detectMagento($this->_output);
        $this->initMagento();

        $versionNumber = \Mage::getVersion();
        $edition = ($this->_magentoEnterprise ? 'ee' : 'ce');
        if ($this->_input->getOption('pro')) {
            $edition = 'pro';
        }

        $versionCode = $edition . '-' . $versionNumber;

        return $versionCode;
    }
}