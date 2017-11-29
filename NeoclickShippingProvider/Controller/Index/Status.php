<?php
namespace IIA\NeoclickShippingProvider\Controller\Index;

use Magento\Framework\App\Action\Context;
use Magento\Framework\Controller\Result\Raw;

class Status extends \Magento\Framework\App\Action\Action
{

    /** @var \Magento\Framework\Controller\Result\Raw */
    protected $rawResultFactory;

    public function __construct(\Magento\Framework\App\Action\Context $context)

    {
        parent::__construct($context);
    }

    public function execute()
    {

        /** @var \Magento\Framework\Controller\Result\Raw $result */
        $result = new \Magento\Framework\Controller\Result\Raw();

        $result->setHeader('Content-Type', 'text/plain');
        $result->setContents('OK');
        return $result;
    }
}