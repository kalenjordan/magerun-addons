MageRun Addons
==============

Some additional comands for the excellent N98-MageRun Magento command-line tool.  

Installation
----------
1. Add the repository to your `composer.json` file under the `require` node.

        "kalenjordan/magerun-addons": "dev-master"
    
    to your `composer.json` file.

2. Add the custom commands to your ~/.n98-magerun.yaml

        commands:
           customCommands:
               - \KJ\Magento\Command\Design\RefreshCommand

