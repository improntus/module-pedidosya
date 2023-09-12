<?php

namespace Improntus\PedidosYa\Controller\Adminhtml\Shipment;

use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\Controller\ResultInterface;
use Magento\Framework\Registry;
use Magento\Framework\View\Result\Page;
use Magento\Framework\Controller\Result\RedirectFactory;
use Magento\Framework\View\Result\PageFactory;
use Improntus\PedidosYa\Model\CreateShipment;
use Improntus\PedidosYa\Helper\Data as PedidosYaHelper;

/**
 * Class Create
 * @author Improntus <http://www.improntus.com> - Ecommerce done right
 * @copyright Copyright (c) 2023 Improntus
 * @package Improntus\PedidosYa\Controller\Adminhtml\Shipment
 */
class Create extends Action
{
    /**
     * @var Registry
     */
    protected $_coreRegistry;

    /**
     * @var PageFactory
     */
    protected $resultPageFactory;

    /**
     * @var Context
     */
    protected $_context;

    /**
     * @var CreateShipment
     */
    protected $_createShipment;

    /**
     * @var PedidosYaHelper
     */
    protected $_pedidosYaHelper;

    /**
     * @param Context $context
     * @param Registry $coreRegistry
     * @param CreateShipment $createShipment
     * @param PedidosYaHelper $pedidosYaHelper
     * @param RedirectFactory $resultRedirectFactory
     */
    public function __construct(
        Context $context,
        Registry $coreRegistry,
        CreateShipment $createShipment,
        PedidosYaHelper $pedidosYaHelper,
        RedirectFactory $resultRedirectFactory
    ) {
        parent::__construct($context);
        $this->_coreRegistry            = $coreRegistry;
        $this->resultRedirectFactory    = $resultRedirectFactory;
        $this->_context                 = $context;
        $this->_createShipment          = $createShipment;
        $this->_pedidosYaHelper         = $pedidosYaHelper;
    }

    /**
     * Method to create Magento shipment and request Pedidos Ya rider
     *
     * @return ResultInterface|Page
     */
    public function execute()
    {
        if ($orderId = $this->getRequest()->getParam('order_id')) {
            try {
                $response = $this->_createShipment->create($orderId);

                // Define Status Code => Message
                $statusMessages = [
                    $this->_pedidosYaHelper::PEDIDOSYA_ERROR_STATUS => 'The status of the order does not allow to generate the shipment Pedidos Ya.',
                    $this->_pedidosYaHelper::PEDIDOSYA_OK => 'Pedidos Ya shipment generated.',
                    $this->_pedidosYaHelper::PEDIDOSYA_ERROR_WS => 'An error occurred trying to generate the shipment Pedidos Ya. WS error response.',
                    $this->_pedidosYaHelper::PEDIDOSYA_ERROR_DATA => 'An error occurred trying to generate the shipment Pedidos Ya. EstimateData field is missing.',
                    $this->_pedidosYaHelper::PEDIDOSYA_ERROR_TIME => 'An error occurred trying to generate the shipment Pedidos Ya. Waypoint is not in working hours.',
                    $this->_pedidosYaHelper::PEDIDOSYA_ERROR_RIDER => 'An error occurred trying to generate the shipment Pedidos Ya. There is no rider available at this time',
                ];

                // Add Message
                if (array_key_exists($response, $statusMessages)) {
                    if ($response == $this->_pedidosYaHelper::PEDIDOSYA_OK) {
                        $this->messageManager->addSuccessMessage($statusMessages[$response]);
                    } else {
                        $this->messageManager->addErrorMessage($statusMessages[$response]);
                    }
                } else {
                    // Other error
                    $this->messageManager->addErrorMessage(__("An error occurred trying to generate the shipment PedidosYa: %1", $response));
                }
            } catch (\Exception $e) {
                $this->messageManager->addErrorMessage(__('An error occurred trying to generate the shipment PedidosYa: %s', $e->getMessage()));
                $this->_pedidosYaHelper->log($e->getMessage());
            }
        }

        $resultRedirect = $this->resultRedirectFactory->create();
        $resultRedirect->setPath('sales/order/view', ['order_id' => $orderId]);
        return $resultRedirect;
    }

    /**
     * @return bool
     */
    protected function _isAllowed()
    {
        return $this->_authorization->isAllowed('Improntus_PedidosYa::shipment_create');
    }
}
