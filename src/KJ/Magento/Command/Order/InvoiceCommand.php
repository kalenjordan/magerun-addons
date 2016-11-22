<?php

namespace KJ\Magento\Command\Order;

use N98\Magento\Application;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class InvoiceCommand extends \N98\Magento\Command\AbstractMagentoCommand
{
    /** @var InputInterface $input */
    protected $_input;

    /** @var OutputInterface $input */
    protected $_output;

    protected $_order;

    protected function configure()
    {
        $this
            ->setName('order:invoice')
            ->addArgument('order', InputArgument::REQUIRED, 'Order Entity ID or Increment ID')
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
        $this->_output->writeln("<info>Create invoice for order.  \r\nRequire order status to be SENT.\r\n</info>");

        
        $order = $this->_getOrder();
        $this->_output->writeln("<info>Order {$order->getIncrementId()} {$order->getCreatedAt()} status: {$order->getState()} @: {$order->getCustomerEmail()}</info>");
        $this->_invoiceOrder();
  
    }



    protected function _invoiceOrder()
    {

        $order = $this->_getOrder();

        if ($order->hasInvoices())
        {
            $this->_output->writeln("<info>Order already has invoice.</info>");
            return;
        }

        if ($order->getState() == 'sent') {

            $invoice = \Mage::getModel('sales/service_order', $order)->prepareInvoice();
            $invoice->register();
            $transaction = \Mage::getModel('core/resource_transaction')
                ->addObject($invoice)
                ->addObject($invoice->getOrder());

            $transaction->save();

            if ($transaction->save()) {
                $this->_output->writeln("<info>Created invoice for order.</info>");
            } else {
                $this->_output->writeln("<info>Create invoice ERROR</info>");
            }

        } else {
            $this->_output->writeln("<info>Order status is not SENT.</info>");
        }


    }

  

    protected function _getOrderId()
    {
        return $this->_input->getArgument('order');
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