<?php

namespace Improntus\PedidosYa\Controller\Adminhtml\Waypoint;

use Exception;
use Improntus\PedidosYa\Model\WaypointFactory;
use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Backend\Model\View\Result\Redirect;
use Magento\Framework\App\Action\HttpPostActionInterface;
use Magento\Framework\Exception\LocalizedException;

/**
 * Class Delete
 * @author Improntus <http://www.improntus.com> - Adobe Gold Technology Partner | Adobe Gold Solution Partner
 * @copyright Copyright (c) 2026 Improntus
 * @package Improntus\PedidosYa\Controller\Adminhtml\Waypoint
 */
class Delete extends Action implements HttpPostActionInterface
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
