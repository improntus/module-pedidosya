<?php

namespace Improntus\PedidosYa\Model\Config\Source;

use Magento\Framework\Data\OptionSourceInterface;

/**
 * Class IntegrationModeOption
 * @author Improntus <http://www.improntus.com> - Adobe Gold Technology Partner | Adobe Gold Solution Partner
 * @copyright Copyright (c) 2026 Improntus
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
