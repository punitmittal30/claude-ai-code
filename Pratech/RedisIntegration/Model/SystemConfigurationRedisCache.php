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

use Predis\Client;

/**
 * Redis Cache Class to update data in redis cache.
 */
class SystemConfigurationRedisCache
{
    /**
     * Order Return Reason Prefix Constant
     */
    public const ORDER_RETURN_REASON_PREFIX = "return:reasons";

    /**
     * Order Return Status Prefix Constant
     */
    public const ORDER_RETURN_STATUS_PREFIX = "return:status";

    /**
     * System Configuration Key Identifier
     */
    public const SYSTEM_CONFIGURATION = "configuration";

    /**
     * Quiz Data Prefix Constant
     */
    public const QUIZ_DATA = "quiz:data";

    /**
     * Videos Data Prefix Constant
     */
    public const VIDEOS_DATA = "videos:data";

    /**
     * Video Carousel Prefix Constant
     */
    public const VIDEO_CAROUSEL_PREFIX = "video:carousel";

    /**
     * @var Client|null
     */
    protected ?Client $redisConnection;

    /**
     * Redis Cache Constructor
     *
     * @param RedisConnection $redisConnection
     */
    public function __construct(
        RedisConnection                     $redisConnection,
    ) {
        $this->redisConnection = $redisConnection->connect();
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
     * Update System Configuration Cache
     *
     * @return void
     */
    public function deleteSystemConfigCache(): void
    {
        $this->redisConnection?->del(self::SYSTEM_CONFIGURATION);
        if ($this->redisConnection && count($this->getKeys(self::SYSTEM_CONFIGURATION))) {
            $this->redisConnection->del($this->getKeys(self::SYSTEM_CONFIGURATION));
        }
    }

    /**
     * Delete Order Return Reasons Cache
     *
     * @return void
     */
    public function deleteOrderReturnReasonsCache(): void
    {
        if ($this->redisConnection && count($this->getKeys(self::ORDER_RETURN_REASON_PREFIX . "*"))) {
            $this->redisConnection->del($this->getKeys(self::ORDER_RETURN_REASON_PREFIX . "*"));
        }
    }

    /**
     * Delete Order Return Statuses Cache
     *
     * @return void
     */
    public function deleteOrderReturnStatusesCache(): void
    {
        if ($this->redisConnection && count($this->getKeys(self::ORDER_RETURN_STATUS_PREFIX . "*"))) {
            $this->redisConnection->del($this->getKeys(self::ORDER_RETURN_STATUS_PREFIX . "*"));
        }
    }

    /**
     * Delete Product By Id.
     *
     * @return void
     */
    public function deleteQuizCache(): void
    {
        if ($this->redisConnection && count($this->getKeys(self::QUIZ_DATA . "*"))) {
            $this->redisConnection->del($this->getKeys(self::QUIZ_DATA . "*"));
        }
    }

    /**
     * Delete Video Cache.
     *
     * @return void
     */
    public function deleteVideoCache(): void
    {
        if ($this->redisConnection && count($this->getKeys(self::VIDEO_CAROUSEL_PREFIX . "*"))) {
            $this->redisConnection->del($this->getKeys(self::VIDEO_CAROUSEL_PREFIX . "*"));
        }
        if ($this->redisConnection && count($this->getKeys(self::VIDEOS_DATA . "*"))) {
            $this->redisConnection->del($this->getKeys(self::VIDEOS_DATA . "*"));
        }
    }
}
