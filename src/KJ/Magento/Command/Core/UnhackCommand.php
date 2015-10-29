<?php

namespace KJ\Magento\Command\Core;

use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class UnhackCommand extends \KJ\Magento\Command\AbstractCommand
{
    protected function configure()
    {
        parent::configure();

        $this->setName('core:unhack')
            ->setDescription('Diff the core and move core hacks to a module.');
    }

    public function execute(InputInterface $input, OutputInterface $output)
    {
        $this->_input = $input;
        $this->_output = $output;
        $this->_execute();
    }

    protected function _execute()
    {
        $this->_info("NEIN! VILL NOT HACK ZE CORE!");
    }
}