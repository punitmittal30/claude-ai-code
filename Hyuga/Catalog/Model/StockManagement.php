<?php

namespace Hyuga\Catalog\Model;

use Exception;
use Hyuga\Catalog\Api\StockManagementInterface;
use Magento\Catalog\Model\Product\Action;
use Magento\Catalog\Model\ProductFactory;
use Magento\CatalogInventory\Api\Data\StockItemInterface;
use Magento\CatalogInventory\Api\StockRegistryInterface;
use Magento\Framework\App\CacheInterface;
use Magento\Framework\DataObject;
use Magento\Framework\Event\ManagerInterface;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Serialize\SerializerInterface;
use Pratech\Base\Logger\RestApiLogger;

class StockManagement implements StockManagementInterface
{
    public function __construct(
        private RestApiLogger          $restApiLogger,
        private ManagerInterface       $eventManager,
        private Action                 $productAction,
        private StockRegistryInterface $stockItemRepository,
        private CacheInterface         $cache,
        private ProductFactory         $productFactory,
        private SerializerInterface    $serializer
    )
    {
    }

    /**
     * @inheritDoc
     */
    public function updateStockItemBySku(string $productSku, StockItemInterface $stockItem, mixed $product = []): int
    {
        $this->restApiLogger->info(
            "Inventory Update | " . $productSku . " | ",
            [
                "stock" => $stockItem->getData(),
                "product" => $product
            ]
        );

        try {
            $productId = $this->getProductIdBySkuEfficient($productSku);

            if (!$productId) {
                throw new NoSuchEntityException(__("Product with SKU '%1' does not exist", $productSku));
            }

            $currentStockItem = $this->stockItemRepository->getStockItem($productId);

            if (!$stockItem->getIsInStock() && !$currentStockItem->getIsInStock()) {
                $this->restApiLogger->info("Skipping update for already OOS product | SKU: " . $productSku);
                return 0;
            }

            $stockItemId = $this->stockItemRepository->updateStockItemBySku($productSku, $stockItem);

            if (isset($product) && (!empty($product['expiry_date']) || !empty($product['mrp']))) {
                $attributesToUpdate = [];

                if (!empty($product['expiry_date'])) {
                    $attributesToUpdate['expiry_date'] = $product['expiry_date'];
                }

                if (!empty($product['mrp'])) {
                    $attributesToUpdate['price'] = $product['mrp'];
                }

                if (!empty($attributesToUpdate)) {
                    $this->productAction->updateAttributes(
                        [$productId],
                        $attributesToUpdate,
                        0
                    );

                    $productData = $this->getMinimalProductData($productId, $productSku);
                    $this->eventManager->dispatch('update_stock_by_vinculum', ['product' => $productData]);
                }
            }

            return $stockItemId;
        } catch (Exception $exception) {
            $this->restApiLogger->error(
                "Error updating stock for SKU: " . $productSku . " | " . $exception->getMessage()
            );
            throw $exception;
        }
    }

    /**
     * Get product ID by SKU efficiently using a consolidated cache
     *
     * @param string $sku
     * @return int|null
     */
    private function getProductIdBySkuEfficient(string $sku): ?int
    {
        $cacheKey = 'product_sku_id_map';

        $skuIdMap = $this->cache->load($cacheKey);
        $skuIdMap = $skuIdMap ? $this->serializer->unserialize($skuIdMap) : [];

        // Check if this SKU exists in the cached map
        if (isset($skuIdMap[$sku])) {
            return (int)$skuIdMap[$sku];
        }

        try {
            $productId = $this->productFactory->create()->getIdBySku($sku);

            if ($productId) {
                $skuIdMap[$sku] = (int)$productId;

                $this->cache->save(
                    $this->serializer->serialize($skuIdMap),
                    $cacheKey,
                    ['product_id_cache'],
                    604800
                );

                return (int)$productId;
            }
        } catch (Exception $e) {
            $this->restApiLogger->error("Error getting product ID for SKU: " . $sku . " | " . $e->getMessage());
        }
        return null;
    }

    /**
     * Get minimal product data needed for event
     *
     * @param int $productId
     * @param string $sku
     * @return DataObject
     */
    private function getMinimalProductData(int $productId, string $sku): DataObject
    {
        return new DataObject([
            'id' => $productId,
            'entity_id' => $productId,
            'sku' => $sku
        ]);
    }
}
