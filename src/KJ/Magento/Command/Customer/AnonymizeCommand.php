<?php

namespace KJ\Magento\Command\Customer;

use N98\Magento\Application;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class AnonymizeCommand extends \N98\Magento\Command\AbstractMagentoCommand
{
    /** @var InputInterface $input */
    protected $_input;

    protected function configure()
    {
        $this
            ->setName('customer:anon')
            ->setDescription('Strip all email addresses from customer tables.')
        ;
    }

    /**
     * @param \Symfony\Component\Console\Input\InputInterface $input
     * @param \Symfony\Component\Console\Output\OutputInterface $output
     * @return int|void
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->_input = $input;
        $this->detectMagento($output, true);
        $this->initMagento();

        $tables = $this->_getTables();
        foreach ($tables as $table => $emailColumn) {
            $output->writeln("<info>Anonymizing $table</info>");
            $this->_anonymizeTable($table, $emailColumn);
        }
    }

    protected function _anonymizeTable($table, $emailColumn)
    {
        $resource = \Mage::getSingleton('core/resource');
        $connection = $resource->getConnection('core_write');

        $query = "
            UPDATE " . $resource->getTableName($table) . "
            SET " . $emailColumn . " = CONCAT('test+',RAND(),'@example.com')
        ";
        $connection->query($query);

        return $this;
    }

    protected function _getTables()
    {
        return array(
            'customer/entity'       => 'email',
            'sales/order'           => 'customer_email',
            'sales/order_address'   => 'email',
            'sales/quote'           => 'customer_email',
            'sales/quote_address'   => 'email',
            'newsletter/subscriber' => 'subscriber_email',
        );
    }
}