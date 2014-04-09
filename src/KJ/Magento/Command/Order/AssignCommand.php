<?php

namespace KJ\Magento\Command\Order;

use N98\Magento\Application;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class AssignCommand extends \N98\Magento\Command\AbstractMagentoCommand
{
    /** @var InputInterface $input */
    protected $_input;

    /** @var OutputInterface $input */
    protected $_output;

    protected $_order;

    protected function configure()
    {
        $this
            ->setName('order:assign')
            ->addOption('dry-run', null, InputOption::VALUE_OPTIONAL, 'Whether to run as a dry-run or real import', true)
            ->addArgument('order', InputArgument::REQUIRED, 'Order Entity ID or Increment ID')
            ->addArgument('customer', InputArgument::REQUIRED, 'Customer Entity ID')
            ->setDescription('(Experimental) Assign an order to a specific customer.')
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
        $this->_output->writeln("<info>This is experimental and it only modifies the order entity.  \r\nOther modifications may be necessary for full featured support, such as re-ordering, etc.\r\n</info>");

        $customer = $this->_getNewCustomer();
        $order = $this->_getOrder();
        $this->_output->writeln("<info>Order {$order->getIncrementId()} {$order->getCreatedAt()}");
        $this->_assignOrder();
        $this->_saveOrder();
    }

    protected function _getFieldMapping()
    {
        $fieldMapping = array(
            'store_id'              => 'store_id',
            'customer_id'           => 'entity_id',
            'customer_email'        => 'email',
            'customer_firstname'    => 'firstname',
            'customer_lastname'     => 'lastname',
            'customer_prefix'       => 'prefix',
            'customer_middlename'   => 'middlename',
            'customer_suffix'       => 'suffix',
        );
        return $fieldMapping;
    }

    protected function _assignOrder()
    {
        foreach ($this->_getFieldMapping() as $orderField => $customerField) {
            $orderValue = $this->_getOrder()->getData($orderField);
            $customerValue = $this->_getNewCustomer()->getData($customerField);
            if ($orderValue != $customerValue) {
                $this->_output->writeln("<info>Change $orderField from $orderValue to $customerValue</info>");
            } elseif (!$orderValue) {
                $this->_output->writeln("<info>No value for $orderField</info>");
            } else {
                $this->_output->writeln("<info>No change for $orderField: $orderValue</info>");
            }

            $this->_getOrder()->setData($orderField, $customerValue);
        }
    }

    protected function _saveOrder()
    {
        if ($this->_input->getOption('dry-run')) {
            $this->_output->writeln("<info>\r\nJust a dry run, order not saved</info>");
        } else {
            $this->_output->writeln("<info>\r\nSAVING order</info>");
            $this->_getOrder()->save();
        }
    }

    protected function _getOrderId()
    {
        return $this->_input->getArgument('order');
    }

    /**
     * @return \Mage_Customer_Model_Customer
     */
    protected function _getNewCustomer()
    {
        if (isset($this->_customer)) {
            return $this->_customer;
        }

        $customerId = $this->_input->getArgument('customer');
        $customer = \Mage::getModel('customer/customer')->load($customerId);
        if (!$customer->getId()) {
            throw new \Exception("Wasn't able to load customer by entity ID: $customerId");
        }

        $this->_customer = $customer;
        return $this->_customer;
    }

    /**
     * @return \Mage_Sales_Model_Order
     */
    protected function _getOrder()
    {
        if (isset($this->_order)) {
            return $this->_order;
        }

        /** @var \Mage_Sales_Model_Order $order */
        $order = \Mage::getModel('sales/order')->load($this->_getOrderId());
        if ($order->getId()) {
            $this->_output->writeln("<info>Loaded order by entity ID: {$this->_getOrderId()}");
        } else {
            $order = \Mage::getModel('sales/order')->loadByIncrementId($this->_getOrderId());
            if ($order->getId()) {
                $this->_output->writeln("<info>Loaded order by increment ID: {$this->_getOrderId()}");
            } else {
                throw new Exception("Wasn't able to load order by entity ID or increment ID: " . $this->_getOrderId());
            }
        }

        $this->_order = $order;
        return $this->_order;
    }
}