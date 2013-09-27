MageRun Addons
==============

Some additional comands for the excellent N98-MageRun Magento command-line tool.

The purpose of this project is just to have an easy way to deploy new, custom
commands that I need to use in various places.  It's easier for me to do this
than to maintain a fork of n98-magerun, but I'd be happy to merge any of these
commands into the main n98-magerun project if desired.

Installation
------------
1. Add the repository to your `composer.json` file under the `require` node.

        "kalenjordan/magerun-addons": "dev-master"
    
    to your `composer.json` file.

2. Update composer from within your n98-magerun root

       php composer.phar update

3. Add the custom commands to your ~/.n98-magerun.yaml

        commands:
           customCommands:
               - \KJ\Magento\Command\Design\RefreshCommand
               - \KJ\Magento\Command\Order\Create\DummyCommand
               - \KJ\Magento\Command\Customer\AnonymizeCommand


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

### Anonymize customer data ###

Anonymize customer data across a bunch of tables: order, order address, newsletter, quotes,
newsletter subscriber.

    $ mr customer:anon

