<?php

namespace Improntus\PedidosYa\Model\Config\Source;

use Magento\Framework\Option\ArrayInterface;

/**
 * Class ModeOption
 * @author Improntus <http://www.improntus.com> - Ecommerce done right
 * @copyright Copyright (c) 2022 Improntus
 * @package Improntus\PedidosYa\Model\Config\Source
 */
class ModeOption implements ArrayInterface
{
    /**
     * @return array|array[]
     */
    public function toOptionArray()
    {
        return [
            ['value' => 'testing', 'label' => __('Testing')],
            ['value' => 'production', 'label' => __('Production')]
        ];
    }
}

