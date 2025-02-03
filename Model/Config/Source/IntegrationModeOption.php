<?php

namespace Improntus\PedidosYa\Model\Config\Source;

use Magento\Framework\Data\OptionSourceInterface;

/**
 * Class IntegrationModeOption
 * @author Improntus <http://www.improntus.com> - Elevating Digital Transformation | Adobe Solution Partner
 * @copyright Copyright (c) 2025 Improntus
 * @package Improntus\PedidosYa\Model\Config\Source
 */
class IntegrationModeOption implements OptionSourceInterface
{

    public function toOptionArray()
    {
        return [
            ['label' => '-- Select --', 'value' => 0],
            ['label' => 'API', 'value' => 'api'],
            ['label' => 'E-commerce', 'value' => 'eco'],
        ];
    }
}
