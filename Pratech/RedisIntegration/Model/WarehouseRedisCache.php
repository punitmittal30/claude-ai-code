<?php
/**
 * Pratech_RedisIntegration
 *
 * PHP version 8.x
 *
 * @category  PHP
 * @package   Pratech\RedisIntegration
 * @author    Puneet Mittal <puneet.mittal@pratechbrands.com>
 * @copyright 2025 Copyright (c) Pratech Brands Private Limited
 * @link      https://pratechbrands.com/
 **/

namespace Pratech\RedisIntegration\Model;

use Exception;
use Pratech\RedisIntegration\Logger\RedisCacheLogger;
use Predis\Client;

class WarehouseRedisCache
{
    /**
     * Warehouse URL identifier
     */
    public const WAREHOUSE_URL_LIST = "warehouse:url:list";

    /**
     * @var Client|null
     */
    protected ?Client $redisConnection;

    /**
     * @param RedisConnection $redisConnection
     * @param RedisCacheLogger $redisCacheLogger
     */
    public function __construct(
        RedisConnection          $redisConnection,
        private RedisCacheLogger $redisCacheLogger,
    ) {
        $this->redisConnection = $redisConnection->connect();
    }

    /**
     * Delete Warehouse URL.
     *
     * @return void
     */
    public function deleteWarehouseUrlList(): void
    {
        try {
            if ($this->redisConnection) {
                if ($this->validateExistingKey(self::WAREHOUSE_URL_LIST)) {
                    $this->redisConnection->del($this->getKeys(self::WAREHOUSE_URL_LIST));
                }
            }
        } catch (Exception $e) {
            $this->redisCacheLogger->error("deleteWarehouseUrlList cache clearing issue | "
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
