<?php
namespace IIA\NeoclickShippingProvider\Model;

/**
 *
 * @author pawelszczepaniak
 *
 */
class NeoclickMagento2
{

    public function __construct(\Magento\Framework\App\Helper\Context $context, \Magento\Store\Model\StoreManagerInterface $storeManager, \Magento\Catalog\Model\ProductFactory $productFactory, \Magento\Quote\Model\QuoteManagement $quoteManagement, \Magento\Customer\Model\CustomerFactory $customerFactory, \Magento\Customer\Api\CustomerRepositoryInterface $customerRepository, \Magento\Sales\Model\Service\OrderService $orderService, \Magento\Quote\Api\CartRepositoryInterface $cartRepositoryInterface, \Magento\Quote\Api\CartManagementInterface $cartManagementInterface, \Magento\Quote\Model\Quote\Address\Rate $shippingRate)
    {
        $this->_storeManager = $storeManager;
        $this->_productFactory = $productFactory;
        $this->quoteManagement = $quoteManagement;
        $this->customerFactory = $customerFactory;
        $this->customerRepository = $customerRepository;
        $this->orderService = $orderService;
        $this->cartRepositoryInterface = $cartRepositoryInterface;
        $this->cartManagementInterface = $cartManagementInterface;
        $this->shippingRate = $shippingRate;
    }

    public function createOrder($data)
    {

        $store = $this->_storeManager->getStore();
        $websiteId = $this->_storeManager->getStore()->getWebsiteId();
        // init the customer
        $customer = $this->customerFactory->create();
        $customer->setWebsiteId($websiteId);
        $customer->loadByEmail($orderData['email']); // load customet by email address
                                                     // check the customer
        if (! $customer->getEntityId()) {
            // If not avilable then create this customer
            $customer->setWebsiteId($websiteId)
                ->setStore($store)
                ->setFirstname($orderData['shipping_address']['firstname'])
                ->setLastname($orderData['shipping_address']['lastname'])
                ->setEmail($orderData['email'])
                ->setPassword($orderData['email']);
            $customer->save();
        }
        // init the quote
        $cart_id = $this->cartManagementInterface->createEmptyCart();
        $cart = $this->cartRepositoryInterface->get($cart_id);
        $cart->setStore($store);
        // if you have already buyer id then you can load customer directly
        $customer = $this->customerRepository->getById($customer->getEntityId());
        $cart->setCurrency();
        $cart->assignCustomer($customer); // Assign quote to customer
                                          // add items in quote
        foreach ($orderData['items'] as $item) {
            $product = $this->_productFactory->create()->load($item['product_id']);
            $cart->addProduct($product, intval($item['qty']));
        }

        $cart->getBillingAddress()->addData($orderData['shipping_address']);
        $cart->getShippingAddress()->addData($orderData['shipping_address']);
        // Collect Rates and Set Shipping & Payment Method
        $this->shippingRate->setCode('freeshipping_freeshipping')->getPrice(1);
        $shippingAddress = $cart->getShippingAddress();
        // @todo set in order data
        $shippingAddress->setCollectShippingRates(true)
            ->collectShippingRates()
            ->setShippingMethod('flatrate_flatrate');
        $cart->getShippingAddress()->addShippingRate($this->shippingRate);
        $cart->setPaymentMethod('checkmo');

        $cart->setInventoryProcessed(false);
        // Set sales order payment
        $cart->getPayment()->importData([
            'method' => 'checkmo'
        ]);
        // Collect total and saeve
        $cart->collectTotals();
        // Submit the quote and create the order
        $cart->save();
        $cart = $this->cartRepositoryInterface->get($cart->getId());
        $order_id = $this->cartManagementInterface->placeOrder($cart->getId());
        return $order_id;
    }

    public function updateOrderStatus()
    {}
}

