<?php
/**
 * @author Improntus Dev Team
 * @copyright Copyright (c) 2023 Improntus (http://www.improntus.com/)
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
     */
    public function __construct(
        ModuleDataSetupInterface $moduleDataSetup,
        WriterInterface $writer,
        ScopeConfigInterface $scopeConfig
    ) {
        $this->moduleDataSetup = $moduleDataSetup;
        $this->writer = $writer;
        $this->scopeConfig = $scopeConfig;
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

        /**
         * Get Old data
         */
        $clientId = $this->scopeConfig->getValue('shipping/pedidosya/client_id');
        $clientSecret = $this->scopeConfig->getValue('shipping/pedidosya/client_secret');
        $username = $this->scopeConfig->getValue('shipping/pedidosya/username');
        $password = $this->scopeConfig->getValue('shipping/pedidosya/password');

        /**
         * Migrate Data
         */
        // ClientId
        if (!is_null($clientId)) {
            $this->writer->save(
                'shipping/pedidosya/ecommerce/client_id',
                $clientId
            );
        }

        // ClientSecret
        if (!is_null($clientSecret)) {
            $this->writer->save(
                'shipping/pedidosya/ecommerce/client_secret',
                $clientSecret
            );
        }

        // Username
        if (!is_null($username)) {
            $this->writer->save(
                'shipping/pedidosya/ecommerce/username',
                $username
            );
        }

        // Password
        if (!is_null($password)) {
            $this->writer->save(
                'shipping/pedidosya/ecommerce/password',
                $password
            );
        }

        // Set integration Mode E-commerce (Legacy)
        $this->writer->save('shipping/pedidosya/integration_mode', 0);

        /**
         * Remove Old Data
         */
        $this->writer->delete('shipping/pedidosya/client_id');
        $this->writer->delete('shipping/pedidosya/client_secret');
        $this->writer->delete('shipping/pedidosya/username');
        $this->writer->delete('shipping/pedidosya/password');

        $this->moduleDataSetup->endSetup();
    }
}