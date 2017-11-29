<?php
namespace IIA\NeoclickShippingProvider\Controller\Index;

use Magento\Framework\App\Action\Context;
use Magento\Framework\Controller\Result\Raw;
use IIA\NeoclickShippingProvider\Model\Neoclick;
use IIA\NeoclickShippingProvider\Model\NeoclickMagento2;
use IIA\NeoclickShippingProvider\Model\NeoclickMerchantAPI;
use IIA\NeoclickShippingProvider\Model\StatusFactory;

class Index extends \Magento\Framework\App\Action\Action
{

    /** @var \Magento\Framework\Controller\Result\Raw */
    protected $rawResultFactory;

    /**
     *
     * @var \IIA\NeoclickShippingProvider\Model\NewsFactory
     */
    protected $_modelStatusFactory;

    /**
     *
     * @param Context $context
     * @param NewsFactory $modelNewsFactory
     */
    public function __construct(Context $context, StatusFactory $modelStatusFactory)
    {
        parent::__construct($context);
        $this->_modelStatusFactory = $modelStatusFactory;
    }

    public function execute()
    {

        /** @var \Magento\Framework\Controller\Result\Raw $result */
        $result = new \Magento\Framework\Controller\Result\Raw();
        $result->setHeader('Content-Type', 'text/plain');
        $result->setContents('OK');
        $neoclickData = $this->getRequest()->getContent();
        \Magento\Framework\App\ObjectManager::getInstance()->get('Psr\Log\LoggerInterface')->debug('Dane z Neoclick:');
        \Magento\Framework\App\ObjectManager::getInstance()->get('Psr\Log\LoggerInterface')->debug($neoclickData);

        $objectManager = \Magento\Framework\App\ObjectManager::getInstance();
        $neoclickDataTable = json_decode($neoclickData);

        ob_start();
        var_dump($neoclickDataTable);
        $output = ob_get_clean();

        $outputFile = "/tmp/output.txt";
        $fileHandle = fopen($outputFile, "a") or die('File creation error.');
        fwrite($fileHandle, $output);
        fclose($fileHandle);

        $neoclickAppId = $objectManager->create('IIA\NeoclickShippingProvider\Helper\Data')->getConfig('carriers/neoclick/neoclickappid');
        $neoclickMerchantId = $objectManager->create('IIA\NeoclickShippingProvider\Helper\Data')->getConfig('carriers/neoclick/neoclickmerchantid');
        $neoclickAccessKey = $objectManager->create('IIA\NeoclickShippingProvider\Helper\Data')->getConfig('carriers/neoclick/neoclickaccesskey');
        $neoclickAppSecret = $objectManager->create('IIA\NeoclickShippingProvider\Helper\Data')->getConfig('carriers/neoclick/neoclickappsecret');
        $neoclickSigningKey = $objectManager->create('IIA\NeoclickShippingProvider\Helper\Data')->getConfig('carriers/neoclick/neoclicksigningkey');
        $neoclickApiUrl = $objectManager->create('IIA\NeoclickShippingProvider\Helper\Data')->getConfig('carriers/neoclick/neoclickapiurl');
        $neoclick = new Neoclick();
        $neoclick->setMerchantID($neoclickMerchantId);
        $neoclick->setAccessKey($neoclickAccessKey);
        $neoclick->setAppId($neoclickAppId);
        $neoclick->setAppSecret($neoclickAppSecret);
        $neoclick->setSigningKey($neoclickSigningKey);
        $neoclick->setApiurl($neoclickApiUrl);

        $neoclickMerchantAPI = new NeoclickMerchantAPI($neoclick);

       /*
          $neoclickDataTable = new \stdClass();
          $neoclickDataTable->orderId = '6300150078929638603';
          $neoclickDataTable->basketId = '49c747e073bb45a2a67f07c868623142';
        */

        $neoclickOrderData = $neoclickMerchantAPI->getOrderById($neoclickDataTable->orderId);
        $neoclickBasketData = $neoclickMerchantAPI->getBasketById($neoclickOrderData->basketId);


        $statusModel = $this->_modelStatusFactory->create();
        $item = $statusModel->getCollection()
            ->addFieldToFilter('magentoOrdered', array(
            'eq' => 1
        ))
            ->addFieldToFilter('orderId', array(
            'eq' => $neoclickOrderData->id
        ));

        if (! empty($item->getData())) {
            $orderExists = true;
        } else {
            $orderExists = false;
        }

        /*
         * $logdata = array(
         * 'orderId' => $neoclickOrderData->id,
         * 'status' => $neoclickOrderData->status,
         * 'data' => $neoclickData
         * );
         * $statusModel->setData($logdata);
         * $statusModel->save();
         */
        if ($neoclickOrderData->status == 'readyToProcess' && ! $orderExists) {

            $fp = fopen("/tmp/neoclicklock.txt", "w+");

            if (flock($fp, LOCK_EX)) {
                ftruncate($fp, 0);
                fwrite($fp, $neoclickOrderData->id);

                $data = array();

                if ($neoclickOrderData->configuration->shipmentMethodId == 'inpostLocker') {

                    $data = array(
                        "currency_id" => "PLN",
                        "email" => $neoclickOrderData->configuration->contactEmail,
                        "shipping_cost" => $neoclickOrderData->shipmentTotalPrice - $neoclickOrderData->discountTotalPrice,
                        "neoclick_order_id" => $neoclickOrderData->id,
                        "shipping_address" => array(
                            "firstname" => 'NeoClick',
                            "lastname" => 'NeoClick',
                            "street" => $neoclickOrderData->configuration->shipmentParams->additionalId,
                            "city" => $neoclickOrderData->configuration->shipmentParams->additionalId,
                            "country_id" => "PL",
                            "region" => "",
                            "postcode" => $neoclickOrderData->configuration->shipmentParams->additionalId,
                            "telephone" => $neoclickOrderData->configuration->shipmentParams->phoneNumber,
                            "fax>" => $neoclickOrderData->configuration->shipmentParams->phoneNumber,
                            "save_in_address_book" => 1
                        ),
                        "shipment_method" => $neoclickOrderData->configuration->shipmentMethodId,
                        "payment_method" => ""
                    );

                    if (isset($neoclickOrderData->configuration->paymentMethodId)){
                        $data["payment_method"] = $neoclickOrderData->configuration->paymentMethodId;
                    } else {
                        $data["payment_method"] = "cod";
                    }

                }

                elseif (isset($neoclickOrderData->configuration->shipmentParams)) {

                    if (isset($neoclickOrderData->configuration->shipmentParams->deliveryAddress)) {

                        $street = $neoclickOrderData->configuration->shipmentParams->deliveryAddress->street . ' ' . $neoclickOrderData->configuration->shipmentParams->deliveryAddress->houseNumber;

                        if ($neoclickOrderData->configuration->shipmentParams->deliveryAddress->flatNumber != '') {
                            $street .= '/' . $neoclickOrderData->configuration->shipmentParams->deliveryAddress->flatNumber;
                        }

                        if (! $neoclickOrderData->configuration->shipmentParams->deliveryAddress->firstName) {
                            $neoclickOrderData->configuration->shipmentParams->deliveryAddress->firstName = 'Neoclick';
                        }

                        if (! $neoclickOrderData->configuration->shipmentParams->deliveryAddress->lastName) {
                            $neoclickOrderData->configuration->shipmentParams->deliveryAddress->lastName = 'Neoclick';
                        }
                    }
                    $data = array(
                        "currency_id" => "PLN",
                        "email" => $neoclickOrderData->configuration->contactEmail,
                        "shipping_cost" => $neoclickOrderData->shipmentTotalPrice - $neoclickOrderData->discountTotalPrice,
                        "neoclick_order_id" => $neoclickOrderData->id,
                        "shipping_address" => array(
                            "firstname" => $neoclickOrderData->configuration->shipmentParams->deliveryAddress->firstName,
                            "lastname" => $neoclickOrderData->configuration->shipmentParams->deliveryAddress->lastName,
                            "street" => $street,
                            "city" => $neoclickOrderData->configuration->shipmentParams->deliveryAddress->city,
                            "country_id" => "PL",
                            "region" => "",
                            "postcode" => $neoclickOrderData->configuration->shipmentParams->deliveryAddress->postalCode,
                            "telephone" => $neoclickOrderData->configuration->shipmentParams->phoneNumber,
                            "fax>" => $neoclickOrderData->configuration->shipmentParams->phoneNumber,
                            "save_in_address_book" => 1
                        ),
                        "shipment_method" => $neoclickOrderData->configuration->shipmentMethodId,
                        "payment_method" => ""
                    );
                    if (isset($neoclickOrderData->configuration->paymentMethodId)){
                        $data["payment_method"] = $neoclickOrderData->configuration->paymentMethodId;
                    } else {
                        $data["payment_method"] = "cod";
                    }
                }



                if (isset($neoclickOrderData->configuration->invoice)) {

                $invoiceStreet = $neoclickOrderData->configuration->invoice->street . ' ' . $neoclickOrderData->configuration->invoice->houseNumber;

                if ($neoclickOrderData->configuration->invoice->flatNumber != '') {
                    $invoiceStreet .= '/' . $neoclickOrderData->configuration->invoice->flatNumber;
                }

                $data["billing_address"] = array(

                    "firstname" => $neoclickOrderData->configuration->shipmentParams->deliveryAddress->firstName,
                    "lastname" => $neoclickOrderData->configuration->shipmentParams->deliveryAddress->lastName,
                    //"vatNumber" => $neoclickOrderData->configuration->invoice->vatNumber,
                    "company" => $neoclickOrderData->configuration->invoice->name,
                    "street" => $invoiceStreet,
                    "city" => $neoclickOrderData->configuration->invoice->city,
                    "postcode" => $neoclickOrderData->configuration->invoice->postalCode,
                    "telephone" => $neoclickOrderData->configuration->shipmentParams->phoneNumber,
                    "country_id" => "PL",
                    "region" => ""

                );
                }
                else {
                    $data["billing_address"] = $data["shipping_address"];
                }


                $neoclickBasketDataItems = array();
                $neoclickBasketDataItemsCount = 0;
                foreach ($neoclickBasketData->articles as $article) {
                    $neoclickBasketDataItems[$neoclickBasketDataItemsCount] = array(
                        "product_id" => $article->id,
                        "qty" => $article->quantity,
                        "price" => $article->price / 100
                    );
                    $neoclickBasketDataItemsCount ++;
                }
                $data['items'] = $neoclickBasketDataItems;

                $item = $statusModel->getCollection()
                    ->addFieldToFilter('magentoOrdered', array(
                    'eq' => 1
                ))
                    ->addFieldToFilter('orderId', array(
                    'eq' => $neoclickOrderData->id
                ));

                if (empty($item->getData())) {

                    $orderId = $objectManager->create('IIA\NeoclickShippingProvider\Helper\Data')->createMageOrder($data);

                    $logdata = array(
                        'orderId' => $neoclickOrderData->id,
                        'status' => $neoclickOrderData->status,
                        'data' => $neoclickData,
                        'magentoOrdered' => 1,
                        'magentoOrderId' => $orderId
                    );
                    $statusModel->setData($logdata);
                    $statusModel->save();
                }
                fflush($fp);
                flock($fp, LOCK_UN);
                fclose($fp);
            }
        }

        else {
            echo "Couldn't get the lock!";
        }

        return $result;
    }
}