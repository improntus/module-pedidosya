<?php

namespace Improntus\PedidosYa\Controller\Adminhtml\Waypoint;

use Improntus\PedidosYa\Model\WaypointFactory;
use Magento\Backend\App\Action\Context;
use Magento\Framework\Filesystem;

/**
 * Class Save
 * @author Improntus <http://www.improntus.com> - Elevating Digital Transformation | Adobe Solution Partner
 * @copyright Copyright (c) 2025 Improntus
 * @package Improntus\PedidosYa\Controller\Adminhtml\Waypoint
 */
class Save extends \Magento\Backend\App\Action
{
    /**
     * @var WaypointFactory
     */
    protected $_waypointFactory;

    /**
     * @var Filesystem
     */
    protected $_filesystem;

    /**
     * @param Context $context
     * @param WaypointFactory $waypointFactory
     * @param Filesystem $filesystem
     */
    public function __construct
    (
        Context $context,
        WaypointFactory $waypointFactory,
        Filesystem $filesystem
    )
    {
        $this->_waypointFactory     = $waypointFactory;
        $this->_filesystem          = $filesystem;
        parent::__construct($context);
    }

    /**
     * @return void
     */
    public function execute()
    {
        $data = $this->getRequest()->getPostValue();

        if (!$data) {
            $this->_redirect('pedidosya/waypoint/index');
            return;
        }

        try {
            $rowData = $this->_waypointFactory->create();

            if (isset($data['entity_id'])) {
                $rowData->load($data['entity_id']);
            }

            $rowData->setData($data);
            $rowData->save();
            $this->messageManager->addSuccessMessage('Waypoint was saved');

        } catch (\Exception $e) {
            $this->messageManager->addErrorMessage(__($e->getMessage()));
        }
        $this->_redirect('pedidosya/waypoint/index');
    }

    /**
     * @return bool
     */
    protected function _isAllowed()
    {
        return $this->_authorization->isAllowed('Improntus_PedidosYa::waypoint_edit');
    }
}
