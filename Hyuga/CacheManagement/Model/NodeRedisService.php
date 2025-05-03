<?php
/**
 * Hyuga_CacheManagement
 *
 * PHP version 8.x
 *
 * @category  PHP
 * @package   Hyuga\CacheManagement
 * @author    Puneet Mittal <puneet.mittal@pratechbrands.com>
 * @copyright 2025 Copyright (c) Pratech Brands Private Limited
 * @link      https://pratechbrands.com/
 **/

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
                if ($this->validateExistingKey(self::NODE_CACHING_LIST['dark_store_url_list'])) {
                    $this->redisConnection->del($this->getKeys(self::NODE_CACHING_LIST['dark_store_url_list']));
                }
                if ($this->validateExistingKey(self::NODE_CACHING_LIST['pincode_serviceability'] . "*")) {
                    $this->redisConnection->del(
                        $this->getKeys(self::NODE_CACHING_LIST['pincode_serviceability'] . "*")
                    );
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
                if ($this->validateExistingKey(self::NODE_CACHING_LIST['category_id_slug_mapping'])) {
                    $this->redisConnection->del($this->getKeys(self::NODE_CACHING_LIST['category_id_slug_mapping']));
                }
            }
        } catch (Exception $e) {
            $this->cachingLogger->error("cleanCategoryIdSlugMapping failed | "
                . $e->getMessage() . " | Trace: " . $e->getTraceAsString());
        }
    }
}
