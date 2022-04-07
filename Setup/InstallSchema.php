<?php

namespace Improntus\PedidosYa\Setup;

use Magento\Framework\Setup\InstallSchemaInterface;
use Magento\Framework\Setup\ModuleContextInterface;
use Magento\Framework\Setup\SchemaSetupInterface;
use Magento\Framework\DB\Ddl\Table;
use Zend_Db_Exception;

/**
 * Class InstallSchema
 * @author Improntus <http://www.improntus.com> - Ecommerce done right
 * @copyright Copyright (c) 2022 Improntus
 * @package Improntus\PedidosYa\Setup
 */
class InstallSchema implements InstallSchemaInterface
{
    /**
     * @param SchemaSetupInterface $setup
     * @param ModuleContextInterface $context
     * @throws Zend_Db_Exception
     */
    public function install(SchemaSetupInterface $setup, ModuleContextInterface $context)
    {
        $installer = $setup;
        $installer->startSetup();

        $pedidosYaEstimateData = [
            'type'    => Table::TYPE_TEXT,
            'nullable'=> true,
            'comment' => 'Pedidos Ya Estimate data',
            'default' => null
        ];

        if (!$installer->getConnection()->tableColumnExists($installer->getTable('quote'), 'pedidosya_estimatedata'))
        {
            $installer->getConnection()->addColumn($installer->getTable('quote'), 'pedidosya_estimatedata', $pedidosYaEstimateData);
        }

        if (!$installer->getConnection()->tableColumnExists($installer->getTable('sales_order'), 'pedidosya_estimatedata'))
        {
            $installer->getConnection()->addColumn($installer->getTable('sales_order'), 'pedidosya_estimatedata', $pedidosYaEstimateData);
        }

        $pedidosYaEstimatedata = [
            'type'    => Table::TYPE_TEXT,
            'nullable'=> true,
            'comment' => 'Pedidos Ya Source Waypoint',
            'default' => null
        ];

        if (!$installer->getConnection()->tableColumnExists($installer->getTable('quote'), 'pedidosya_source_waypoint'))
        {
            $installer->getConnection()->addColumn($installer->getTable('quote'), 'pedidosya_source_waypoint', $pedidosYaEstimatedata);
        }

        if (!$installer->getConnection()->tableColumnExists($installer->getTable('sales_order'), 'pedidosya_source_waypoint'))
        {
            $installer->getConnection()->addColumn($installer->getTable('sales_order'), 'pedidosya_source_waypoint', $pedidosYaEstimatedata);
        }

        $pedidosYa = $installer->getConnection()
            ->newTable($installer->getTable('improntus_pedidosya'))
            ->addColumn(
                'entity_id',
                Table::TYPE_INTEGER,
                null,
                ['identity' => true, 'nullable' => false, 'primary' => true],
                'Id Autoincremental'
            )
            ->addColumn(
                'order_id',
                Table::TYPE_INTEGER,
                null,
                ['nullable' => false],
                'Order Id'
            )
            ->addColumn('increment_id', Table::TYPE_TEXT, null, ['nullable' => false])
            ->addColumn('info_preorder', Table::TYPE_TEXT, null, ['nullable' => true])
            ->addColumn('info_confirmed', Table::TYPE_TEXT, null, ['nullable' => true])
            ->addColumn('info_cancelled', Table::TYPE_TEXT, null, ['nullable' => true])
            ->addColumn('status', Table::TYPE_TEXT, null, ['nullable' => true])
            ->setComment('Save Pedidos Ya data');


        $installer->getConnection()->createTable($pedidosYa);

        $pedidosYaToken = $installer->getConnection()
            ->newTable($installer->getTable('improntus_pedidosya_token'))
            ->addColumn(
                'entity_id',
                Table::TYPE_INTEGER,
                null,
                ['identity' => true, 'nullable' => false, 'primary' => true],
                'Entity Id'
            )
            ->addColumn(
                'token',
                Table::TYPE_TEXT,
                255,
                ['nullable' => false],
                'Token'
            )
            ->addColumn(
                'lastest_use',
                Table::TYPE_TIMESTAMP,
                null,
                ['nullable' => false, 'default' => Table::TIMESTAMP_INIT],
                'Lastest use'
            )
            ->setComment('Pedidos Ya token data');

        $installer->getConnection()->createTable($pedidosYaToken);

        $pedidosYaWaypoint = $installer->getConnection()
            ->newTable($installer->getTable('improntus_pedidosya_waypoint'))
            ->addColumn(
                'entity_id',
                Table::TYPE_INTEGER,
                null,
                ['identity' => true, 'nullable' => false, 'primary' => true],
                'Entity Id'
            )
            ->addColumn(
                'enabled',
                Table::TYPE_BOOLEAN,
                null,
                ['nullable' => false],
                'Enabled'
            )
            ->addColumn(
                'name',
                Table::TYPE_TEXT,
                255,
                ['nullable' => false],
                'Name'
            )
            ->addColumn(
                'address',
                Table::TYPE_TEXT,
                255,
                ['nullable' => false],
                'Address'
            )
            ->addColumn(
                'additional_information',
                Table::TYPE_TEXT,
                255,
                ['nullable' => false],
                'Address additional information'
            )
            ->addColumn(
                'telephone',
                Table::TYPE_TEXT,
                255,
                ['nullable' => false],
                'Telephone'
            )
            ->addColumn(
                'working_hours_monday_open',
                Table::TYPE_TEXT,
                255,
                ['nullable' => true],
                'Monday - Open Working hour'
            )
            ->addColumn(
                'working_hours_monday_close',
                Table::TYPE_TEXT,
                255,
                ['nullable' => true],
                'Monday - Close Working hour'
            )
            ->addColumn(
                'working_hours_tuesday_open',
                Table::TYPE_TEXT,
                255,
                ['nullable' => true],
                'Tuesday - Open Working hour'
            )
            ->addColumn(
                'working_hours_tuesday_close',
                Table::TYPE_TEXT,
                255,
                ['nullable' => true],
                'Tuesday - Close Working hour'
            )
            ->addColumn(
                'working_hours_wednesday_open',
                Table::TYPE_TEXT,
                255,
                ['nullable' => true],
                'Wednesday - Open Working hour'
            )
            ->addColumn(
                'working_hours_wednesday_close',
                Table::TYPE_TEXT,
                255,
                ['nullable' => true],
                'Wednesday - Close Working hour'
            )
            ->addColumn(
                'working_hours_thursday_open',
                Table::TYPE_TEXT,
                255,
                ['nullable' => true],
                'Thursday - Open Working hour'
            )
            ->addColumn(
                'working_hours_thursday_close',
                Table::TYPE_TEXT,
                255,
                ['nullable' => true],
                'Thursday - Close Working hour'
            )
            ->addColumn(
                'working_hours_friday_open',
                Table::TYPE_TEXT,
                255,
                ['nullable' => true],
                'Friday - Open Working hour'
            )
            ->addColumn(
                'working_hours_friday_close',
                Table::TYPE_TEXT,
                255,
                ['nullable' => true],
                'Friday - Close Working hour'
            )
            ->addColumn(
                'working_hours_saturday_open',
                Table::TYPE_TEXT,
                255,
                ['nullable' => true],
                'Saturday - Open Working hour'
            )
            ->addColumn(
                'working_hours_saturday_close',
                Table::TYPE_TEXT,
                255,
                ['nullable' => true],
                'Saturday - Close Working hour'
            )
            ->addColumn(
                'working_hours_sunday_open',
                Table::TYPE_TEXT,
                255,
                ['nullable' => true],
                'Sunday - Open Working hour'
            )
            ->addColumn(
                'working_hours_sunday_close',
                Table::TYPE_TEXT,
                255,
                ['nullable' => true],
                'Sunday - Close Working hour'
            )
            ->addColumn(
                'region',
                Table::TYPE_TEXT,
                255,
                ['nullable' => false],
                'Region'
            )
            ->addColumn(
                'city',
                Table::TYPE_TEXT,
                255,
                ['nullable' => false],
                'City'
            )
            ->addColumn(
                'postcode',
                Table::TYPE_INTEGER,
                10,
                ['nullable' => false],
                'Postcode'
            )
            ->addColumn(
                'instructions',
                Table::TYPE_TEXT,
                255,
                ['nullable' => false],
                'Instructions'
            )
            ->addColumn(
                'latitude',
                Table::TYPE_TEXT,
                60,
                ['nullable' => false],
                'Latitude'
            )
            ->addColumn(
                'longitude',
                Table::TYPE_TEXT,
                60,
                ['nullable' => false],
                'Longitude'
            )
            ->setComment('Pedidos Ya Waypoints');

        $installer->getConnection()->createTable($pedidosYaWaypoint);
        $installer->endSetup();
    }
}
