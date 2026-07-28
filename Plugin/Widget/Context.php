<?php
namespace Improntus\PedidosYa\Plugin\Widget;

use Improntus\PedidosYa\Model\PedidosYaFactory;
use Magento\Backend\Block\Widget\Context AS Subject;
use Magento\Sales\Api\OrderRepositoryInterface;
use Magento\Framework\UrlInterface;
use Magento\Framework\Data\Form\FormKey;
use Improntus\PedidosYa\Helper\Data as DataPedidosYa;

/**
 * Class Context
 * @author Improntus <http://www.improntus.com> - Adobe Gold Technology Partner | Adobe Gold Solution Partner
 * @copyright Copyright (c) 2026 Improntus
 * @package Improntus\PedidosYa\Plugin\Widget
 */
class Context
{
    /**
     * @var OrderRepositoryInterface
     */
    protected $_orderRepository;

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
     * @var FormKey
     */
    protected $_formKey;

    /**
     * @param OrderRepositoryInterface $orderRepository
     * @param DataPedidosYa $helperPedidosYa
     * @param UrlInterface $urlInterface
     * @param PedidosYaFactory $pedidosYaFactory
     * @param FormKey $formKey
     */
    public function __construct(
        OrderRepositoryInterface $orderRepository,
        DataPedidosYa $helperPedidosYa,
        UrlInterface $urlInterface,
        PedidosYaFactory $pedidosYaFactory,
        FormKey $formKey
    )
    {
        $this->_orderRepository     = $orderRepository;
        $this->_helperPedidosYa     = $helperPedidosYa;
        $this->_backendUrl          = $urlInterface;
        $this->_pedidosYaFactory    = $pedidosYaFactory;
        $this->_formKey             = $formKey;
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
        if (!$this->_helperPedidosYa->isActive()) {
            return $buttonList;
        }

        if ($subject->getRequest()->getFullActionName() !== 'sales_order_view') {
            return $buttonList;
        }

        $orderId = (int) $subject->getRequest()->getParam('order_id');
        if (!$orderId) {
            return $buttonList;
        }

        try {
            $order = $this->_orderRepository->get($orderId);
        } catch (\Exception $e) {
            return $buttonList;
        }

        if ($order->getShippingMethod() !== 'pedidosya_pedidosya') {
            return $buttonList;
        }

        $pedidosYa = $this->_pedidosYaFactory->create()
            ->getCollection()
            ->addFieldToFilter('order_id', ['eq' => $orderId])
            ->getFirstItem();

        $confirmationCode = '';
        $status = '';
        if (count($pedidosYa->getData()) > 0) {
            $infoPedidosYa = json_decode((string) $pedidosYa->getInfoConfirmed());
            $confirmationCode = $infoPedidosYa->confirmationCode ?? '';
            $status = $pedidosYa->getStatus();
        }

        if (!empty($confirmationCode) && $status !== 'pedidosya_cancelled') {
            $cancelUrl = $this->_backendUrl->getUrl('pedidosya/shipment/cancel', ['order_id' => $orderId]);
            $buttonList->add(
                'pedidosya_shipment_cancel',
                [
                    'label'     => __('Cancel Rider'),
                    'onclick'   => $this->getPostOnClick($cancelUrl),
                    'class'     => 'primary pedidosya-shipment-button'
                ]
            );
        } elseif (!empty($confirmationCode)) {
            $createUrl = $this->_backendUrl->getUrl('pedidosya/shipment/create', ['order_id' => $orderId]);
            $buttonList->add(
                'pedidosya_shipment_create',
                [
                    'label'     => __('Re Request Rider'),
                    'onclick'   => $this->getPostOnClick($createUrl),
                    'class'     => 'primary pedidosya-shipment-button'
                ]
            );
        } else {
            $createUrl = $this->_backendUrl->getUrl('pedidosya/shipment/create', ['order_id' => $orderId]);
            $buttonList->add(
                'pedidosya_shipment_create',
                [
                    'label'     => __('Request Rider'),
                    'onclick'   => $this->getPostOnClick($createUrl),
                    'class'     => 'primary pedidosya-shipment-button'
                ]
            );
        }

        return $buttonList;
    }

    /**
     * Build an onclick handler that submits a form-key protected POST request.
     *
     * @param string $url
     * @return string
     */
    private function getPostOnClick($url)
    {
        $formKey = $this->_formKey->getFormKey();
        return "var f=document.createElement('form');"
            . "f.method='POST';"
            . "f.action='" . $url . "';"
            . "var k=document.createElement('input');"
            . "k.type='hidden';k.name='form_key';k.value='" . $formKey . "';"
            . "f.appendChild(k);document.body.appendChild(f);f.submit();";
    }
}
