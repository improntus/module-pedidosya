<?php

namespace Improntus\PedidosYa\Setup;

use Magento\Eav\Model\Entity\Attribute\ScopedAttributeInterface;
use Magento\Eav\Setup\EavSetupFactory;
use Magento\Framework\Setup\InstallDataInterface;
use Magento\Framework\Setup\ModuleContextInterface;
use Magento\Framework\Setup\ModuleDataSetupInterface;

/**
 * Class InstallData
 * @author Improntus <http://www.improntus.com> - Ecommerce done right
 * @copyright Copyright (c) 2022 Improntus
 * @package Improntus\PedidosYa\Setup
 */
class InstallData implements InstallDataInterface
{
    /**
     * @var EavSetupFactory
     */
    private $_eavSetupFactory;

    /**
     * @param EavSetupFactory $eavSetupFactory
     */
    public function __construct(EavSetupFactory $eavSetupFactory)
    {
        $this->_eavSetupFactory = $eavSetupFactory;
    }

    /**
     * {@inheritdoc}
     */
    public function install(ModuleDataSetupInterface $setup, ModuleContextInterface $context)
    {
        $setup->startSetup();
        $eavSetup = $this->_eavSetupFactory->create(['setup' => $setup]);
        $eavSetup->addAttribute(
            \Magento\Catalog\Model\Product::ENTITY,
            'volume',
            [
                'frontend'  => '',
                'label'     => 'Volume',
                'input'     => 'text',
                'class'     => '',
                'global'    => ScopedAttributeInterface::SCOPE_GLOBAL,
                'visible'   => true,
                'required'  => false,
                'user_defined' => false,
                'default'   => '',
                'apply_to'  => '',
                'visible_on_front'        => false,
                'is_used_in_grid'         => false,
                'is_visible_in_grid'      => false,
                'is_filterable_in_grid'   => false,
                'used_in_product_listing' => true
            ]
        );

        $setup->endSetup();
    }
}
