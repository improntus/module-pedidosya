<?php

namespace Improntus\PedidosYa\Model\ResourceModel;

use Magento\Framework\Model\ResourceModel\Db\AbstractDb;
use Magento\Framework\Model\ResourceModel\Db\Context;

/**
 * Class PedidosYa
 * @author Improntus <http://www.improntus.com> - Ecommerce done right
 * @copyright Copyright (c) 2023 Improntus
 * @package Improntus\PedidosYa\Model\ResourceModel
 */
class PedidosYa extends AbstractDb
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
        $this->_init('improntus_pedidosya','entity_id');
    }
}
