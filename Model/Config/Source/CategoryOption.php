<?php

namespace Improntus\PedidosYa\Model\Config\Source;

use Improntus\PedidosYa\Model\Webservice;
use Magento\Framework\Option\ArrayInterface;

/**
 * Class CategoryOption
 * @author Improntus <http://www.improntus.com> - Adobe Gold Technology Partner | Adobe Gold Solution Partner
 * @copyright Copyright (c) 2026 Improntus
 * @package Improntus\PedidosYa\Model\Config\Source
 */
class CategoryOption implements ArrayInterface
{
    /**
     * @var Webservice
     */
    protected $_webService;

    /**
     * @param Webservice $webService
     */
    public function __construct(
        Webservice $webService
    )
    {
        $this->_webService = $webService;
    }

    /**
     * @return array
     */
    public function toOptionArray()
    {
        $options = [];
        $categories = $this->_webService->getCategories();

        if($categories) {
            foreach ($categories as $_category) {
                $options[] = ['value' => $_category->id, 'label' => __($_category->name)];
            }
        } else {
            $options[] = ['value' => 999999, 'label' => __('Error WS')];
        }
        return $options;
    }
}

