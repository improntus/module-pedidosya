<?php

/**
 * @author Improntus Dev Team
 * @copyright Copyright (c) 2025 Improntus (http://www.improntus.com/)
 */

namespace Improntus\PedidosYa\Model\ResourceModel;

use Magento\Framework\Model\ResourceModel\Db\AbstractDb;
use Magento\Framework\Model\ResourceModel\Db\Context;

/**
 * Class Token
 * @author Improntus <http://www.improntus.com> - Elevating Digital Transformation | Adobe Solution Partner
 * @copyright Copyright (c) 2022 Improntus
 * @package Improntus\PedidosYa\Model\ResourceModel
 */
class Token extends AbstractDb
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
        $this->_init('improntus_pedidosya_token','entity_id');
    }
}
