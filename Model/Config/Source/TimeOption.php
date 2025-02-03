<?php

namespace Improntus\PedidosYa\Model\Config\Source;

use Magento\Framework\Option\ArrayInterface;

/**
 * Class TimeOption
 * @author Improntus <http://www.improntus.com> - Elevating Digital Transformation | Adobe Solution Partner
 * @copyright Copyright (c) 2025 Improntus
 * @package Improntus\PedidosYa\Model\Config\Source
 */
class TimeOption implements ArrayInterface
{
    /**
     * @return array|array[]
     */
    public function toOptionArray()
    {
        return [
            ['value' => '9999', 'label' => __('No available')],
            ['value' => '00', 'label' => __('00:00')],
            ['value' => '01', 'label' => __('01:00')],
            ['value' => '02', 'label' => __('02:00')],
            ['value' => '03', 'label' => __('03:00')],
            ['value' => '04', 'label' => __('04:00')],
            ['value' => '05', 'label' => __('05:00')],
            ['value' => '06', 'label' => __('06:00')],
            ['value' => '07', 'label' => __('07:00')],
            ['value' => '08', 'label' => __('08:00')],
            ['value' => '09', 'label' => __('09:00')],
            ['value' => '10', 'label' => __('10:00')],
            ['value' => '11', 'label' => __('11:00')],
            ['value' => '12', 'label' => __('12:00')],
            ['value' => '13', 'label' => __('13:00')],
            ['value' => '14', 'label' => __('14:00')],
            ['value' => '15', 'label' => __('15:00')],
            ['value' => '16', 'label' => __('16:00')],
            ['value' => '17', 'label' => __('17:00')],
            ['value' => '18', 'label' => __('18:00')],
            ['value' => '19', 'label' => __('19:00')],
            ['value' => '20', 'label' => __('20:00')],
            ['value' => '21', 'label' => __('21:00')],
            ['value' => '22', 'label' => __('22:00')],
            ['value' => '23', 'label' => __('23:00')],
        ];
    }
}

