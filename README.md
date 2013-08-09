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

Note: I should mention that the URL parsing to generate the URLs needs work.  It supports either a 
URL ending in .com or a URL relative to the base (.e.g. {{base_url}}skin).  

### Create dummy order ###

This is very experimental and has some defaults in it such as the default billing address for a customer
that aren't very international-friendly.

    $ mr order:create:dummy
    
It picks a random customer, random product, and a random order creation date up to two years ago from 
the present time, and creates an order.
