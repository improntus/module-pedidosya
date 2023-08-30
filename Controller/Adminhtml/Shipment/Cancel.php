<?php

namespace Improntus\PedidosYa\Controller\Adminhtml\Shipment;

use Magento\Sales\Model\Order;
use Magento\Framework\Registry;
use Magento\Backend\App\Action;
use Magento\Framework\View\Result\Page;
use Magento\Backend\App\Action\Context;
use Magento\Sales\Model\OrderRepository;
use Improntus\PedidosYa\Model\Webservice;
use Magento\Framework\View\Result\PageFactory;
use Improntus\PedidosYa\Model\PedidosYaFactory;
use Magento\Framework\Controller\ResultFactory;
use Magento\Framework\Controller\ResultInterface;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Improntus\PedidosYa\Helper\Data as PedidosYaHelper;

/**
 * Class Cancel
 * @author Improntus <http://www.improntus.com> - Ecommerce done right
 * @copyright Copyright (c) 2023 Improntus
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
    ) {
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
        if ($orderId = $this->getRequest()->getParam('order_id')) {
            try {
                $order = $this->_orderRepository->get($orderId);
                $pedidosYa = $this->_pedidosYaFactory->create()
                    ->getCollection()
                    ->addFieldToFilter('order_id', ['eq' => $orderId])
                    ->getFirstItem();

                if (count($pedidosYa->getData()) > 0) {
                    $infoPedidosYa = json_decode($pedidosYa->getInfoConfirmed());
                    $confirmationCode = $infoPedidosYa->confirmationCode ?? '';

                    if($this->_pedidosYaHelper->getIntegrationMode()){
                        // API MODE
                        $pedidosYaShippingId = $infoPedidosYa->shippingId ?? '';
                    } else {
                        // E-COMMERCE MODE
                        $pedidosYaShippingId = $infoPedidosYa->id ?? '';
                    }

                    if ($order instanceof Order && $pedidosYaShippingId && $confirmationCode) {
                        /**
                         * Cancel shipping order. Only shippings in "CONFIRMED" status can be canceled using this endpoint.
                         * Once shipping order has "IN_PROGRESS" status, it's necessary to contact PedidosYA for cancellation request.
                         */
                        if ($infoPedidosYa->status == 'CONFIRMED') {
                            $webservice = $this->_webservice;
                            $shipment = $order->getShipmentsCollection()->getFirstItem();
                            $reason = ['reasonText' => 'Canceled by the store'];
                            $cancelData = $webservice->cancelShippingOrder($pedidosYaShippingId, $reason, $order->getStoreId());
                            if (isset($cancelData->status)) {
                                if ($cancelData->status == 'CANCELLED') {
                                    $statusComment = __('PedidosYa: Cancellation of shipment %1', $pedidosYaShippingId);
                                    $pedidosYa->setStatus('pedidosya_cancelled');
                                    $pedidosYa->setInfoCancelled(json_encode($cancelData));
                                    $pedidosYa->save();
                                    $history = $order->addStatusHistoryComment($statusComment);
                                    $history->save();
                                    $shipment->addComment($statusComment);
                                    $shipment->save();
                                    $this->messageManager->addSuccessMessage(__('Shipment PedidosYa was canceled'));
                                } else {
                                    $this->messageManager->addErrorMessage(__('An error occurred when canceling the shipment in PedidosYa: %1', $cancelData->message));
                                }
                            } else {
                                $this->messageManager->addErrorMessage(__('No PedidosYa shipment was canceled.'));
                            }
                        } else {
                            $this->messageManager->addErrorMessage(__('No Pedidos Ya shipment was canceled. Only shipments with CONFIRMED status can be cancelled.'));
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
