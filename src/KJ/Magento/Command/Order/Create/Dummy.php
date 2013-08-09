<?php

namespace KJ\Magento\Command\Order\Create;

use N98\Magento\Application;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class Dummy extends \N98\Magento\Command\AbstractMagentoCommand
{
    /** @var InputInterface $input */
    protected $_input;

    protected $_customer;
    protected $_product;
    protected $_quote;

    protected function configure()
    {
        $this
            ->setName('order:create:dummy')
            ->setDescription('(Experimental) Create a dummy order using a random customer, product, and date.')
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

        $customer = $this->getCustomer();
        $output->writeln(sprintf("<info>Using customer: %s (%s)</info>", $customer->getName(), $customer->getEmail()));

        $product = $this->getProduct();
        $output->writeln(sprintf("<info>Using product: %s (%s)</info>", $product->getName(), $product->getId()));

        $createdAt = $this->getCreatedAt();
        $output->writeln(sprintf("<info>Using created_at date: %s</info>", $createdAt));

        $order = $this->createOrder();
        $output->writeln(sprintf("<info>Created order: %s</info>", $order->getId()));
    }

    /**
     * @return \Mage_Customer_Model_Customer
     */
    protected function getCustomer()
    {
        if (isset($this->_customer)) {
            return $this->_customer;
        }

        /** @var \Mage_Customer_Model_Resource_Customer_Collection $customers */
        $customers = \Mage::getModel('customer/customer')->getCollection()
            ->setPageSize(1);
        $customers->getSelect()->order(new \Zend_Db_Expr('RAND()'));

        /** @var \Mage_Customer_Model_Customer $customer */
        $customer = $customers->getFirstItem();
        $this->_customer = \Mage::getModel('customer/customer')->load($customer->getId());

        return $this->_customer;
    }

    /**
     * @return \Mage_Catalog_Model_Product
     */
    protected function getProduct()
    {
        if (isset($this->_product)) {
            return $this->_product;
        }

        /** @var \Mage_Catalog_Model_Resource_Product_Collection $products */
        $products = \Mage::getModel('catalog/product')->getCollection()
            ->setPageSize(1);
        $products->getSelect()->order(new \Zend_Db_Expr('RAND()'));

        /** @var \Mage_Catalog_Model_Product $firstResult */
        $firstResult = $products->getFirstItem();
        $this->_product = \Mage::getModel('catalog/product')->load($firstResult->getId());

        return $this->_product;
    }

    protected function getCreatedAt()
    {
        $daysAgo = rand(1, 365 * 2);
        $createdAtTimestamp = time() - $daysAgo * 24 * 60 * 60;
        $createdAtString = date('Y-m-d', $createdAtTimestamp);

        return $createdAtString;
    }

    protected function createOrder()
    {
        $quote = $this->getQuote();
        $this->addItemToQuote();
        $this->setupBillingAddress();
        $this->setupShippingAddress();
        $this->setupShippingMethod();
        $this->setupPaymentMethod();
        $this->getQuote()->collectTotals();

        $service = \Mage::getModel('sales/service_quote', $quote);
        $order = $service->submitOrder();
        $order->setCreatedAt($this->getCreatedAt());
        $order->save();

        return $order;
    }

    /**
     * @return \Mage_Sales_Model_Quote
     */
    protected function getQuote()
    {
        if (isset($this->_quote)) {
            return $this->_quote;
        }

        $quote = \Mage::getModel('sales/quote')->assignCustomer($this->getCustomer());
        $storeId = $this->getCustomer()->getStore()->getId();
        $store = $quote->getStore()->load($storeId);
        $quote->setStore($store);

        $this->_quote = $quote;
        return $this->_quote;
    }

    protected function addItemToQuote()
    {
        $product = $this->getProduct();
        $quote = $this->getQuote();

        /** @var Mage_Sales_Model_Quote_Item $quoteItem */
        $quoteItem = $quote->addProduct($product);
        if (is_string($quoteItem)) {
            throw new \Exception(sprintf("Error: $quoteItem (product ID: %s, Item ID: %s)", $product->getId(), $item->getId()));
        }

        $quoteItem->setQuote($quote);
        $quoteItem->checkData();

        return $this;
    }

    protected function setupBillingAddress()
    {
        $quoteBillingAddress = new \Mage_Sales_Model_Quote_Address();
        $quoteBillingAddress->importCustomerAddress($this->getCustomerBillingAddress());
        $this->getQuote()->setBillingAddress($quoteBillingAddress);

        return $this;
    }

    protected function getCustomerShippingAddress()
    {
        if ($this->getCustomer()->getDefaultShippingAddress()) {
            $this->getCustomer()->getDefaultShippingAddress();
        } elseif ($this->getCustomer()->getDefaultBillingAddress()) {
            return $this->getCustomer()->getDefaultBillingAddress();
        } else {
            return $this->getDefaultAddress();
        }
    }

    protected function getCustomerBillingAddress()
    {
        if ($this->getCustomer()->getDefaultBillingAddress()) {
            return $this->getCustomer()->getDefaultBillingAddress();
        } else if ($this->getCustomer()->getDefaultShippingAddress()) {
            $this->getCustomer()->getDefaultShippingAddress();
        } else {
            return $this->getDefaultAddress();
        }
    }

    protected function getDefaultAddress()
    {
        $data = array (
            'firstname' => $this->getCustomer()->getFirstname(),
            'lastname' => $this->getCustomer()->getLastname(),
            'street' => array (
                '0' => '123 Abc Road',
            ),
            'city' => 'Los Angeles',
            'region_id' => '12',
            'region' => 'California',
            'postcode' => '91201',
            'country_id' => 'US',
            'telephone' => '888 888 8888',
        );
        $address = \Mage::getModel('customer/address')->setData($data);
        return $address;
    }

    protected function setupShippingAddress()
    {
        $address = new \Mage_Sales_Model_Quote_Address();
        $address->importCustomerAddress($this->getCustomerShippingAddress());
        $this->getQuote()->setShippingAddress($address);

        return $this;
    }

    protected function setupShippingMethod()
    {
        /** @var Mage_Sales_Model_Quote_Address $shippingAddress */
        $shippingAddress = $this->getQuote()->getShippingAddress();

        $shippingAddress->setShippingMethod('flatrate_flatrate')
            ->setCollectShippingRates(true)
            ->collectShippingRates();

        return $this;
    }

    protected function setupPaymentMethod()
    {
        $quotePayment = $this->getQuote()->getPayment();
        $quotePayment->setMethod('checkmo');
        $this->getQuote()->setPayment($quotePayment);

        return $this;

    }
}