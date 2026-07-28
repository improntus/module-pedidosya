<?php
/**
 * @author Improntus Dev Team
 * @copyright Copyright (c) 2026 Improntus (http://www.improntus.com/)
 */
declare(strict_types=1);

namespace Improntus\PedidosYa\Setup\Patch\Data;

use Magento\Framework\App\Config\Storage\WriterInterface;
use Magento\Framework\App\ResourceConnection;
use Magento\Framework\Encryption\EncryptorInterface;
use Magento\Framework\Setup\Patch\DataPatchInterface;

/**
 * Encrypts any credential still stored as plaintext in core_config_data.
 *
 * The credential fields became type="obscure" (Backend\Encrypted) in system.xml, and the
 * getters in Helper\Data now decrypt() them. Installs that already ran UpdatePeYaV2 keep it in
 * patch_list, so a re-edit of that patch would never execute. This standalone patch runs once on
 * the next setup:upgrade and encrypts in place any value that is not already in Magento's
 * encrypted format, keeping existing carrier authentication working.
 */
class EncryptPedidosYaCredentials implements DataPatchInterface
{
    /**
     * Credential config paths declared as type="obscure" + Backend\Encrypted in system.xml.
     */
    private const CREDENTIAL_PATHS = [
        'shipping/pedidosya/api/token',
        'shipping/pedidosya/ecommerce/client_id',
        'shipping/pedidosya/ecommerce/client_secret',
        'shipping/pedidosya/ecommerce/username',
        'shipping/pedidosya/ecommerce/password',
    ];

    /**
     * @var ResourceConnection
     */
    private ResourceConnection $resourceConnection;

    /**
     * @var WriterInterface
     */
    private WriterInterface $configWriter;

    /**
     * @var EncryptorInterface
     */
    private EncryptorInterface $encryptor;

    /**
     * @param ResourceConnection $resourceConnection
     * @param WriterInterface $configWriter
     * @param EncryptorInterface $encryptor
     */
    public function __construct(
        ResourceConnection $resourceConnection,
        WriterInterface $configWriter,
        EncryptorInterface $encryptor
    ) {
        $this->resourceConnection = $resourceConnection;
        $this->configWriter = $configWriter;
        $this->encryptor = $encryptor;
    }

    /**
     * @return array
     */
    public static function getDependencies()
    {
        return [UpdatePeYaV2::class];
    }

    /**
     * @return array
     */
    public function getAliases()
    {
        return [];
    }

    /**
     * @return $this
     */
    public function apply()
    {
        $connection = $this->resourceConnection->getConnection();
        $configTable = $this->resourceConnection->getTableName('core_config_data');

        foreach (self::CREDENTIAL_PATHS as $path) {
            $select = $connection->select()
                ->from($configTable, ['scope', 'scope_id', 'value'])
                ->where('path = ?', $path);

            foreach ($connection->fetchAll($select) as $row) {
                $value = (string) $row['value'];

                if ($value === '' || $this->isEncrypted($value)) {
                    continue;
                }

                $this->configWriter->save(
                    $path,
                    $this->encryptor->encrypt($value),
                    (string) $row['scope'],
                    (int) $row['scope_id']
                );
            }
        }

        return $this;
    }

    /**
     * Detects Magento's encrypted value format (keyVersion:cipherVersion:payload).
     *
     * @param string $value
     * @return bool
     */
    private function isEncrypted(string $value): bool
    {
        return (bool) preg_match('/^\d+:\d+:/', $value);
    }
}
