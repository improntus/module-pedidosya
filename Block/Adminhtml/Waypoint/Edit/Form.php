<?php

namespace Improntus\PedidosYa\Block\Adminhtml\Waypoint\Edit;

use Improntus\PedidosYa\Model\Config\Source\TimeOption;
use Improntus\PedidosYa\Model\Waypoint;
use Magento\Backend\Block\Template\Context;
use Magento\Backend\Block\Widget\Form\Generic;
use Magento\Config\Model\Config\Source\Yesno;
use Magento\Framework\Data\FormFactory;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Registry;
use Magento\Store\Model\System\Store;

/**
 * Class Form
 * @author Improntus <http://www.improntus.com> - Ecommerce done right
 * @copyright Copyright (c) 2023 Improntus
 * @package Improntus\PedidosYa\Block\Adminhtml\Waypoint\Edit
 */
class Form extends Generic
{
    /**
     * @var Store
     */
    protected $_systemStore;

    /**
     * @var Yesno
     */
    protected $_options;

    /**
     * @var TimeOption
     */
    protected $_timeOptions;

    /**
     * @param Context $context
     * @param Registry $registry
     * @param FormFactory $formFactory
     * @param Yesno $options
     * @param TimeOption $timeOptions
     * @param array $data
     */
    public function __construct(
        Context $context,
        Registry $registry,
        FormFactory $formFactory,
        Yesno $options,
        TimeOption $timeOptions,
        array $data = []
    ) {
        $this->_options     = $options;
        $this->_timeOptions = $timeOptions;
        parent::__construct($context, $registry, $formFactory, $data);
    }

    /**
     * @return Form
     * @throws LocalizedException
     */
    protected function _prepareForm()
    {
        $model = $this->_coreRegistry->registry('row_data');
        $form = $this->_formFactory->create(
            [
                'data' =>
                [
                    'id'        => 'edit_form',
                    'enctype'   => 'multipart/form-data',
                    'action'    => $this->getData('action'),
                    'method'    => 'post'
                ]
            ]
        );

        if ($model instanceof Waypoint && $model->getEntityId()) {
            $fieldset = $form->addFieldset(
                'base_fieldset',
                ['legend' =>'', 'class' => 'fieldset-wide']
            );
            $fieldset->addField('entity_id', 'hidden', ['name' => 'entity_id']);
        } else {
            $fieldset = $form->addFieldset(
                'base_fieldset',
                ['legend' => '', 'class' => 'fieldset-wide']
            );
        }

        $fieldset->addField(
            'enabled',
            'select',
            [
                'name' => 'enabled',
                'label' => __('Enabled'),
                'id' => 'enabled',
                'title' => __('Enabled'),
                'values' => $this->_options->toOptionArray(),
                'required' => true,
            ]
        );

        $fieldset->addField(
            'name',
            'text',
            [
                'name' => 'name',
                'label' => __('Name'),
                'id' => 'name',
                'title' => __('Name'),
                'class' => 'required-entry',
                'required' => true,
            ]
        );

        $fieldset->addField(
            'address',
            'text',
            [
                'name' => 'address',
                'label' => __('Address'),
                'id' => 'address',
                'title' => __('Address'),
                'class' => 'required-entry',
                'required' => true
            ]
        );

        $fieldset->addField(
            'telephone',
            'text',
            [
                'name' => 'telephone',
                'label' => __('Telephone'),
                'id' => 'telephone',
                'title' => __('Telephone'),
                'required' => true
            ]
        );

        $fieldset->addField(
            'instructions',
            'text',
            [
                'name' => 'instructions',
                'label' => __('Instructions'),
                'id' => 'instructions',
                'title' => __('Instructions'),
                'required' => false
            ]
        );

        $fieldset->addField(
            'region',
            'text',
            [
                'name' => 'region',
                'label' => __('Region'),
                'id' => 'region',
                'title' => __('Region'),
                'class' => 'required-entry',
                'required' => true
            ]
        );

        $fieldset->addField(
            'city',
            'text',
            [
                'name' => 'city',
                'label' => __('City'),
                'id' => 'city',
                'title' => __('City'),
                'class' => 'required-entry',
                'required' => true
            ]
        );

        $fieldset->addField(
            'postcode',
            'text',
            [
                'name' => 'postcode',
                'label' => __('Postcode'),
                'id' => 'postcode',
                'title' => __('Postcode'),
                'class' => 'required-entry validate-numbers',
                'required' => true
            ]
        );

        $fieldset->addField(
            'latitude',
            'text',
            [
                'name' => 'latitude',
                'label' => __('Latitude'),
                'id' => 'latitude',
                'title' => __('Latitude'),
                'class' => 'required-entry',
                'required' => true
            ]
        );

        $fieldset->addField(
            'longitude',
            'text',
            [
                'name' => 'longitude',
                'label' => __('Longitude'),
                'id' => 'longitude',
                'title' => __('Longitude'),
                'class' => 'required-entry',
                'required' => true
            ]
        );

        $fieldset->addField(
            'working_hours_monday_open',
            'select',
            [
                'name' => 'working_hours_monday_open',
                'label' => __('Monday - Open Working Hour'),
                'id' => 'working_hours_monday_open',
                'title' => __('Monday - Open Working Hour'),
                'required' => false,
                'values' => $this->_timeOptions->toOptionArray(),
                'class' => 'select'
            ]
        );

        $fieldset->addField(
            'working_hours_monday_close',
            'select',
            [
                'name' => 'working_hours_monday_close',
                'label' => __('Monday - Close Working Hour'),
                'id' => 'working_hours_monday_close',
                'title' => __('Monday - Close Working Hour'),
                'required' => false,
                'values' => $this->_timeOptions->toOptionArray(),
                'class' => 'select'
            ]
        );

        $fieldset->addField(
            'working_hours_tuesday_open',
            'select',
            [
                'name' => 'working_hours_tuesday_open',
                'label' => __('Tuesday - Open Working Hour'),
                'id' => 'working_hours_tuesday_open',
                'title' => __('Tuesday - Open Working Hour'),
                'required' => false,
                'values' => $this->_timeOptions->toOptionArray(),
                'class' => 'select'
            ]
        );

        $fieldset->addField(
            'working_hours_tuesday_close',
            'select',
            [
                'name' => 'working_hours_tuesday_close',
                'label' => __('Tuesday - Close Working Hour'),
                'id' => 'working_hours_tuesday_close',
                'title' => __('Tuesday - Close Working Hour'),
                'required' => false,
                'values' => $this->_timeOptions->toOptionArray(),
                'class' => 'select'
            ]
        );

        $fieldset->addField(
            'working_hours_wednesday_open',
            'select',
            [
                'name' => 'working_hours_wednesday_open',
                'label' => __('Wednesday - Open Working Hour'),
                'id' => 'working_hours_wednesday_open',
                'title' => __('Wednesday - Open Working Hour'),
                'required' => false,
                'values' => $this->_timeOptions->toOptionArray(),
                'class' => 'select'
            ]
        );

        $fieldset->addField(
            'working_hours_wednesday_close',
            'select',
            [
                'name' => 'working_hours_wednesday_close',
                'label' => __('Wednesday - Close Working Hour'),
                'id' => 'working_hours_wednesday_close',
                'title' => __('Wednesday - Close Working Hour'),
                'required' => false,
                'values' => $this->_timeOptions->toOptionArray(),
                'class' => 'select'
            ]
        );

        $fieldset->addField(
            'working_hours_thursday_open',
            'select',
            [
                'name' => 'working_hours_thursday_open',
                'label' => __('Thursday - Open Working Hour'),
                'id' => 'working_hours_thursday_open',
                'title' => __('Thursday - Open Working Hour'),
                'required' => false,
                'values' => $this->_timeOptions->toOptionArray(),
                'class' => 'select'
            ]
        );

        $fieldset->addField(
            'working_hours_thursday_close',
            'select',
            [
                'name' => 'working_hours_thursday_close',
                'label' => __('Thursday - Close Working Hour'),
                'id' => 'working_hours_thursday_close',
                'title' => __('Thursday - Close Working Hour'),
                'required' => false,
                'values' => $this->_timeOptions->toOptionArray(),
                'class' => 'select'
            ]
        );

        $fieldset->addField(
            'working_hours_friday_open',
            'select',
            [
                'name' => 'working_hours_friday_open',
                'label' => __('Friday - Open Working Hour'),
                'id' => 'working_hours_friday_open',
                'title' => __('Friday - Open Working Hour'),
                'required' => false,
                'values' => $this->_timeOptions->toOptionArray(),
                'class' => 'select'
            ]
        );

        $fieldset->addField(
            'working_hours_friday_close',
            'select',
            [
                'name' => 'working_hours_friday_close',
                'label' => __('Friday - Close Working Hour'),
                'id' => 'working_hours_friday_close',
                'title' => __('Friday - Close Working Hour'),
                'required' => false,
                'values' => $this->_timeOptions->toOptionArray(),
                'class' => 'select'
            ]
        );

        $fieldset->addField(
            'working_hours_saturday_open',
            'select',
            [
                'name' => 'working_hours_saturday_open',
                'label' => __('Saturday - Open Working Hour'),
                'id' => 'working_hours_saturday_open',
                'title' => __('Saturday - Open Working Hour'),
                'required' => false,
                'values' => $this->_timeOptions->toOptionArray(),
                'class' => 'select'
            ]
        );

        $fieldset->addField(
            'working_hours_saturday_close',
            'select',
            [
                'name' => 'working_hours_saturday_close',
                'label' => __('Saturday - Close Working Hour'),
                'id' => 'working_hours_saturday_close',
                'title' => __('Saturday - Close Working Hour'),
                'required' => false,
                'values' => $this->_timeOptions->toOptionArray(),
                'class' => 'select'
            ]
        );

        $fieldset->addField(
            'working_hours_sunday_open',
            'select',
            [
                'name' => 'working_hours_sunday_open',
                'label' => __('Sunday - Open Working Hour'),
                'id' => 'working_hours_sunday_open',
                'title' => __('Sunday - Open Working Hour'),
                'required' => false,
                'values' => $this->_timeOptions->toOptionArray(),
                'class' => 'select'
            ]
        );

        $fieldset->addField(
            'working_hours_sunday_close',
            'select',
            [
                'name' => 'working_hours_sunday_close',
                'label' => __('Sunday - Close Working Hour'),
                'id' => 'working_hours_sunday_close',
                'title' => __('Sunday - Close Working Hour'),
                'required' => false,
                'values' => $this->_timeOptions->toOptionArray(),
                'class' => 'select'
            ]
        );

        if ($model instanceof Waypoint && $model->getEntityId()) {
            $form->setValues($model->getData());
        }

        $form->setUseContainer(true);
        $this->setForm($form);

        return parent::_prepareForm();
    }
}
