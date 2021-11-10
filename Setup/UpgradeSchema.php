<?php

namespace Improntus\PedidosYa\Setup;

use Magento\Framework\DB\Ddl\Table;
use Magento\Framework\Setup\ModuleContextInterface;
use Magento\Framework\Setup\SchemaSetupInterface;
use Magento\Framework\Setup\UpgradeSchemaInterface;

/**
 * Class UpgradeSchema
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
        $connection = $installer->getConnection();

        if (version_compare($context->getVersion(), '1.0.17', '<=')) {
            $connection->query("ALTER TABLE `improntus_pedidosya` ADD UNIQUE INDEX `improntus_pedidosya_order_id` (`order_id`);");
        }

        $setup->endSetup();
    }
}
