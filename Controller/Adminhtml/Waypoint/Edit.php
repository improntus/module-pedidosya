<?php

namespace Improntus\PedidosYa\Controller\Adminhtml\Waypoint;

use Improntus\PedidosYa\Model\WaypointFactory;
use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Backend\Model\View\Result\Page;
use Magento\Framework\App\ResponseInterface;
use Magento\Framework\Controller\ResultInterface;
use Magento\Framework\Registry;
use Magento\Framework\View\Result\PageFactory;

/**
 * Class Edit
 * @author Improntus <http://www.improntus.com> - Elevating Digital Transformation | Adobe Solution Partner
 * @copyright Copyright (c) 2025 Improntus
 * @package Improntus\PedidosYa\Controller\Adminhtml\Waypoint
 */
class Edit extends Action
{
    /**
     * @var Registry
     */
    protected $_coreRegistry;

    /**
     * @var WaypointFactory
     */
    protected $_sucursalFactory;

    /**
     * @var PageFactory
     */
    protected $_resultPageFactory;

    /**
     * @param Context $context
     * @param Registry $coreRegistry
     * @param PageFactory $resultPageFactory
     * @param WaypointFactory $sucursalFactory
     */
    public function __construct(
        Context $context,
        Registry $coreRegistry,
        PageFactory $resultPageFactory,
        WaypointFactory $sucursalFactory
    )
    {
        parent::__construct($context);
        $this->_sucursalFactory     = $sucursalFactory;
        $this->_coreRegistry        = $coreRegistry;
        $this->_resultPageFactory   = $resultPageFactory;
    }

    /**
     * @return ResultInterface|\Magento\Framework\View\Result\Page|void
     */
    public function execute()
    {
        $rowId = (int) $this->getRequest()->getParam('id');
        $rowData = $this->_sucursalFactory->create();

        if ($rowId) {
            $rowData = $rowData->load($rowId);
            $rowTitle = $rowData->getName();

            if (!$rowData->getEntityId()) {
                $this->messageManager->addErrorMessage(__('Row data no longer exist.'));
                $this->_redirect('pedidosya/waypoint/index');
                return;
            }
        }

        $this->_coreRegistry->register('row_data', $rowData);

        $resultPage = $this->_resultPageFactory->create();
        $title = $rowId ? __('Edit Waypoint ').$rowTitle : __('Create Waypoint');

        $resultPage->getConfig()->getTitle()->prepend($title);
        return $resultPage;
    }

    /**
     * @return bool
     */
    protected function _isAllowed()
    {
        return $this->_authorization->isAllowed('Improntus_PedidosYa::waypoint_edit');
    }
}
