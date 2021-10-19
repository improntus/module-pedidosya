<?php

namespace Improntus\PedidosYa\Block\Adminhtml\Waypoint\Edit;

use Magento\Backend\Block\Widget\Context;
use Magento\Framework\Phrase;
use Magento\Framework\Registry;

/**
 * Class Container
 * @package Improntus\PedidosYa\Block\Adminhtml\Waypoint\Edit
 */
class Container extends \Magento\Backend\Block\Widget\Form\Container
{
    /**
     * @var Registry|null
     */
    protected $_coreRegistry = null;

    /**
     * @param Context $context
     * @param Registry $registry
     * @param array $data
     */
    public function __construct(
        Context $context,
        Registry $registry,
        array $data = []
    )
    {
        $this->_coreRegistry = $registry;
        parent::__construct($context, $data);
    }

    protected function _construct()
    {
        $this->_blockGroup = 'Improntus_PedidosYa';
        $this->_controller = 'adminhtml_waypoint';
        parent::_construct();

        if ($this->_isAllowedAction('Improntus_PedidosYa::waypoint_save'))
            $this->buttonList->update('save', 'label', __('Save'));
        else
            $this->buttonList->remove('save');

        if ($this->_isAllowedAction('Improntus_PedidosYa::waypoint_delete'))
            $this->buttonList->update('delete', 'label', __('Delete'));
        else
            $this->buttonList->remove('delete');

        $this->buttonList->remove('reset');
    }

    /**
     * @return Phrase|string
     */
    public function getHeaderText()
    {
        return __('Add Row Data');
    }

    /**
     * @param $resourceId
     * @return bool
     */
    protected function _isAllowedAction($resourceId)
    {
        return $this->_authorization->isAllowed($resourceId);
    }

    /**
     * @return array|mixed|string|null
     */
    public function getFormActionUrl()
    {
        if ($this->hasFormActionUrl())
            return $this->getData('form_action_url');

        return $this->getUrl('*/*/save');
    }

    /**
     * @return string
     */
    public function getBackUrl()
    {
        return $this->getUrl('pedidosya/waypoint/index');
    }

    /**
     * @return string
     */
    public function getDeleteUrl()
    {
        return $this->getUrl('pedidosya/waypoint/delete', [$this->_objectId => $this->getRequest()->getParam($this->_objectId)]);
    }
}