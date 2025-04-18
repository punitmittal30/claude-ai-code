<?php

namespace Hyuga\CacheManagement\Model;

use Exception;
use Hyuga\CacheManagement\Api\NodeRedisServiceInterface;
use Hyuga\LogManagement\Logger\CachingLogger;
use Pratech\RedisIntegration\Model\RedisConnection;
use Predis\Client;

class NodeRedisService implements NodeRedisServiceInterface
{
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
    ) {
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

    /**
     * Delete Warehouse URL.
     *
     * @return void
     */
    public function cleanCategoryIdSlugMapping(): void
    {
        try {
            if ($this->redisConnection) {
                if ($this->validateExistingKey(self::CATEGORY_ID_SLUG_MAPPING)) {
                    $this->redisConnection->del($this->getKeys(self::CATEGORY_ID_SLUG_MAPPING));
                }
            }
        } catch (Exception $e) {
            $this->cachingLogger->error("cleanCategoryIdSlugMapping failed | "
                . $e->getMessage() . " | Trace: " . $e->getTraceAsString());
        }
    }
}
