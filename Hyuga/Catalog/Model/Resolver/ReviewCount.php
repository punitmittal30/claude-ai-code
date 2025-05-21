<?php
/**
 * Hyuga_Catalog
 *
 * PHP version 8.x
 *
 * @category  PHP
 * @package   Hyuga\Catalog
 * @author    Puneet Mittal <puneet.mittal@pratechbrands.com>
 * @copyright 2025 Copyright (c) Pratech Brands Private Limited
 * @link      https://pratechbrands.com/
 **/
declare(strict_types=1);

namespace Hyuga\Catalog\Model\Resolver;

use Magento\Catalog\Api\Data\ProductInterface;
use Magento\Framework\App\CacheInterface;
use Magento\Framework\App\ResourceConnection;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\GraphQl\Config\Element\Field;
use Magento\Framework\GraphQl\Query\ResolverInterface;
use Magento\Framework\GraphQl\Schema\Type\ResolveInfo;
use Magento\Review\Model\Review;
use Magento\Store\Model\StoreManagerInterface;

/**
 * Resolver for ReviewCount
 */
class ReviewCount implements ResolverInterface
{
    /**
     * Cache key prefix for review counts
     */
    private const CACHE_KEY_PREFIX = 'product_review_count_';

    /**
     * Cache lifetime in seconds (1 week)
     */
    private const CACHE_LIFETIME = 604800;

    /**
     * In-memory cache for review counts by product ID
     *
     * @var array
     */
    private array $reviewCountCache = [];

    /**
     * @param ResourceConnection $resourceConnection
     * @param StoreManagerInterface $storeManager
     * @param CacheInterface $cache
     */
    public function __construct(
        private ResourceConnection    $resourceConnection,
        private StoreManagerInterface $storeManager,
        private CacheInterface        $cache
    ) {
    }

    /**
     * @inheritdoc
     */
    public function resolve(
        Field       $field,
        $context,
        ResolveInfo $info,
        array       $value = null,
        array       $args = null
    ) {
        if (!isset($value['model'])) {
            throw new LocalizedException(__('"model" value should be specified'));
        }

        /** @var ProductInterface $product */
        $product = $value['model'];
        $productId = (int)$product->getId();

        // Check in-memory cache first (for this request)
        if (isset($this->reviewCountCache[$productId])) {
            return $this->reviewCountCache[$productId];
        }

        // Build cache key with store and product ID
        $storeId = (int)$this->storeManager->getStore()->getId();
        $cacheKey = self::CACHE_KEY_PREFIX . $storeId . '_' . $productId;

        // Check persistent cache
        $cachedCount = $this->cache->load($cacheKey);
        if ($cachedCount) {
            $reviewCount = (int)$cachedCount;
            $this->reviewCountCache[$productId] = $reviewCount;
            return $reviewCount;
        }

        // Get review count from database
        $reviewCount = $this->getReviewCount($productId, $storeId);

        // Store in both caches
        $this->reviewCountCache[$productId] = $reviewCount;
        $this->cache->save(
            (string)$reviewCount,
            $cacheKey,
            ['review_counts', 'product_' . $productId],
            self::CACHE_LIFETIME
        );

        return $reviewCount;
    }

    /**
     * Get review count directly from database
     *
     * @param int $productId
     * @param int $storeId
     * @return int
     */
    private function getReviewCount(int $productId, int $storeId): int
    {
        $connection = $this->resourceConnection->getConnection();
        $reviewTable = $this->resourceConnection->getTableName('review');
        $reviewStoreTable = $this->resourceConnection->getTableName('review_store');

        $select = $connection->select()
            ->from(['r' => $reviewTable], ['COUNT(r.review_id)'])
            ->join(
                ['rs' => $reviewStoreTable],
                'r.review_id = rs.review_id',
                []
            )
            ->where('r.entity_pk_value = ?', $productId)
            ->where('r.status_id = ?', Review::STATUS_APPROVED)
            ->where('rs.store_id IN (?)', [0, $storeId]);

        return (int)$connection->fetchOne($select);
    }
}
