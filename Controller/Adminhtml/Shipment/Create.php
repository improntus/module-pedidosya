<?php

namespace Improntus\PedidosYa\Controller\Adminhtml\Shipment;


use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\App\Response\Http\FileFactory;
use Magento\Framework\Controller\ResultInterface;
use Magento\Framework\Registry;
use Magento\Framework\View\Result\Page;
use Magento\Framework\Controller\ResultFactory;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\View\Result\PageFactory;
use Improntus\PedidosYa\Model\CreateShipment;
use Improntus\PedidosYa\Helper\Data as PedidosYaHelper;

/**
 * Class Create
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
     * @var ScopeConfigInterface
     */
    protected $_scopeConfig;

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
     * @param FileFactory $fileFactory
     * @param ScopeConfigInterface $scopeConfigInterface
     * @param CreateShipment $createShipment
     * @param PedidosYaHelper $pedidosYaHelper
     */
    public function __construct(
        Context $context,
        Registry $coreRegistry,
        FileFactory $fileFactory,
        ScopeConfigInterface $scopeConfigInterface,
        CreateShipment $createShipment,
        PedidosYaHelper $pedidosYaHelper
    )
    {
        parent::__construct($context);
        $this->_coreRegistry        = $coreRegistry;
        $this->resultPageFactory    = $context->getResultFactory();
        $this->_context             = $context;
        $this->_scopeConfig         = $scopeConfigInterface;
        $this->_createShipment      = $createShipment;
        $this->_pedidosYaHelper     = $pedidosYaHelper;
    }

    /**
     * Method to create Magento shipment and request Pedidos Ya rider
     *
     * @return ResultInterface|Page
     */
    public function execute()
    {
        if($orderId = $this->getRequest()->getParam('order_id')) {
            try {
                $response = $this->_createShipment->create($orderId);
                if($response == $this->_pedidosYaHelper::STATUS_NOT_ALLOWED) {
                    $this->messageManager->addErrorMessage(__('The status of the order does not allow to generate the shipment Pedidos Ya.'));
                } elseif($response == $this->_pedidosYaHelper::PEDIDOSYA_OK) {
                    $this->messageManager->addSuccessMessage(__('Pedidos Ya shipment generated.'));
                } elseif($response == $this->_pedidosYaHelper::PEDIDOSYA_ERROR_WS) {
                    $this->messageManager->addErrorMessage(__('An error occurred trying to generate the shipment Pedidos Ya. WS error response.'));
                } elseif($response == $this->_pedidosYaHelper::PEDIDOSYA_ERROR_DATA) {
                    $this->messageManager->addErrorMessage(__('An error occurred trying to generate the shipment Pedidos Ya. EstimateData field is missing.'));
                }
            } catch (\Exception $e) {
                $this->messageManager->addErrorMessage(__('An error occurred trying to generate the shipment Pedidos Ya.') . $e->getMessage());
                $this->_pedidosYaHelper->log($e->getMessage(), true);
            }
        }

        $resultRedirect = $this->resultPageFactory->create(ResultFactory::TYPE_REDIRECT);
        $resultRedirect->setUrl($this->_redirect->getRefererUrl());

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