<?php

namespace Improntus\PedidosYa\Model;

use Magento\Framework\Data\Collection\AbstractDb;
use Magento\Framework\Model\AbstractModel;
use Magento\Framework\Model\Context;
use Magento\Framework\Model\ResourceModel\AbstractResource;
use Magento\Framework\Registry;

/**
 * Class Waypoint
 * @author Improntus <http://www.improntus.com> - Ecommerce done right
 * @copyright Copyright (c) 2022 Improntus
 * @package Improntus\PedidosYa\Model
 */
class Waypoint extends AbstractModel
{
    /**
     * @var string
     */
    protected $_eventPrefix = 'improntus_pedidosya_waypoint_event';

    /**
     * @var string
     */
    /**
     * @var bool
     */
    protected $_isStatusChanged = false;

    /**
     * @param Context $context
     * @param Registry $registry
     * @param AbstractResource|null $resource
     * @param AbstractDb|null $resourceCollection
     * @param array $data
     */
    public function __construct(
        Context $context,
        Registry $registry,
        AbstractResource $resource = null,
        AbstractDb $resourceCollection = null,
        array $data = []
    ) {
        parent::__construct($context, $registry, $resource, $resourceCollection, $data);
    }

    protected function _construct()
    {
        $this->_init('Improntus\PedidosYa\Model\ResourceModel\Waypoint');
    }
}
