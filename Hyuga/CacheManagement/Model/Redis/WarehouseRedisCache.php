<?php

namespace Hyuga\CacheManagement\Model\Redis;

use Exception;
use Hyuga\LogManagement\Logger\CachingLogger;
use Pratech\RedisIntegration\Model\RedisConnection;
use Predis\Client;

class WarehouseRedisCache
{
    /**
     * Identifiers
     */
    public const DARK_STORE_URL_LIST = "warehouse:url:list";
    public const PINCODE_SERVICEABILITY = "pincode:serviceability";

    /**
     * @var Client|null
     */
    protected ?Client $redisConnection;

    /**
     * @param RedisConnection $redisConnection
     * @param CachingLogger $cachingLogger
     */
    public function __construct(
        RedisConnection       $redisConnection,
        private CachingLogger $cachingLogger,
    )
    {
        $this->redisConnection = $redisConnection->connect();
    }

    /**
     * Delete Warehouse URL.
     *
     * @return void
     */
    public function cleanAllPincodeCachesAndDarkStoreSlugs(): void
    {
        try {
            if ($this->redisConnection) {
                if ($this->validateExistingKey(self::DARK_STORE_URL_LIST)) {
                    $this->redisConnection->del($this->getKeys(self::DARK_STORE_URL_LIST));
                }
                if ($this->validateExistingKey(self::PINCODE_SERVICEABILITY . "*")) {
                    $this->redisConnection->del($this->getKeys(self::PINCODE_SERVICEABILITY . "*"));
                }
            }
        } catch (Exception $e) {
            $this->cachingLogger->error("clearPincodeServiceabilityAndDarkStoreSlugs failed | "
                . $e->getMessage() . " | Trace: " . $e->getTraceAsString());
        }
    }

    /**
     * Validate Existing Cache Key.
     *
     * @param string $keyIdentifier
     * @return bool
     */
    public function validateExistingKey(string $keyIdentifier): bool
    {
        return $this->redisConnection && count($this->redisConnection->keys($keyIdentifier));
    }

    /**
     * Get Already Existing Redis Keys.
     *
     * @param string $pattern
     * @return array
     */
    private function getKeys(string $pattern): array
    {
        return $this->redisConnection->keys($pattern);
    }
}
