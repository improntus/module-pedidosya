<?php

namespace Improntus\PedidosYa\Model\Config\Source;

use Magento\Framework\Option\ArrayInterface;

/**
 * Class ModeOption
 * @author Improntus <http://www.improntus.com> - Ecommerce done right
 * @copyright Copyright (c) 2022 Improntus
 * @package Improntus\PedidosYa\Model\Config\Source
 */
class CountryOption implements ArrayInterface
{
    /**
     * @return array|array[]
     */
    public function toOptionArray()
    {
        return [
            ['value' => '', 'label' => __('Select')],
            ['value' => 'AR', 'label' => __('Argentina')],
            ['value' => 'UY', 'label' => __('Uruguay')],
            ['value' => 'CL', 'label' => __('Chile')],
            ['value' => 'PA', 'label' => __('Panamá')],
            ['value' => 'BO', 'label' => __('Bolivia')],
            ['value' => 'DM', 'label' => __('Dominicana')],
            ['value' => 'PY', 'label' => __('Paraguay')],
            ['value' => 'VE', 'label' => __('Venezuela')],
            ['value' => 'PE', 'label' => __('Perú')],
            ['value' => 'EC', 'label' => __('Ecuador')],
            ['value' => 'GT', 'label' => __('Guatemala')],
            ['value' => 'CR', 'label' => __('Costa Rica')],
            ['value' => 'HN', 'label' => __('Honduras')],
            ['value' => 'SV', 'label' => __('El Salvador')],
            ['value' => 'NI', 'label' => __('Nicaragua')]
        ];
    }
}

