<?php

namespace IIA\NeoclickShippingProvider\Model\Resource\Status;

use Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection;

class Collection extends AbstractCollection
{
    /**
     * Define model & resource model
     */
    protected function _construct()
    {
        $this->_init(
            'IIA\NeoclickShippingProvider\Model\Status',
            'IIA\NeoclickShippingProvider\Model\Resource\Status'
        );
    }
}