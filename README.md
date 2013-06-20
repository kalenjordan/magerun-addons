MageRun Addons
==============

Some additional comands for the excellent N98-MageRun Magento command-line tool.  

Installation
------------
1. Add the repository to your `composer.json` file under the `require` node.

        "kalenjordan/magerun-addons": "dev-master"
    
    to your `composer.json` file.

2. Add the custom commands to your ~/.n98-magerun.yaml

        commands:
           customCommands:
               - \KJ\Magento\Command\Design\RefreshCommand


Commands
--------

### Bust Frontend Browser Caches ###

This command modifies the skin and js base URLs with a timestamp-specific URL, so that browsers will pull 
down fresh CSS and JS.

    $ mr  design:refresh

It's intended to be used in conjunction with a web server rewrite rule that will rewrite, for example:
       
    /<timestamp>/skin/...
    
to

    /skin/...
