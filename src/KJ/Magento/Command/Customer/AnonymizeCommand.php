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

    /** @var OutputInterface $output  */
    protected $_output;

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
        $this->_output = $output;

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

        $tableName = $resource->getTableName($table);
        $records = $connection->fetchAll("
            SELECT $emailColumn
            FROM $tableName
            WHERE $emailColumn NOT LIKE 'test+%'
        ");

        foreach ($records as $record) {
            $this->_anonymizeCustomer($table, $emailColumn, $record);
        }

        return $this;
    }

    protected function _anonymizeCustomer($table, $emailColumn, $record)
    {
        if (!isset($record['email']) || !$record['email']) {
            return $this;
        }

        $resource = \Mage::getSingleton('core/resource');
        $connection = $resource->getConnection('core_write');

        $actualEmail = $record['email'];
        $randomizedEmail = 'test+' . rand(1, 9999999999) . '@example.com';

        foreach ($this->_getTables() as $table => $emailColumn) {
            $this->_anonymizeCustomerForTable($table, $emailColumn, $actualEmail, $randomizedEmail);
        }
    }

    protected function _anonymizeCustomerForTable($table, $emailColumn, $actualEmail, $randomizedEmail)
    {
        $resource = \Mage::getSingleton('core/resource');
        $connection = $resource->getConnection('core_write');
        $tableName = $resource->getTableName($table);

        $query = "
            UPDATE $tableName
            SET $emailColumn = '$randomizedEmail'
            WHERE $emailColumn = '$actualEmail'
        ";

        $this->_output->writeln("<info>Changing $actualEmail to $randomizedEmail in $table");
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