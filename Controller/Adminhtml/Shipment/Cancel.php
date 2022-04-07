<?php

namespace Improntus\PedidosYa\Controller\Adminhtml\Shipment;

use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\Controller\ResultInterface;
use Magento\Framework\Registry;
use Magento\Framework\View\Result\Page;
use Magento\Framework\View\Result\PageFactory;
use Magento\Sales\Model\Order;
use Magento\Sales\Model\OrderRepository;
use Magento\Framework\Controller\ResultFactory;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Improntus\PedidosYa\Model\Webservice;
use Improntus\PedidosYa\Model\PedidosYaFactory;
use Improntus\PedidosYa\Helper\Data as PedidosYaHelper;

/**
 * Class Cancel
 * @author Improntus <http://www.improntus.com> - Ecommerce done right
 * @copyright Copyright (c) 2022 Improntus
 * @package Improntus\PedidosYa\Controller\Adminhtml\Shipment
 */
class Cancel extends Action
{
    /**
     * @var Registry
     */
    protected $_coreRegistry;

    /**
     * @var PageFactory
     */
    protected $_resultPageFactory;

    /**
     * @var OrderRepository
     */
    protected $_orderRepository;

    /**
     * @var Webservice
     */
    protected $_webservice;

    /**
     * @var Context
     */
    protected $_context;

    /**
     * @var ScopeConfigInterface
     */
    protected $_scopeConfig;

    /**
     * @var PedidosYaFactory
     */
    protected $_pedidosYaFactory;

    /**
     * @var PedidosYaHelper
     */
    protected $_pedidosYaHelper;

    /**
     * @param Context $context
     * @param Registry $coreRegistry
     * @param PedidosYaFactory $pedidosYaFactory
     * @param OrderRepository $orderRepository
     * @param Webservice $webservice
     * @param ScopeConfigInterface $scopeConfigInterface
     * @param PedidosYaHelper $pedidosYaHelper
     */
    public function __construct(
        Context $context,
        Registry $coreRegistry,
        PedidosYaFactory $pedidosYaFactory,
        OrderRepository $orderRepository,
        Webservice $webservice,
        ScopeConfigInterface $scopeConfigInterface,
        PedidosYaHelper $pedidosYaHelper
    )
    {
        parent::__construct($context);
        $this->_coreRegistry        = $coreRegistry;
        $this->_resultPageFactory   = $context->getResultFactory();
        $this->_pedidosYaFactory    = $pedidosYaFactory;
        $this->_orderRepository     = $orderRepository;
        $this->_webservice          = $webservice;
        $this->_context             = $context;
        $this->_scopeConfig         = $scopeConfigInterface;
        $this->_pedidosYaHelper     = $pedidosYaHelper;
    }

    /**
     * Method to cancel Pedidos Ya rider
     *
     * @return ResultInterface|Page
     */
    public function execute()
    {
        if($orderId = $this->getRequest()->getParam('order_id')) {
            try {
                $order = $this->_orderRepository->get($orderId);
                $pedidosYa = $this->_pedidosYaFactory->create()
                    ->getCollection()
                    ->addFieldToFilter('order_id', ['eq' => $orderId])
                    ->getFirstItem();

                if (count($pedidosYa->getData()) > 0) {
                    $infoPedidosYa = json_decode($pedidosYa->getInfoConfirmed());
                    $confirmationCode = $infoPedidosYa->confirmationCode ?? '';
                    $pedidosYaShippingId = $infoPedidosYa->id ?? '';

                    if($order instanceof Order && $pedidosYaShippingId && $confirmationCode) {

                        /**
                         * Cancel shipping order. Only shippings in "CONFIRMED" status can be canceled using this endpoint.
                         * Once shipping order has "IN_PROGRESS" status, it's necessary to contact PedidosYA for cancellation request.
                         */
                        if($infoPedidosYa->status == 'CONFIRMED') {
                            $webservice = $this->_webservice;
                            $shipment = $order->getShipmentsCollection()->getFirstItem();
                            $reason = ['reasonText' => 'Cancelled'];
                            $cancelData = $webservice->cancelShippingOrder($pedidosYaShippingId, $reason);

                            if(isset($cancelData->status)) {
                                if($cancelData->status == 'CANCELLED') {
                                    $statusComment = __('Pedidos Ya: Cancellation of shipment %1',$pedidosYaShippingId);
                                    $pedidosYa->setStatus('pedidosya_cancelled');
                                    $pedidosYa->setInfoCancelled(json_encode($cancelData));
                                    $pedidosYa->save();
                                    $history = $order->addStatusHistoryComment($statusComment);
                                    $history->save();
                                    $shipment->addComment($statusComment);
                                    $shipment->save();
                                    $this->messageManager->addSuccessMessage(__('Shipment Pedidos Ya was canceled'));
                                } else {
                                    $this->messageManager->addErrorMessage(__('No Pedidos Ya shipment was canceled. Error: %1', $cancelData->message));
                                }
                            } else {
                                $this->messageManager->addErrorMessage(__('No Pedidos Ya shipment was canceled.'));
                            }
                        } else {
                            $this->messageManager->addErrorMessage(__('No Pedidos Ya shipment was canceled. Solo envíos con status CONFIRMED pueden ser cancelados.'));
                        }
                    }
                }
            } catch (\Exception $e) {
                $this->_pedidosYaHelper->log($e->getMessage());
                $this->messageManager->addErrorMessage(__('There was a problem canceling the shipment.') . $e->getMessage());
            }
        }

        $resultRedirect = $this->_resultPageFactory->create(ResultFactory::TYPE_REDIRECT);
        $resultRedirect->setUrl($this->_redirect->getRefererUrl());
        return $resultRedirect;
    }

    /**
     * @return bool
     */
    protected function _isAllowed()
    {
        return $this->_authorization->isAllowed('Improntus_PedidosYa::shipment_cancel');
    }

}
