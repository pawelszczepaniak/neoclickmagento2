<?php
namespace IIA\NeoclickShippingProvider\Model;

use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\DataObject;
use Magento\Shipping\Model\Carrier\AbstractCarrier;
use Magento\Shipping\Model\Carrier\CarrierInterface;
use Magento\Shipping\Model\Config;
use Magento\Shipping\Model\Rate\ResultFactory;
use Magento\Store\Model\ScopeInterface;
use Magento\Quote\Model\Quote\Address\RateResult\ErrorFactory;
use Magento\Quote\Model\Quote\Address\RateResult\Method;
use Magento\Quote\Model\Quote\Address\RateResult\MethodFactory;
use Magento\Quote\Model\Quote\Address\RateRequest;
use Psr\Log\LoggerInterface;

class Carrier extends AbstractCarrier implements CarrierInterface
{

    /**
     * Carrier's code
     *
     * @var string
     */
    protected $_code = 'neoclick';

    /**
     * Whether this carrier has fixed rates calculation
     *
     * @var bool
     */
    protected $_isFixed = true;

    /**
     *
     * @var ResultFactory
     */
    protected $rateResultFactory;

    /**
     *
     * @var MethodFactory
     */
    protected $rateMethodFactory;

    /**
     *
     * @param ScopeConfigInterface $scopeConfig
     * @param ErrorFactory $rateErrorFactory
     * @param LoggerInterface $logger
     * @param ResultFactory $rateResultFactory
     * @param MethodFactory $rateMethodFactory
     * @param array $data
     */
    public function __construct(ScopeConfigInterface $scopeConfig, ErrorFactory $rateErrorFactory, LoggerInterface $logger, ResultFactory $rateResultFactory, MethodFactory $rateMethodFactory, array $data = [])
    {
        $this->rateResultFactory = $rateResultFactory;
        $this->rateMethodFactory = $rateMethodFactory;
        parent::__construct($scopeConfig, $rateErrorFactory, $logger, $data);
    }

    public function getAllowedMethods()
    {
        return [
            'neoclick' => $this->getConfigData('name')
        ];
    }

    /**
     *
     * @param RateRequest $request
     * @return bool|Result
     */
    public function collectRates(RateRequest $request)
    {
        if (! $this->getConfigFlag('active')) {
            return false;
        }

        /** @var \Magento\Shipping\Model\Rate\Result $result */
        $result = $this->rateResultFactory->create();

        /** @var \Magento\Quote\Model\Quote\Address\RateResult\Method $method */
        $method = $this->rateMethodFactory->create();

        $method->setCarrier('neoclick');
        $method->setCarrierTitle($this->getConfigData('title'));

        $method->setMethod('neoclick');
        $method->setMethodTitle($this->getConfigData('name'));

        $method->setMethod('neoclick');
        $method->setMethodTitle($this->getConfigData('description'));

        $amount = 0;
        $method->setPrice($amount);
        $method->setCost($amount);

        $result->append($method);

        return $result;
    }
}
