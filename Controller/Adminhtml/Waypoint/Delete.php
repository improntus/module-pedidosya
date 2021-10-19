<?php

namespace Improntus\PedidosYa\Controller\Adminhtml\Waypoint;

use Exception;
use Improntus\PedidosYa\Model\WaypointFactory;
use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Backend\Model\View\Result\Redirect;
use Magento\Framework\Exception\LocalizedException;

/**
 * Class Delete
 * @package Improntus\PedidosYa\Controller\Adminhtml\Waypoint
 */
class Delete extends Action
{
    /**
     * @var WaypointFactory
     */
    protected $_waypointFactory;

    /**
     * @param Context $context
     * @param WaypointFactory $waypointFactory
     */
    public function __construct
    (
        Context $context,
        WaypointFactory $waypointFactory
    )
    {
        parent::__construct($context);
        $this->_waypointFactory = $waypointFactory;
    }

    /**
     * @return Redirect
     * @throws Exception
     */
    public function execute()
    {
        /** @var Redirect $resultRedirect */
        $resultRedirect = $this->resultRedirectFactory->create();
        $waypointId = (int)$this->getRequest()->getParam('id');
        $waypoint = $this->_waypointFactory->create()->load($waypointId);

        if($waypoint->getId()) {
            try {
                $waypointName = $waypoint->getName();
                $waypoint->delete();

                $this->messageManager->addSuccessMessage(__('Waypoint %1 was deleted.', $waypointName));
            } catch (LocalizedException $e) {
                $this->messageManager->addErrorMessage($e->getMessage());

                return $resultRedirect->setPath('pedidosya/waypoint/index');
            }
        }
        return $resultRedirect->setPath('pedidosya/waypoint/index');
    }
    protected function _isAllowed()
    {
        return $this->_authorization->isAllowed('Improntus_PedidosYa::waypoint_delete');
    }

}
