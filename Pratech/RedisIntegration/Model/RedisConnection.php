<?php
/**
 * Pratech_RedisIntegration
 *
 * PHP version 8.x
 *
 * @category  PHP
 * @package   Pratech\RedisIntegration
 * @author    Puneet Mittal <puneet.mittal@pratechbrands.com>
 * @copyright 2023 Copyright (c) Pratech Brands Private Limited
 * @link      https://pratechbrands.com/
 **/

namespace Pratech\RedisIntegration\Model;

use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\Encryption\EncryptorInterface;
use Magento\Store\Model\ScopeInterface;
use Pratech\Base\Logger\ConnectionLogger;
use Predis\Client as Client;

/**
 * Class to establish redis connection.
 */
class RedisConnection
{
    /**
     * REDIS HOST Constant
     */
    public const REDIS_HOST = 'redis/credentials/host';

    /**
     * REDIS PASSWORD Constant
     */
    public const REDIS_PASSWORD = 'redis/credentials/password';

    /**
     * Redis Connection Constructor.
     *
     * @param ScopeConfigInterface $scopeConfig
     * @param ConnectionLogger $connectionLogger
     * @param EncryptorInterface $encryptor
     */
    public function __construct(
        private ScopeConfigInterface $scopeConfig,
        private ConnectionLogger     $connectionLogger,
        private EncryptorInterface   $encryptor
    ) {
    }

    /**
     * Connect Redis
     *
     * @return Client|null
     */
    public function connect(): ?Client
    {
        try {
            $redisHost = $this->scopeConfig->getValue(self::REDIS_HOST, ScopeInterface::SCOPE_STORE);
            $redisPassword = $this->getPassword();
            if ($redisPassword) {
                return new Client([
                    'host'   => $redisHost,
                    'password' => $redisPassword
                ]);
            }
            return new Client($redisHost);
        } catch (\Exception $exception) {
            $this->connectionLogger->error($exception->getMessage() . __METHOD__);
        }
        return null;
    }

    /**
     * Get Redis Password
     *
     * @return string
     */
    private function getPassword(): string
    {
        $password = $this->scopeConfig->getValue(
            self::REDIS_PASSWORD,
            ScopeInterface::SCOPE_STORE
        );
        return $this->encryptor->decrypt($password);
    }
}
