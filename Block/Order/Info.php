<?php

namespace Improntus\PedidosYa\Block\Order;

use Improntus\PedidosYa\Model\PedidosYaFactory;
use Magento\Sales\Model\Order;
use Magento\Framework\View\Element\Template\Context as TemplateContext;
use Magento\Framework\Registry;
use Magento\Payment\Helper\Data as PaymentHelper;
use Magento\Sales\Model\Order\Address\Renderer as AddressRenderer;

/**
 * Class Info
 * @author Improntus <http://www.improntus.com> - Elevating Digital Transformation | Adobe Solution Partner
 * @copyright Copyright (c) 2025 Improntus
 * @package Improntus\PedidosYa\Block\Order
 */
class Info extends \Magento\Sales\Block\Order\Info
{
    /**
     * @var string
     */
    protected $_template = 'Improntus_PedidosYa::order/info.phtml';

    /**
     * @var null
     */
    protected $coreRegistry = null;

    /**
     * @var PaymentHelper
     */
    protected $paymentHelper;

    /**
     * @var AddressRenderer
     */
    protected $addressRenderer;

    /**
     * @var PedidosYaFactory
     */
    protected $_pedidosYaFactory;

    /**
     * @param TemplateContext $context
     * @param Registry $registry
     * @param PaymentHelper $paymentHelper
     * @param AddressRenderer $addressRenderer
     * @param array $data
     * @param PedidosYaFactory $pedidosYaFactory
     */
    public function __construct(
        TemplateContext $context,
        Registry $registry,
        PaymentHelper $paymentHelper,
        AddressRenderer $addressRenderer,
        PedidosYaFactory $pedidosYaFactory,
        array $data = []
    ) {
        $this->_pedidosYaFactory = $pedidosYaFactory;
        parent::__construct($context,$registry, $paymentHelper, $addressRenderer, $data);
    }

    /**
     * @return Order
     */
    public function getOrder()
    {
        return $this->coreRegistry->registry('current_order');
    }

    /**
     * @return string
     */
    public function getTrackingUrlStatus()
    {
        $order = $this->getOrder();

        $pedidosYa = $this->_pedidosYaFactory->create();
        $pedidosYa = $pedidosYa->getCollection()
            ->addFieldToFilter('order_id', ['eq' => $order->getEntityId()])
            ->getFirstItem();

        if (count($pedidosYa->getData()) > 0) {
            $infoPedidosYa = $pedidosYa->getInfoConfirmed();
            $infoPedidosYa = json_decode($infoPedidosYa);
            $trackingUrl = $infoPedidosYa->shareLocationUrl ?? '';
        }

        if($order->getShippingMethod() == 'pedidosya_pedidosya' && !empty($trackingUrl)) {
            return $trackingUrl;
        }

        return '';
    }

}

