<?php

namespace KJ\Magento\Util;

class AbstractUtil
{
    protected function _outputVerbose($message)
    {
        if (isset($this->_input) && $this->_input->getOption('verbose')) {
            $this->_output->writeln('Verbose: ' . $message);
        }

        return $this;
    }

    protected function _executeShellCommand($command)
    {
        $this->_outputVerbose("Executing $command");
        $command = "$command 2>&1";
        exec($command, $commandOutput, $returnValue);

        if ($returnValue > 1) {
            throw new \Exception(implode(PHP_EOL, $commandOutput));
        }

        return $commandOutput;
    }
}