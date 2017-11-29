<?php

namespace IIA\NeoclickShippingProvider\Model\Resource;

use Magento\Framework\Model\ResourceModel\Db\AbstractDb;

class Status extends AbstractDb
{
    /**
     * Define main table
     */
    protected function _construct()
    {
        $this->_init('neoclick_status', 'id');
    }
}