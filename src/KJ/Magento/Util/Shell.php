<?php

namespace KJ\Magento\Util;

class Shell extends AbstractUtil
{
    public function __construct($input, $output)
    {
        $this->_input = $input;
        $this->_output = $output;
    }

    public function executeShellCommand($command)
    {
        return $this->_executeShellCommand($command);
    }
}