<?php

namespace KJ\Magento\Command\Order;

use N98\Magento\Application;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class PushCommand extends \N98\Magento\Command\AbstractMagentoCommand
{
    /** @var InputInterface $input */
    protected $_input;

    /** @var OutputInterface $input */
    protected $_output;

    protected $_order;

    protected function configure()
    {
        $this
            ->setName('order:push')
            ->addArgument('order', InputArgument::REQUIRED, 'Order Entity ID or Increment ID')
            ->addOption('received', null, InputOption::VALUE_OPTIONAL, "Change order with status sent to received")
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
        $this->_output->writeln("<info>This is shortcut to change order status.  \r\nFrom New -> Sent for RMA usage.\r\n</info>");

        $order = $this->_getOrder();
        $this->_output->writeln("<info>Order {$order->getIncrementId()} {$order->getCreatedAt()} status: {$order->getState()} @: {$order->getCustomerEmail()}</info>");

        if ($this->_input->getOption('received')) {
            $this->_receiveOrder();
        } else {
            $this->_pushOrder();
        }

    }

    protected function _pushOrder()
{

    $orderId = (int)$this->_getOrderId();
    $entityId = \Mage::getModel('sales/order')->loadByIncrementId($this->_getOrderId())->getId();

    $resource = \Mage::getSingleton('core/resource');
    $writeConnection = $resource->getConnection('core_write');
    $query = "UPDATE sales_flat_order SET `state` = 'exported', `status` = 'exported' WHERE increment_id = {$orderId};UPDATE sales_flat_order_grid SET `status` = 'exported' WHERE increment_id = {$orderId};INSERT INTO `sales_flat_order_status_history` VALUES (NULL, {$entityId},0,0, 'Order status has been set to exported', 'exported', NOW(), 'order');UPDATE sales_flat_order SET `state` = 'in_progress', `status` = 'in_progress' WHERE increment_id = {$orderId};UPDATE sales_flat_order_grid SET `status` = 'in_progress' WHERE increment_id = {$orderId};INSERT INTO `sales_flat_order_status_history` VALUES (NULL, {$entityId},0,0, 'Order status has been set to in_progress', 'in_progress', NOW(), 'order');UPDATE sales_flat_order SET `state` = 'sent', `status` = 'sent' WHERE increment_id = {$orderId};UPDATE sales_flat_order_grid SET `status` = 'sent' WHERE increment_id = {$orderId};INSERT INTO `sales_flat_order_status_history` VALUES (NULL, {$entityId},0,0, 'Order status has been set to sent', 'sent', NOW(), 'order'); UPDATE sales_flat_order_item SET qty_invoiced=qty_ordered,qty_shipped=qty_ordered WHERE order_id={$entityId};";

    if ($writeConnection->query($query)) {
        $this->_output->writeln("<info>Order push success</info>");
    } else {
        $this->_output->writeln("<info>Order push ERROR</info>");
    }

}

    protected function _receiveOrder()
    {

        if ($this->_getOrder()->getState() == 'sent') {
            $comment = "Order status has been set to received.";
            $this->_getOrder()->setState('received','received',$commet,true)->save();
            $this->_output->writeln("<info>Order chcange status to RECEIVED</info>");
            return;
        }
            $this->_output->writeln("<info>Order must be in SENT status.</info>");

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
                throw new \Exception("Wasn't able to load order by entity ID or increment ID: " . $this->_getOrderId());
            }
        }

        $this->_order = $order;
        return $this->_order;
    }
}