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

namespace Hyuga\Catalog\Model\Resolver;

use Exception;
use Magento\Catalog\Api\Data\ProductInterface;
use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Catalog\Model\Product\Attribute\Source\Status as ProductStatus;
use Magento\Catalog\Pricing\Price\FinalPrice;
use Magento\CatalogInventory\Api\StockRegistryInterface;
use Magento\ConfigurableProduct\Block\Product\View\Type\Configurable as ConfigurableOptionsProvider;
use Magento\Framework\App\CacheInterface;
use Magento\Framework\App\ResourceConnection;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\GraphQl\Config\Element\Field;
use Magento\Framework\GraphQl\Query\ResolverInterface;
use Magento\Framework\GraphQl\Schema\Type\ResolveInfo;

/**
 * Set Default Variant Id for Configurable Product
 */
class DefaultVariantId implements ResolverInterface
{
    /**
     * Cache key prefix for default variant ids
     */
    private const CACHE_KEY_PREFIX = 'default_variant_id_';

    /**
     * Default Variant Constructor
     *
     * @param ProductRepositoryInterface $productRepository
     * @param ConfigurableOptionsProvider $configurableOptionsProvider
     * @param StockRegistryInterface $stockRegistry
     * @param CacheInterface $cache
     * @param ResourceConnection $resourceConnection
     */
    public function __construct(
        private ProductRepositoryInterface  $productRepository,
        private ConfigurableOptionsProvider $configurableOptionsProvider,
        private StockRegistryInterface      $stockRegistry,
        private CacheInterface              $cache,
        private ResourceConnection          $resourceConnection
    ) {
    }

    /**
     * @inheritDoc
     */
    public function resolve(
        Field       $field,
        $context,
        ResolveInfo $info,
        array       $value = null,
        array       $args = null
    ) {
        if (!array_key_exists('model', $value)
            || !$value['model'] instanceof ProductInterface
        ) {
            throw new LocalizedException(__('"model" value should be specified'));
        }

        $product = $value['model'];

        if ($product->getTypeId() != 'configurable') {
            return null;
        }

        return $this->getDefaultVariantId($product->getId());
    }

    /**
     * Get Default Variant Id for Configurable Products
     *
     * @param int $productId
     * @return int|null
     * @throws NoSuchEntityException
     */
    private function getDefaultVariantId(int $productId): ?int
    {
        $cacheKey = self::CACHE_KEY_PREFIX . $productId;
        $cachedValue = $this->cache->load($cacheKey);

        if ($cachedValue !== false) {
            return (int)$cachedValue;
        }

        try {
            // Get variant IDs directly from the database for better performance
            $variantIds = $this->getConfigurableVariantIds($productId);

            if (empty($variantIds)) {
                return null;
            }

            // Get enabled variants with stock information
            $enabledVariantIds = $this->getEnabledVariantsWithStock($variantIds);

            if (empty($enabledVariantIds)) {
                $variantId = $variantIds[0];
            } else {
                $variantId = $this->getLowestPriceVariant($enabledVariantIds);
            }

            $this->cache->save(
                (string)$variantId,
                $cacheKey,
                ['configurable_products', 'default_variants'],
                3600
            );

            return $variantId;
        } catch (Exception $e) {
            return $this->getDefaultVariantIdFallback($productId);
        }
    }

    /**
     * Get configurable product variant IDs directly from database
     *
     * @param int $productId
     * @return array
     */
    private function getConfigurableVariantIds(int $productId): array
    {
        $connection = $this->resourceConnection->getConnection();
        $select = $connection->select()
            ->from(
                ['link' => $this->resourceConnection->getTableName('catalog_product_super_link')],
                ['product_id']
            )
            ->where('link.parent_id = ?', $productId);

        return $connection->fetchCol($select);
    }

    /**
     * Filter variants by status and stock availability
     *
     * @param array $variantIds
     * @return array
     */
    private function getEnabledVariantsWithStock(array $variantIds): array
    {
        if (empty($variantIds)) {
            return [];
        }

        $connection = $this->resourceConnection->getConnection();

        // Get enabled products
        $select = $connection->select()
            ->from(
                ['e' => $this->resourceConnection->getTableName('catalog_product_entity')],
                ['entity_id']
            )
            ->join(
                ['status' => $this->resourceConnection->getTableName('catalog_product_entity_int')],
                'e.entity_id = status.entity_id',
                []
            )
            ->join(
                ['ea' => $this->resourceConnection->getTableName('eav_attribute')],
                'status.attribute_id = ea.attribute_id AND ea.attribute_code = "status"',
                []
            )
            ->where('e.entity_id IN (?)', $variantIds)
            ->where('status.value = ?', ProductStatus::STATUS_ENABLED);

        $enabledIds = $connection->fetchCol($select);

        if (empty($enabledIds)) {
            return [];
        }

        // Get stock information
        $select = $connection->select()
            ->from(
                ['stock' => $this->resourceConnection->getTableName('cataloginventory_stock_item')],
                ['product_id']
            )
            ->where('stock.product_id IN (?)', $enabledIds)
            ->where('stock.is_in_stock = ?', 1);

        return $connection->fetchCol($select);
    }

    /**
     * Get variant with the lowest price from given variant IDs
     *
     * @param array $variantIds
     * @return int|null
     */
    private function getLowestPriceVariant(array $variantIds): ?int
    {
        if (empty($variantIds)) {
            return null;
        }

        $connection = $this->resourceConnection->getConnection();

        // Get the lowest price variant
        $select = $connection->select()
            ->from(
                ['price' => $this->resourceConnection->getTableName('catalog_product_entity_decimal')],
                ['entity_id', 'value']
            )
            ->join(
                ['ea' => $this->resourceConnection->getTableName('eav_attribute')],
                'price.attribute_id = ea.attribute_id AND ea.attribute_code = "price"',
                []
            )
            ->where('price.entity_id IN (?)', $variantIds)
            ->order('price.value ASC')
            ->limit(1);

        $result = $connection->fetchRow($select);

        return $result ? (int)$result['entity_id'] : null;
    }

    /**
     * Fallback method to get default variant ID using original approach
     *
     * @param int $productId
     * @return int|null
     * @throws NoSuchEntityException
     */
    private function getDefaultVariantIdFallback(int $productId): ?int
    {
        $minimumAmount = null;
        $variantId = null;

        $product = $this->productRepository->getById($productId);

        $productVariants = $this->configurableOptionsProvider->getProducts($product);

        foreach ($productVariants as $variant) {
            $variantStock = $this->stockRegistry->getStockItem($variant->getId());
            if ($variant->getStatus() != ProductStatus::STATUS_ENABLED || !$variantStock->getIsInStock()) {
                continue;
            }

            $variantAmount = $variant->getPriceInfo()
                ->getPrice(FinalPrice::PRICE_CODE)
                ->getAmount();

            if (!$minimumAmount
                || ($variantAmount->getValue() < $minimumAmount->getValue())
            ) {
                $minimumAmount = $variantAmount;
                $variantId = $variant->getId();
            }
        }

        if (!$variantId && !empty($productVariants)) {
            $variantId = isset($productVariants[0]) ? $productVariants[0]->getId() : null;
        }

        return $variantId;
    }
}
