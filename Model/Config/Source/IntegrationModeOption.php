<?php

namespace Improntus\PedidosYa\Model\Config\Source;

use Magento\Framework\Data\OptionSourceInterface;

/**
 * Class IntegrationModeOption
 * @author Improntus <http://www.improntus.com> - Ecommerce done right
 * @copyright Copyright (c) 2023 Improntus
 * @package Improntus\PedidosYa\Model\Config\Source
 */
class IntegrationModeOption implements OptionSourceInterface
{

    public function toOptionArray()
    {
        return [
            ['label' => '-- Select --', 'value' => ''],
            ['label' => 'API', 'value' => 1],
            ['label' => 'E-commerce', 'value' => 0],
        ];
    }
}
