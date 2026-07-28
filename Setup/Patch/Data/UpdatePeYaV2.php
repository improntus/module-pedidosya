<?php
/**
 * @author Improntus Dev Team
 * @copyright Copyright (c) 2026 Improntus (http://www.improntus.com/)
 */
namespace Improntus\PedidosYa\Setup\Patch\Data;

use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\App\Config\Storage\WriterInterface;
use Magento\Framework\Encryption\EncryptorInterface;
use Magento\Framework\Setup\ModuleDataSetupInterface;
use Magento\Framework\Setup\Patch\DataPatchInterface;

class UpdatePeYaV2 implements DataPatchInterface
{
    /**
     * @var ModuleDataSetupInterface $moduleDataSetup
     */
    protected $moduleDataSetup;

    /**
     * @var WriterInterface $writer
     */
    protected $writer;

    /**
     * @var EncryptorInterface $encryptor
     */
    protected $encryptor;

    /**
     * @var ScopeConfigInterface $scopeConfig
     */
    protected $scopeConfig;

    /**
     * @param ModuleDataSetupInterface $moduleDataSetup
     * @param WriterInterface $writer
     * @param ScopeConfigInterface $scopeConfig
     * @param EncryptorInterface $encryptor
     */
    public function __construct(
        ModuleDataSetupInterface $moduleDataSetup,
        WriterInterface $writer,
        ScopeConfigInterface $scopeConfig,
        EncryptorInterface $encryptor
    ) {
        $this->moduleDataSetup = $moduleDataSetup;
        $this->writer = $writer;
        $this->scopeConfig = $scopeConfig;
        $this->encryptor = $encryptor;
    }

    public static function getDependencies()
    {
        return [];
    }

    public function getAliases()
    {
        return [];
    }

    public function apply()
    {
        $this->moduleDataSetup->startSetup();

        $connection = $this->moduleDataSetup->getConnection();
        $configTable = $this->moduleDataSetup->getTable('core_config_data');

        /**
         * Map legacy plaintext credential paths to their new (encrypted) e-commerce paths.
         */
        $legacyMap = [
            'shipping/pedidosya/client_id'     => 'shipping/pedidosya/ecommerce/client_id',
            'shipping/pedidosya/client_secret' => 'shipping/pedidosya/ecommerce/client_secret',
            'shipping/pedidosya/username'      => 'shipping/pedidosya/ecommerce/username',
            'shipping/pedidosya/password'      => 'shipping/pedidosya/ecommerce/password',
        ];

        foreach ($legacyMap as $oldPath => $newPath) {
            /**
             * Migrate across ALL scopes (default / websites / stores) so per-scope
             * credentials are preserved, encrypting each value on write.
             */
            $select = $connection->select()
                ->from($configTable, ['scope', 'scope_id', 'value'])
                ->where('path = ?', $oldPath);

            foreach ($connection->fetchAll($select) as $row) {
                if ($row['value'] === null || $row['value'] === '') {
                    continue;
                }
                $this->writer->save(
                    $newPath,
                    $this->encryptor->encrypt($row['value']),
                    $row['scope'],
                    (int) $row['scope_id']
                );
            }

            /**
             * Remove the legacy path across every scope.
             */
            $connection->delete($configTable, ['path = ?' => $oldPath]);
        }

        // Set integration Mode E-commerce (Legacy) at default scope
        $this->writer->save('shipping/pedidosya/integration_mode', 'eco');

        $this->moduleDataSetup->endSetup();
    }
}
