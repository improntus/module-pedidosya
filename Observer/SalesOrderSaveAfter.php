<?php
namespace Improntus\PedidosYa\Observer;

use Improntus\PedidosYa\Helper\Data;
use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Improntus\PedidosYa\Model\CreateShipment;

/**
 * Class SalesOrderSaveAfter
 * @package Improntus\PedidosYa\Observer
 */
class SalesOrderSaveAfter implements ObserverInterface
{
    /**
     * @var Data
     */
    protected $_helper;

    /**
     * @var CreateShipment
     */
    protected $_createShipment;

    /**
     * @param Data $data
     * @param CreateShipment $createShipment
     */
    public function __construct(
        Data $data,
        CreateShipment $createShipment
    ) {
        $this->_helper          = $data;
        $this->_createShipment  = $createShipment;
    }

    /**
     * @param Observer $observer
     * @return $this|void
     */
    public function execute(Observer $observer)
    {
        try {
            $order = $observer->getEvent()->getOrder();
            if($this->_createShipment->create(NULL, $order))
            return $this;
        } catch (\Exception $e) {
            $this->_helper->log($e->getMessage());
        }
    }
}
