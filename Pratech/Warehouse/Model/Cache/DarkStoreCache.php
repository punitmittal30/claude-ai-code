<?php
/**
 * Pratech_Warehouse
 *
 * PHP version 8.x
 *
 * @category  PHP
 * @package   Pratech\Warehouse
 * @author    Puneet Mittal <puneet.mittal@pratechbrands.com>
 * @copyright 2025 Copyright (c) Pratech Brands Private Limited
 * @link      https://pratechbrands.com/
 **/

namespace Pratech\Warehouse\Model\Cache;

use Exception;
use Magento\Framework\App\CacheInterface;
use Magento\Framework\Event\ManagerInterface as EventManager;
use Magento\Framework\Serialize\SerializerInterface;
use Psr\Log\LoggerInterface;

/**
 * Centralized Dark Store Cache Service
 */
class DarkStoreCache
{
    /**
     * Cache key for available dark stores
     */
    public const CACHE_KEY_AVAILABLE_DARK_STORES = 'available_dark_stores';

    /**
     * Cache tag for dark stores
     */
    public const CACHE_TAG_DARK_STORES = 'dark_stores';

    /**
     * Cache lifetime for dark stores (1 week)
     */
    public const CACHE_LIFETIME = 604800;

    /**
     * Constructor
     *
     * @param CacheInterface $cache
     * @param EventManager $eventManager
     * @param SerializerInterface $serializer
     * @param LoggerInterface $logger
     */
    public function __construct(
        private CacheInterface      $cache,
        private EventManager        $eventManager,
        private SerializerInterface $serializer,
        private LoggerInterface     $logger
    ) {
    }

    /**
     * Clear dark store cache
     *
     * @return void
     */
    public function clearDarkStoreCache(): void
    {
        try {
            $this->cache->remove(self::CACHE_KEY_AVAILABLE_DARK_STORES);
            $this->cache->clean([self::CACHE_TAG_DARK_STORES]);

            // Dispatch event to allow other modules to clear their caches
            $this->eventManager->dispatch('pratech_warehouse_dark_store_cache_clear_after');

            $this->logger->debug('Dark store cache cleared successfully');
        } catch (Exception $e) {
            $this->logger->error('Error clearing dark store cache: ' . $e->getMessage());
        }
    }

    /**
     * Get available dark stores from cache or source
     *
     * @param callable $callback Function to fetch dark stores if not in cache
     * @return array
     */
    public function getAvailableDarkStores(callable $callback): array
    {
        $cachedData = $this->cache->load(self::CACHE_KEY_AVAILABLE_DARK_STORES);

        if ($cachedData) {
            return $this->serializer->unserialize($cachedData);
        }

        // Get fresh data using the callback
        $darkStores = $callback();

        if (!empty($darkStores)) {
            $this->cache->save(
                $this->serializer->serialize($darkStores),
                self::CACHE_KEY_AVAILABLE_DARK_STORES,
                [self::CACHE_TAG_DARK_STORES],
                self::CACHE_LIFETIME
            );
        }

        return $darkStores;
    }
}
