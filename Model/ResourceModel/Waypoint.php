<?php

namespace Improntus\PedidosYa\Model\ResourceModel;

use Magento\Framework\Model\ResourceModel\Db\AbstractDb;
use Magento\Framework\Model\ResourceModel\Db\Context;

/**
 * Class Waypoint
 * @package Improntus\PedidosYa\Model\ResourceModel
 */
class Waypoint extends AbstractDb
{
    /**
     * @param Context $context
     * @param null $resourcePrefix
     */
    public function __construct(
        Context $context,
        $resourcePrefix = null
    ) {
        parent::__construct($context, $resourcePrefix);
    }

    public function _construct()
    {
        $this->_init('improntus_pedidosya_waypoint','entity_id');
    }
}