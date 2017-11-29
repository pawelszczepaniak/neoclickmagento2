<?php
namespace IIA\NeoclickShippingProvider\Block;

use Magento\Checkout\Model\Session;
use Magento\Checkout\Helper\Cart;
use Magento\Framework\App\ObjectManager;
use IIA\NeoclickShippingProvider\Model\Neoclick;
use IIA\NeoclickShippingProvider\Model\NeoclickWidget;
use IIA\NeoclickShippingProvider\Model\NeoclickMerchantAPI;

class Button extends \Magento\Framework\View\Element\Template
{

    public $assetRepository;

    public $neoclick;

    /**
     * @param \Magento\Framework\View\Element\Template\Context $context
     * @param array $data
     * @param \Magento\Framework\View\Asset\Repository $assetRepository
     */
    public function __construct(\Magento\Framework\View\Element\Template\Context $context, array $data = [], \Magento\Framework\View\Asset\Repository $assetRepository)
    {
        $objectManager = \Magento\Framework\App\ObjectManager::getInstance();
        $neoclickAppId = $objectManager->create('IIA\NeoclickShippingProvider\Helper\Data')->getConfig('carriers/neoclick/neoclickappid');
        $neoclickMerchantId = $objectManager->create('IIA\NeoclickShippingProvider\Helper\Data')->getConfig('carriers/neoclick/neoclickmerchantid');
        $neoclickAccessKey = $objectManager->create('IIA\NeoclickShippingProvider\Helper\Data')->getConfig('carriers/neoclick/neoclickaccesskey');
        $neoclickAppSecret = $objectManager->create('IIA\NeoclickShippingProvider\Helper\Data')->getConfig('carriers/neoclick/neoclickappsecret');
        $neoclickSigningKey = $objectManager->create('IIA\NeoclickShippingProvider\Helper\Data')->getConfig('carriers/neoclick/neoclicksigningkey');
        $neoclickApiUrl = $objectManager->create('IIA\NeoclickShippingProvider\Helper\Data')->getConfig('carriers/neoclick/neoclickapiurl');

        $this->neoclick = new Neoclick();
        $this->neoclick->setMerchantID($neoclickMerchantId);
        $this->neoclick->setAccessKey($neoclickAccessKey);
        $this->neoclick->setAppId($neoclickAppId);
        $this->neoclick->setAppSecret($neoclickAppSecret);
        $this->neoclick->setSigningKey($neoclickSigningKey);
        $this->neoclick->setApiurl($neoclickApiUrl);
        $this->assetRepository = $assetRepository;
        return parent::__construct($context, $data);
    }

    /**
     * @return string
     */
    public function getContent()
    {
        $object = \Magento\Framework\App\ObjectManager::getInstance();
        $cart = $object->create('Magento\Checkout\Model\Cart')
            ->getQuote()
            ->getAllVisibleItems();

        $cartCount = count($cart);
        $neoclickProductTable = array();
        $neoclickProductTableId = 0;
        foreach ($cart as $product) {
            $neoclickProductTable[$neoclickProductTableId]['id'] = $product->getProductId();
            $neoclickProductTable[$neoclickProductTableId]['name'] = $product->getName();
            $neoclickProductTable[$neoclickProductTableId]['price'] =  $product->getPrice() * 100;
            $neoclickProductTable[$neoclickProductTableId]['quantity'] = $product->getQty();
            $neoclickProductTable[$neoclickProductTableId]['dimensions']['weight'] = 10;
            $neoclickProductTable[$neoclickProductTableId]['dimensions']['width'] = 10;
            $neoclickProductTable[$neoclickProductTableId]['dimensions']['height'] = 10;
            $neoclickProductTable[$neoclickProductTableId]['dimensions']['depth'] = 10;
            $neoclickProductTableId ++;
        }
        $neoclickWidget = new NeoclickWidget($this->neoclick);
        $neoclickWidget->setArticles(json_encode($neoclickProductTable));
        $neoclickWidget->setCurrency('PLN');
        $neoclickWidget->setType('real');
        $neoclickWidget->setCorrelationId('');
        $neoclickWidget->setDimensionsWidth(100);
        $neoclickWidget->setDimensionsHeight(100);
        $neoclickWidget->setDimensionsDepth(100);
        $neoclickWidget->setDimensionsWeight(100);
        return $neoclickWidget->generateBasketSimpleMagento2();

    }


}