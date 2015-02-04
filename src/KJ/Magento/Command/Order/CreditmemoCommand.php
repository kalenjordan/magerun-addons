<?php

namespace KJ\Magento\Command\Order;

use N98\Magento\Application;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class CreditmemoCommand extends \N98\Magento\Command\AbstractMagentoCommand
{
    /** @var InputInterface $input */
    protected $_input;

    /** @var OutputInterface $input */
    protected $_output;

    protected $_order;

    protected function configure()
    {
        $this
            ->setName('order:creditmemo')
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
        $this->_output->writeln("<info>Create creditmemo for order.  \r\nOrder with invoice only invoice.\r\n</info>");

        
        $order = $this->_getOrder();
        $this->_output->writeln("<info>Order {$order->getIncrementId()} {$order->getCreatedAt()} payment: {$order->getPayment()->getMethod()} invoice: {$order->hasInvoices()}</info>");
        
        if (!$order->hasInvoices()) {
             $this->_output->writeln("<info>Order {$order->getIncrementId()} {$order->getCreatedAt()} has no invoice.</info>");
             } else {
                 $this->_creditMemo();
            }
    }



    protected function _creditMemo()
    {



$orderId = (int)$this->_getOrderId();
$entityId = \Mage::getModel('sales/order')->loadByIncrementId($this->_getOrderId())->getId();

$order = $this->_getOrder();

$inv = $order->getInvoiceCollection()->getFirstItem();
$service = \Mage::getModel('sales/service_order', $order);

$data = array();
$paymentMethod = $order->getPayment()->getMethod();
if ($paymentMethod == \Mage_Paypal_Model_Config::METHOD_WPP_EXPRESS || $paymentMethod == 'payuplpro') {
    $data["do_offline"] = 0;
} else {
    $data["do_offline"] = 1;
}
$data["qtys"] = array();

foreach ($inv->getAllItems() as $item) {
    $data['qtys'][$item->getOrderItemId()] = $item->getQty();
}

if ($order->getShippingRefunded()) {
    $data['shipping_amount'] = 0;
}

$creditmemo = $service->prepareInvoiceCreditmemo($inv, $data);
$creditmemo->register();
$transactionSave = \Mage::getModel('core/resource_transaction')
        ->addObject($creditmemo)
        ->addObject($creditmemo->getOrder());
if ($creditmemo->getInvoice()) {
    $transactionSave->addObject($creditmemo->getInvoice());
}
$transactionSave->save();
\Mage::getModel('sales/order_creditmemo')->_setNextCustomId($creditmemo);
$this->_output->writeln("<info>Creditmemo created.</info>");
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