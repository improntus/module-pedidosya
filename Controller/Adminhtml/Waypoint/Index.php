<?php

namespace Improntus\PedidosYa\Controller\Adminhtml\Waypoint;

use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\Controller\ResultInterface;
use Magento\Framework\View\Result\Page;
use Magento\Framework\View\Result\PageFactory;

/**
 * Class Index
 * @author Improntus <http://www.improntus.com> - Elevating Digital Transformation | Adobe Solution Partner
 * @copyright Copyright (c) 2025 Improntus
 * @package Improntus\PedidosYa\Controller\Adminhtml\Waypoint
 */
class Index extends Action
{
    /**
     * @var PageFactory
     */
    private $pageFactory;

    public function __construct(
        Context $context,
        PageFactory $rawFactory
    ) {
        $this->pageFactory = $rawFactory;
        parent::__construct($context);
    }

    /**
     * @return ResultInterface|Page
     */
    public function execute()
    {
        $resultPage = $this->pageFactory->create();
        $resultPage->setActiveMenu('Improntus_PedidosYa::waypoint');
        $resultPage->getConfig()->getTitle()->prepend(__('Waypoints'));

        return $resultPage;
    }

    /**
     * @return bool
     */
    protected function _isAllowed()
    {
        return $this->_authorization->isAllowed('Improntus_PedidosYa::waypoints');
    }
}
