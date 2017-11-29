<?php

namespace IIA\NeoclickShippingProvider\Model;

use Magento\Framework\Model\AbstractModel;

class Status extends AbstractModel
{
    /**
     * Define resource model
     */
    protected function _construct()
    {

        $this->_init('IIA\NeoclickShippingProvider\Model\Resource\Status');

    }
}
