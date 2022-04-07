<?php

namespace Improntus\PedidosYa\Setup;

use Magento\Framework\DB\Adapter\AdapterInterface;
use Magento\Framework\Setup\ModuleContextInterface;
use Magento\Framework\Setup\SchemaSetupInterface;
use Magento\Framework\Setup\UpgradeSchemaInterface;

/**
 * Class UpgradeSchema
 * @author Improntus <http://www.improntus.com> - Ecommerce done right
 * @copyright Copyright (c) 2022 Improntus
 * @package Improntus\PedidosYa\Setup
 */
class UpgradeSchema implements UpgradeSchemaInterface
{
    /**
     * {@inheritdoc}
     */
    public function upgrade(SchemaSetupInterface $setup, ModuleContextInterface $context)
    {
        $installer = $setup;
        $installer->startSetup();

        if (version_compare($context->getVersion(), '1.0.17', '<=')) {
            $setup->getConnection()->addIndex(
                $setup->getTable('improntus_pedidosya'),
                'improntus_pedidosya_order_id',
                'order_id',
                AdapterInterface::INDEX_TYPE_UNIQUE
            );
        }

        $setup->endSetup();
    }
}
