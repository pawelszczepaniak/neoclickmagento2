<?php
namespace IIA\NeoclickShippingProvider\Controller\Index;

use Magento\Framework\App\ObjectManager;

class Fail extends \Magento\Framework\App\Action\Action
{

    /** @var \Magento\Framework\View\Result\PageFactory  */
    protected $resultPageFactory;

    public function __construct(\Magento\Framework\App\Action\Context $context, \Magento\Framework\View\Result\PageFactory $resultPageFactory)
    {
        $this->resultPageFactory = $resultPageFactory;
        parent::__construct($context);
    }

    public function execute()
    {
        $object = \Magento\Framework\App\ObjectManager::getInstance();
        $cart = $object->create('Magento\Checkout\Model\Cart');
        // var_dump($cart->getItems());
        $items = $cart->getItems();
        foreach ($items as $item) {
            $cart->removeItem($item->getId());
        }
        $cart->save();

        return $this->resultPageFactory->create();
    }
}