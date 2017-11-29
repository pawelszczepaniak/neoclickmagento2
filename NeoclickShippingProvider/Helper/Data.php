<?php
namespace IIA\NeoclickShippingProvider\Helper;

use \Magento\Framework\App\Helper\AbstractHelper;
use \Magento\Framework\App\ObjectManager;

class Data extends AbstractHelper
{

    public function __construct(\Magento\Framework\App\Helper\Context $context, \Magento\Store\Model\StoreManagerInterface $storeManager, \Magento\Catalog\Model\ProductFactory $productFactory, \Magento\Quote\Model\QuoteManagement $quoteManagement, \Magento\Customer\Model\CustomerFactory $customerFactory, \Magento\Customer\Api\CustomerRepositoryInterface $customerRepository, \Magento\Sales\Model\Service\OrderService $orderService, \Magento\Quote\Api\CartRepositoryInterface $cartRepositoryInterface, \Magento\Quote\Api\CartManagementInterface $cartManagementInterface, \Magento\Quote\Model\Quote\Address\Rate $shippingRate, \Magento\Sales\Model\Order\Payment\Transaction\BuilderInterface $transactionBuilder)
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
        $this->_transactionBuilder = $transactionBuilder;
        parent::__construct($context);
    }

    public function getConfig($config_path)
    {
        return $this->scopeConfig->getValue($config_path, \Magento\Store\Model\ScopeInterface::SCOPE_STORE);
    }

    public function createMageOrder($orderData)
    {
        $store = $this->_storeManager->getStore();
        $websiteId = $this->_storeManager->getStore()->getWebsiteId();

        $customer = $this->customerFactory->create();
        $customer->setWebsiteId($websiteId);
        $customer->loadByEmail($orderData['email']);



        if (! $customer->getEntityId()) {

            $customer->setWebsiteId($websiteId)
                ->setStore($store)
                ->setFirstname($orderData['shipping_address']['firstname'])
                ->setLastname($orderData['shipping_address']['lastname'])
                ->setEmail($orderData['email'])
                ->setPassword($orderData['email']);
            $customer->save();
        }

        $cart_id = $this->cartManagementInterface->createEmptyCart();
        $cart = $this->cartRepositoryInterface->get($cart_id);
        $cart->setStore($store);

        $customer = $this->customerRepository->getById($customer->getEntityId());
        $cart->setCurrency();
        $cart->assignCustomer($customer);

        foreach ($orderData['items'] as $item) {
            $product = $this->_productFactory->create()->load($item['product_id']);
            $product->setPrice($item['price']);
            $cart->addProduct($product, intval($item['qty']));
        }

        $cart->getBillingAddress()->addData($orderData['billing_address']);
        $cart->getShippingAddress()->addData($orderData['shipping_address']);

        $this->shippingRate->setCode('neoclick_neoclick')
            ->setPrice($orderData['shipping_cost'] / 100)
            ->setMethodDescription('Neoclick')
            ->setMethodTitle('Neoclick Id: ' . $orderData['neoclick_order_id'].' : '.$orderData["shipment_method"].' : '.$orderData["payment_method"]);

        $shippingAddress = $cart->getShippingAddress();

        $shippingAddress
         ->setShippingDescription('Neoclick -'.$orderData["shipment_method"])
         ->setShippingMethod('neoclick_neoclick');

        $cart->getShippingAddress()->addShippingRate($this->shippingRate);


        $cart->getPayment()->importData([
            'method' => 'neoclick'
        ]);
        $cart->collectTotals();
        $cart->save();

        $cart = $this->cartRepositoryInterface->get($cart->getId());

        try {
            $order_id = $this->cartManagementInterface->placeOrder($cart->getId());
        } catch (Exeception $e) {}

        $objectManager = \Magento\Framework\App\ObjectManager::getInstance();
        $order = $objectManager->create('\Magento\Sales\Model\Order')->load($order_id);


        $payment = $order->getPayment();
        $payment->setLastTransId($orderData['neoclick_order_id']);
        $payment->setTransactionId($orderData['neoclick_order_id']);
        $payment->setAdditionalInformation([
            \Magento\Sales\Model\Order\Payment\Transaction::RAW_DETAILS => $orderData["payment_method"]
        ]);
        $formatedPrice = $order->getBaseCurrency()->formatTxt($order->getGrandTotal());

        $message = __('The authorized amount is %1.', $formatedPrice);

        $trans = $this->_transactionBuilder;
        $transaction = $trans->setPayment($payment)
            ->setOrder($order)
            ->setTransactionId($orderData['neoclick_order_id'])
            ->setAdditionalInformation([
            \Magento\Sales\Model\Order\Payment\Transaction::RAW_DETAILS => $orderData["payment_method"]
        ])
            ->setFailSafe(true)
            ->

        build(\Magento\Sales\Model\Order\Payment\Transaction::TYPE_CAPTURE);

        $payment->addTransactionCommentsToOrder($transaction, $message);
        $payment->setParentTransactionId(null);
        $payment->save();
        $order->save();

    return $order_id;
}

}