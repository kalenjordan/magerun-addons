<?php

namespace KJ\Magento\Command\Order\Create;

use N98\Magento\Application;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class DummyCommand extends \N98\Magento\Command\AbstractMagentoCommand
{
    /** @var InputInterface $input */
    protected $_input;

    /** @var OutputInterface $input */
    protected $_output;

    /** @var  \Mage_Customer_Model_Customer $_customer */
    protected $_customer;

    /** @var  \Mage_Catalog_Model_Product $_product */
    protected $_product;

    /** @var  \Mage_Sales_Model_Quote $_quote */
    protected $_quote;

    /* Lazy loading */
    protected $_defaultStoreId;

    /* Supported shipping methods */
    protected $_availableSippingMethods = array('flatrate_flatrate', 'tablerate_bestway');

    protected function configure()
    {
        $this
            ->setName('order:create:dummy')
            ->addArgument('count', InputArgument::REQUIRED, 'Count')
            ->addOption('customer', null, InputOption::VALUE_OPTIONAL, "A customer ID to use for the order")
            ->addOption('product', null, InputOption::VALUE_OPTIONAL, "A product SKU to use for the order")
            ->addOption('store', null, InputOption::VALUE_OPTIONAL, "A store ID to use for the order")
            ->addOption('shipping', null, InputOption::VALUE_OPTIONAL, "A shipping method code to use for the order")
			->addOption('custom_options', null, InputOption::VALUE_OPTIONAL, "Custom options to use for the product, if applicable")
			->addOption('payment', null, InputOption::VALUE_OPTIONAL, "A payment method code to use for the order.  Defaults to 'checkmo'")
			->addOption('cc_token', null, InputOption::VALUE_OPTIONAL, "Token to use for payment method, if applicable")
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
        $this->_output = $output;
        $this->detectMagento($output, true);
        $this->initMagento();

        for ($i = 1; $i <= $input->getArgument('count'); $i++) {
            echo "$i. ";
            try {
                $this->_createOrder();
            } catch (\Exception $e) {
                $this->_output->writeln("<error>Problem creating order: " . $e->getMessage() . "</error>");
            }
            $this->_resetEverything();
        }

    }

    /**
     * Reset the customer, product, quote objects that have been saved
     * so that new ones can be generated for the next iteration.
     */
    protected function _resetEverything()
    {
        unset($this->_customer);
        unset($this->_product);
        unset($this->_quote);
    }

    protected function _createOrder()
    {
        $customer = $this->getCustomer();
        $this->_output->writeln(sprintf("<info>Using customer: %s (%s)</info>", $customer->getName(), $customer->getData('email')));

        $product = $this->getProduct();
        $this->_output->writeln(sprintf("<info>Using product: %s (%s)</info>", $product->getName(), $product->getId()));

        $createdAt = $this->getCreatedAt();
        $this->_output->writeln(sprintf("<info>Using created_at date: %s</info>", $createdAt));

        $order = $this->createOrderFromQuote();
        if ($order) {
            $this->_output->writeln(sprintf("<info>Created order: %s</info>", $order->getIncrementId()));
        }
    }

    /**
     * @return \Mage_Customer_Model_Customer
     */
    protected function getCustomer()
    {
        if (isset($this->_customer)) {
            return $this->_customer;
        }

        if ($this->_input->getOption('customer')) {
            $customer = \Mage::getModel('customer/customer')->load($this->_input->getOption('customer'));
        } else {
            $customer = $this->_loadRandomCustomer();
        }

        // Load customer to make sure we have the full customer information
        $this->_customer = \Mage::getModel('customer/customer')->load($customer->getId());
        return $this->_customer;
    }

    protected function _loadRandomCustomer()
    {
        /** @var \Mage_Customer_Model_Resource_Customer_Collection $customers */
        $customers = \Mage::getModel('customer/customer')->getCollection();
        $customers->setPageSize(1);
        $customers->getSelect()->order(new \Zend_Db_Expr('RAND()'));

        /** @var \Mage_Customer_Model_Customer $customer */
        $customer = $customers->getFirstItem();

        return $customer;
    }

    /**
     * @throws \Exception
     * @return \Mage_Catalog_Model_Product
     */
    protected function getProduct()
    {
        if (isset($this->_product)) {
            return $this->_product;
        }

        $productInput = $this->_input->getOption('product');

        if ($productInput && !preg_match('/%/', $productInput)) {
            /** @var \Mage_Catalog_Model_Product $product */
            $product = \Mage::getModel('catalog/product');
            $product->loadByAttribute('sku', $productInput);
            if (!$product) {
                throw new \Exception("Couldn't find product by SKU: " . $productInput);
            }
            $product = \Mage::getModel('catalog/product')->load($product->getId());
        } else {
            $product = $this->_loadRandomProduct($productInput);
        }

        $this->_product = $product;
        return $this->_product;
    }

    protected function _loadRandomProduct($skuPattern = null)
    {

        // Choose only from simple products so that no configuration has to be made
        /** @var \Mage_Catalog_Model_Resource_Product_Collection $products */
        $products = \Mage::getModel('catalog/product')->getCollection()
                          ->addAttributeToFilter('type_id', \Mage_Catalog_Model_Product_Type::TYPE_SIMPLE);


        $products->setPageSize(1);
        $products->getSelect()->order(new \Zend_Db_Expr('RAND()'));
        if ($skuPattern) {
            $products->getSelect()->where('sku LIKE ?', $skuPattern);
        }

        // Only find random products that are saleable and in stock
        \Mage::getModel('catalog/product_status')->addSaleableFilterToCollection($products);
        \Mage::getModel('cataloginventory/stock')->addInStockFilterToCollection($products);

        if (!$products->getSize()) {
            $errorMessage = 'No products are matching the criteria';
            if ($skuPattern) {
                $errorMessage .= " ($skuPattern)";
            }

            throw new \Exception($errorMessage);
        }

        /** @var \Mage_Catalog_Model_Product $firstResult */
        $firstResult = $products->getFirstItem();
        $product = \Mage::getModel('catalog/product')->load($firstResult->getId());

        return $product;
    }

    protected function getCreatedAt()
    {
        $daysAgo = rand(1, 365 * 2);
        $createdAtTimestamp = time() - $daysAgo * 24 * 60 * 60;
        $createdAtString = date('Y-m-d', $createdAtTimestamp);

        return $createdAtString;
    }

    protected function createOrderFromQuote()
    {
        $quote = $this->getQuote();
        $this->addItemToQuote();
        $this->setupBillingAddress();
        $this->setupShippingAddress();
        $this->setupShippingMethod();
        $this->setupPaymentMethod();
        $this->getQuote()->collectTotals()
            ->save();

        /** @var \Mage_Sales_Model_Service_Quote $service */
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

        /** @var \Mage_Sales_Model_Quote $quote */
        $quote = \Mage::getModel('sales/quote');
        $quote->assignCustomer($this->getCustomer());
        $store = $quote->getStore()->load($this->_getStoreId());
        $quote->setStore($store);

        $this->_quote = $quote;
        return $this->_quote;
    }

    protected function addItemToQuote()
    {
        $product = $this->getProduct();
        $quote = $this->getQuote();

		$request = null;
		if (!$product->getOptionsReadonly()) {
			$customOptions = $this->_getCustomOptions();
			$param = array(
				'product' => $product->getId(),
				'qty' => 1,
				'options' => $customOptions
			);
			$request = new \Varien_Object();
			$request->setData($param);
		}

        /** @var \Mage_Sales_Model_Quote_Item $quoteItem */
		$quoteItem = $quote->addProduct($product, $request);
        if (is_string($quoteItem)) {
            throw new \Exception(sprintf("Error: $quoteItem"));
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
            return $this->getCustomer()->getDefaultShippingAddress();
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
            return $this->getCustomer()->getDefaultShippingAddress();
        } else {
            return $this->getDefaultAddress();
        }
    }

    protected function getDefaultAddress()
    {
        $data = array (
            'firstname' => $this->getCustomer()->getData('firstname'),
            'lastname' => $this->getCustomer()->getData('lastname'),
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
        $shippingMethodCode = $this->_getShippingMethodCode();

        $this->getQuote()->getShippingAddress()->setShippingMethod($shippingMethodCode)
            ->setCollectShippingRates(true)
            ->collectShippingRates();

        return $this;
    }

    protected function setupPaymentMethod()
    {
        $quotePayment = $this->getQuote()->getPayment();

		if ($paymentInput = $this->_input->getOption('payment')) {
			$quotePayment->setMethod($paymentInput);
			if ($ccTokenInput = $this->_input->getOption('cc_token')) {
				\Mage::app()->getRequest()->setPost('payment', array(  //works for Braintree
					'method' => $paymentInput,
					'cc_token' => $ccTokenInput
				));
			}
		} else {
			$quotePayment->setMethod('checkmo');
		}

        $this->getQuote()->setPayment($quotePayment);

        return $this;

    }

    protected function _getDefaultStoreId()
    {
        if (empty($this->_defaultStoreId)) {
            $this->_defaultStoreId = \Mage::app()
                ->getWebsite(true)
                ->getDefaultGroup()
                ->getDefaultStoreId();
        }

        return $this->_defaultStoreId;
    }

    protected function _getStoreId()
    {
        return $this->_input->getOption('store') ? $this->_input->getOption('store') : $this->_getDefaultStoreId();
    }

    protected function _getShippingMethodCode()
    {
        $shippingMethodCode = $this->_input->getOption('shipping');
        if (! $shippingMethodCode) {
            return $this->_availableSippingMethods[0];
        }

        if (!in_array($shippingMethodCode, $this->_availableSippingMethods)) {
            throw new \Exception('Shipping method is not supported.');
        }

        return $shippingMethodCode;
    }

	protected function _getCustomOptions()
	{
		$customOptions = $this->_input->getOption('custom_options');
		$customOptions = explode(',', $customOptions);
		$customOptions = array_combine(range(1, count($customOptions)), $customOptions);
		if (!is_array($customOptions)) {
			throw new \Exception(sprintf("Error: Error parsing custom options"));
		}

		return $customOptions;
	}
}