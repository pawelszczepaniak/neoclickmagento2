<?php
namespace IIA\NeoclickShippingProvider\Controller\Index;

use Magento\Framework\App\Action\Context;
use Magento\Checkout\Model\Cart as CustomerCart;

class Remove extends \Magento\Framework\App\Action\Action
{

    /**
     *
     * @var \Magento\Checkout\Model\Session
     */
    protected $checkoutSession;

    /**
     *
     * @var \Magento\Checkout\Model\Cart
     */
    protected $cart;

    /**
     *
     * @param Context $context
     * @param \Magento\Checkout\Model\Session $checkoutSession
     * @param CustomerCart $cart
     */
    public function __construct(Context $context)
    {
        parent::__construct($context);
    }

    public function execute()
    {
        $object = \Magento\Framework\App\ObjectManager::getInstance();
        $cart = $object->create('Magento\Checkout\Model\Cart');
        $items = $cart->getItems();
        foreach ($items as $item) {
            $cart->removeItem($item->getId());
        }

        $cart->save();
        $cart->truncate();
        $message = __('Dziękujemy za zamówienie z wykorzystaniem Neoclick');
        $this->messageManager->addSuccessMessage($message);

        $response = [
            'success' => true
        ];

        $this->getResponse()->representJson($this->_objectManager->get('Magento\Framework\Json\Helper\Data')
            ->jsonEncode($response));
    }

    public function deleteQuoteItems()
    {
        $checkoutSession = $this->getCheckoutSession();
        $allItems = $checkoutSession->getQuote()->getAllVisibleItems();
        foreach ($allItems as $item) {
            $itemId = $item->getItemId();
            $quoteItem = $this->getItemModel()->load($itemId);
            $quoteItem->delete();
        }
    }

    public function getCheckoutSession()
    {
        $objectManager = \Magento\Framework\App\ObjectManager::getInstance();
        $checkoutSession = $objectManager->get('Magento\Checkout\Model\Session');
        return $checkoutSession;
    }

    public function getItemModel()
    {
        $objectManager = \Magento\Framework\App\ObjectManager::getInstance();
        $itemModel = $objectManager->create('Magento\Quote\Model\Quote\Item');
        return $itemModel;
    }

}