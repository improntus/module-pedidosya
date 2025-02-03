<?php
namespace Improntus\PedidosYa\Plugin\Widget;

use Improntus\PedidosYa\Model\PedidosYaFactory;
use Magento\Backend\Block\Widget\Context AS Subject;
use Magento\Sales\Model\Order;
use Magento\Framework\UrlInterface;
use Improntus\PedidosYa\Helper\Data as DataPedidosYa;

/**
 * Class Context
 * @author Improntus <http://www.improntus.com> - Elevating Digital Transformation | Adobe Solution Partner
 * @copyright Copyright (c) 2025 Improntus
 * @package Improntus\PedidosYa\Plugin\Widget
 */
class Context
{
    /**
     * @var Order
     */
    protected $_order;

    /**
     * @var DataPedidosYa
     */
    protected $_helperPedidosYa;

    /**
     * @var UrlInterface
     */
    protected $_backendUrl;

    /**
     * @var PedidosYaFactory
     */
    protected $_pedidosYaFactory;

    /**
     * @param Order $order
     * @param DataPedidosYa $helperPedidosYa
     * @param UrlInterface $urlInterface
     * @param PedidosYaFactory $pedidosYaFactory
     */
    public function __construct(
        Order $order,
        DataPedidosYa $helperPedidosYa,
        UrlInterface $urlInterface,
        PedidosYaFactory $pedidosYaFactory
    )
    {
        $this->_order               = $order;
        $this->_helperPedidosYa     = $helperPedidosYa;
        $this->_backendUrl          = $urlInterface;
        $this->_pedidosYaFactory    = $pedidosYaFactory;
    }

    /**
     * @param Subject $subject
     * @param $buttonList
     * @return mixed
     */
    public function afterGetButtonList(
        Subject $subject,
        $buttonList
    )
    {
        if($this->_helperPedidosYa->isActive()) {
            $orderId    = $subject->getRequest()->getParam('order_id');
            $order      = $this->_order->load($orderId);

            $pedidosYa = $this->_pedidosYaFactory->create();
            $pedidosYa = $pedidosYa->getCollection()
                ->addFieldToFilter('order_id', ['eq' => $orderId])
                ->getFirstItem();

            if (count($pedidosYa->getData()) > 0) {
                $infoPedidosYa = $pedidosYa->getInfoConfirmed();
                $infoPedidosYa = json_decode($infoPedidosYa);
                $confirmationCode = $infoPedidosYa->confirmationCode ?? '';
            }

            if($subject->getRequest()->getFullActionName() == 'sales_order_view' && $order->getShippingMethod() == 'pedidosya_pedidosya') {
                if(!empty($confirmationCode)) {
                    if($pedidosYa->getStatus() != 'pedidosya_cancelled') {
                        $baseUrl = $this->_backendUrl->getUrl('pedidosya/shipment/cancel', ['order_id' => $orderId]);

                        $buttonList->add(
                            'pedidosya_shipment_cancel',
                            [
                                'label'     => __('Cancel Rider'),
                                'onclick' => "location.href='{$baseUrl}'",
                                'class'     => 'primary pedidosya-shipment-button'
                            ]
                        );
                    } else {
                        $baseUrl = $this->_backendUrl->getUrl('pedidosya/shipment/create', ['order_id' => $orderId]);

                        $buttonList->add(
                            'pedidosya_shipment_create',
                            [
                                'label'     => __('Re Request Rider'),
                                'onclick' => "location.href='{$baseUrl}'",
                                'class'     => 'primary pedidosya-shipment-button'
                            ]
                        );
                    }
                } else {
                    $baseUrl = $this->_backendUrl->getUrl('pedidosya/shipment/create', ['order_id' => $orderId]);

                    $buttonList->add(
                        'pedidosya_shipment_create',
                        [
                            'label'     => __('Request Rider'),
                            'onclick' => "location.href='{$baseUrl}'",
                            'class'     => 'primary pedidosya-shipment-button'
                        ]
                    );
                }
            }
        }
        return $buttonList;
    }
}
