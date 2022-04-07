<?php
namespace Improntus\PedidosYa\Observer;

use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Magento\Quote\Api\CartRepositoryInterface;
use Magento\Checkout\Model\Session;

/**
 * Class SalesOrderPlaceBefore
 * @author Improntus <http://www.improntus.com> - Ecommerce done right
 * @copyright Copyright (c) 2022 Improntus
 * @package Improntus\PedidosYa\Observer
 */
class SalesOrderPlaceBefore implements ObserverInterface
{
    /**
     * @var CartRepositoryInterface
     */
    protected $_quoteRepository;

    /**
     * @var Session
     */
    protected $_checkoutSession;

    /**
     * @param CartRepositoryInterface $quoteRepository
     * @param Session $checkoutSession
     */
    public function __construct(
        CartRepositoryInterface $quoteRepository,
        Session                 $checkoutSession
    )
    {
        $this->_quoteRepository = $quoteRepository;
        $this->_checkoutSession = $checkoutSession;
    }

    /**
     * @param Observer $observer
     * @return $this
     */
    public function execute(Observer $observer)
    {
        $order = $observer->getEvent()->getOrder();

        if($order->getShippingMethod() == \Improntus\PedidosYa\Model\Carrier\PedidosYa::CARRIER_CODE . '_'
            . \Improntus\PedidosYa\Model\Carrier\PedidosYa::CARRIER_CODE)
        {
            $order->setPedidosyaEstimatedata($this->_checkoutSession->getPedidosyaEstimatedata());
            $order->setPedidosyaSourceWaypoint($this->_checkoutSession->getPedidosyaSourceWaypoint());
        }

        return $this;
    }
}
