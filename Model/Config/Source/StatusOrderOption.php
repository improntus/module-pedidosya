<?php

namespace Improntus\PedidosYa\Model\Config\Source;

use Magento\Framework\Option\ArrayInterface;
use Magento\Sales\Model\ResourceModel\Order\Status\CollectionFactory;

/**
 * Class StatusOrderOption
 * @package Improntus\PedidosYa\Model\Config\Source
 */
class StatusOrderOption implements ArrayInterface
{
    /**
     * @var CollectionFactory
     */
    protected $_statusCollectionFactory;

    /**
     * @param CollectionFactory $statusCollectionFactory
     */
    public function __construct(
        CollectionFactory $statusCollectionFactory
    )
    {
        $this->_statusCollectionFactory = $statusCollectionFactory;
    }

    /**
     * @return array
     */
    public function toOptionArray()
    {
        $statuses = $this->_statusCollectionFactory->create()->toOptionArray();
        array_unshift($statuses, ['value' => '', 'label' => '']);

        return $statuses;
    }
}
