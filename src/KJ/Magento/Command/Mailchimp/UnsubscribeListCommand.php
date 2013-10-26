<?php

namespace KJ\Magento\Command\Mailchimp;

use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class UnsubscribeListCommand extends \KJ\Magento\Command\AbstractCommand
{
    protected function configure()
    {
        $this
            ->setName('mailchimp:unsubscribe:list')
            ->addOption('page-size', null, InputOption::VALUE_OPTIONAL, "The number of results pulled at once over Mailchimp API", 1000)
            ->setDescription('Grab a list of unsubscribed email addresses from the primary mailchimp account configured in MageMonkey extension.');
    }

    public function execute(InputInterface $input, OutputInterface $output)
    {
        $this->_input = $input;
        $this->_output = $output;

        $this->detectMagento($output, true);
        $this->initMagento();

        $output->writeln("<info>Initiating Mailchimp API connection</info>");
        $this->_validate();
        $list = $this->_getList();
        $output->writeln("<info>Getting unsubscribes for list {$list['id']}: {$list['name']} using a page size of " . $this->_input->getOption('page-size') . "</info>");

        $members = $this->_getMembers();
        $output->writeln("<info>Found " . $this->_totalResults . " unsubscribes.</info>");
        $this->_pageNumber = 0;

        $i = 1;
        while ($members = $this->_getMembers()) {
            foreach ($members as $member) {
                \Mage::dispatchEvent('mailchimp_list_unsubscribe_discovered', array('email' => $member['email']));
                $output->writeln("<info>$i. " . $member['email'] . "</info>");
                $i++;
            }
        }
    }

    protected function _getApi()
    {
        $api = \Mage::getSingleton('monkey/api');
        return $api;
    }

    protected function _getMembers()
    {
        if (!isset($this->_startPosition)) {
            $this->_startPosition = 0;
        }

        $list = $this->_getList();

        $pageSize = $this->_input->getOption('page-size');
        $members = $this->_getApi()->listMembers($list['id'], 'unsubscribed', null, $this->_pageNumber, $pageSize);
        $this->_pageNumber += 1;
        $this->_totalResults = $members['total'];

        if (!isset($members['data'])) {
            return false;
        }

        return $members['data'];
    }

    protected function _getList()
    {
        if (isset($this->_list)) {
            return $this->_list;
        }

        $listId = \Mage::helper('monkey')->config('list');
        if (!$listId) {
            throw new \Exception("You need to select your primary Mailchimp list");
        }

        $lists = $this->_getApi()->lists(array('list_id' => $listId));
        if (!isset($lists['data'][0])) {
            throw new \Exception("Wasn't able to find list data for list ID: " . $listId);
        }

        $list = $lists['data'][0];

        $this->_list = $list;
        return $list;
    }

    protected function _validate()
    {
        $api = $this->_getApi();
        if ($api->errorMessage) {
            throw new \Exception("Problem with Mailchimp API configuration: " . $api->errorMessage);
        }
    }
}