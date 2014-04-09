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

### Assign order to new customer ###

Assign an order to a new customer by ID.

This is very experimental - doesn't modify all of the places that customer data exists on the order
models, such as the shipping and billing address - just the customer name, email, ID on the order
entity.

    $ mr order:assign 10000000001 10
    
Assigns the order #10000000001 to customer ID 10.

### Anonymize customer data ###

Anonymize customer data across a bunch of tables: order, order address, newsletter, quotes,
newsletter subscriber.

    $ mr customer:anon

### Core file diff ###

Diff core files to see if they've been modified

    $ mr diff:files

This just does a simple diff against a fresh copy of the Magento version's
code base.  Need to add support for it to understand overrides such as a
file in app/code/local or lib/.

### Theme diff ###

Diff theme files to see what has been modified.

    $ mr diff:theme customtheme/default default/default
    
See what customizations have been made in your custom theme against the
base theme.

Summary screenshot:
![Image](https://raw.github.com/kalenjordan/magerun-addons/master/docs/img/diff-summary.png)

Details screenshot:
![Image](https://raw.github.com/kalenjordan/magerun-addons/master/docs/img/diff-details.png)


### Grab mailchimp unsubscribes ###

Grab all of the mailchimp unsubscribes to your primary list

    $ mr mailchimp:unsubscribe:list

If you're using Ebizmarts_MageMonkey to manage your Mailchimp integration,
this will allow you to grab a list of all of the unsubscribed emails to
your primary list.

The main purpose for doing this is if you need to import these unsubscribes
somewhere.  The routine will dispatch an event `mailchimp_list_unsubscribe_discovered`
which you can observe in order to handle them.


### Uninstall a module ###

Uninstall a module by deleting all the module's files and removing database tables.

    $ mr dev:module:remove Aitoc_*

NOTE: This is not fully baked yet, at the moment it just deletes the main module config
file and the code directory.  Pretty trivial, but I'm going to add in database tables,
layout files, template files, etc.

Oh and wildcards aren't supported in the module name yet either, but I just had to do
that as an example :)
