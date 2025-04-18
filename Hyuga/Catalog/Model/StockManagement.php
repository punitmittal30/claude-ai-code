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

namespace Hyuga\Catalog\Model;

use DateTime;
use Exception;
use Hyuga\Catalog\Api\StockManagementInterface;
use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Catalog\Model\Product\Action;
use Magento\Catalog\Model\Product\Attribute\Source\Status;
use Magento\Catalog\Model\ProductFactory;
use Magento\CatalogInventory\Api\Data\StockItemInterface;
use Magento\CatalogInventory\Api\StockRegistryInterface;
use Magento\Framework\App\ResourceConnection;
use Magento\Framework\DataObject;
use Magento\Framework\Event\ManagerInterface;
use Magento\Framework\Exception\NoSuchEntityException;
use Pratech\Base\Logger\RestApiLogger;

class StockManagement implements StockManagementInterface
{
    /**
     * @param RestApiLogger $restApiLogger
     * @param ManagerInterface $eventManager
     * @param Action $productAction
     * @param StockRegistryInterface $stockItemRepository
     * @param ProductRepositoryInterface $productRepository
     * @param ResourceConnection $resourceConnection
     */
    public function __construct(
        private RestApiLogger              $restApiLogger,
        private ManagerInterface           $eventManager,
        private Action                     $productAction,
        private StockRegistryInterface     $stockItemRepository,
        private ProductRepositoryInterface $productRepository,
        private ResourceConnection         $resourceConnection,
    ) {
    }

    /**
     * Update Stock Item By Product Sku
     *
     * @param string $productSku
     * @param StockItemInterface $stockItem
     * @param mixed $product
     * @return int
     * @throws NoSuchEntityException
     */
    public function updateStockItemBySku(string $productSku, StockItemInterface $stockItem, mixed $product = []): int
    {
        $this->restApiLogger->info(
            "Inventory Update | " . $productSku,
            [
                "stock" => $stockItem->getData(),
                "product" => $product
            ]
        );

        try {
            $fullProduct = $this->productRepository->get($productSku);
            $productId = $fullProduct->getId();

            if ($fullProduct->getStatus() == Status::STATUS_DISABLED) {
                $this->restApiLogger->info("Skipping update for disabled product | SKU: " . $productSku);
                return 0;
            }

            $connection = $this->resourceConnection->getConnection();
            $stockTable = $this->resourceConnection->getTableName('cataloginventory_stock_item');

            $select = $connection->select()
                ->from($stockTable, ['is_in_stock', 'qty'])
                ->where('product_id = ?', $productId);

            $currentStockData = $connection->fetchRow($select);
            $currentIsInStock = (bool)($currentStockData['is_in_stock'] ?? false);

            if (!$stockItem->getIsInStock() && !$currentIsInStock) {
                $this->restApiLogger->info("Skipping update for already OOS product | SKU: " . $productSku);
                return 0;
            }

            // Detect stock status change
            $stockStatusChanged = false;
            if ($currentIsInStock != $stockItem->getIsInStock()) {
                $this->restApiLogger->info("Stock status changed for SKU: " . $productSku .
                    " | From: " . ($currentIsInStock ? 'In Stock' : 'Out of Stock') .
                    " | To: " . ($stockItem->getIsInStock() ? 'In Stock' : 'Out of Stock'));
                $stockStatusChanged = true;
            }

            $stockItemId = $this->stockItemRepository->updateStockItemBySku($productSku, $stockItem);

            $attributesChanged = [];

            if (isset($product) && (!empty($product['expiry_date']) || !empty($product['mrp']))) {
                $attributesToUpdate = [];

                if (!empty($product['expiry_date'])) {
                    $oldExpiryDate = $fullProduct->getData('expiry_date');
                    $attributesToUpdate['expiry_date'] = $product['expiry_date'];

                    $normalizedOldDate = $oldExpiryDate ? $this->normalizeDate($oldExpiryDate) : null;
                    $normalizedNewDate = $this->normalizeDate($product['expiry_date']);

                    if ($normalizedOldDate !== $normalizedNewDate) {
                        $attributesChanged['expiry_date'] = [
                            'old' => $oldExpiryDate,
                            'new' => $product['expiry_date']
                        ];
                    }
                }

                if (!empty($product['mrp'])) {
                    $oldPrice = $fullProduct->getPrice();
                    $attributesToUpdate['price'] = $product['mrp'];

                    if ($oldPrice != $product['mrp']) {
                        $attributesChanged['price'] = [
                            'old' => $oldPrice,
                            'new' => $product['mrp']
                        ];
                    }
                }

                if (!empty($attributesToUpdate)) {
                    $this->productAction->updateAttributes(
                        [$productId],
                        $attributesToUpdate,
                        0
                    );

                    $productData = $this->getMinimalProductData($productId, $productSku);

                    $this->eventManager->dispatch('update_stock_by_vinculum', [
                        'product' => $productData,
                        'attribute_changes' => $attributesChanged,
                        'stock_status_changed' => $stockStatusChanged
                    ]);
                } elseif ($stockStatusChanged) {
                    $productData = $this->getMinimalProductData($productId, $productSku);
                    $this->eventManager->dispatch('update_stock_by_vinculum', [
                        'product' => $productData,
                        'attribute_changes' => [],
                        'stock_status_changed' => $stockStatusChanged
                    ]);
                }
            } elseif ($stockStatusChanged) {
                $productData = $this->getMinimalProductData($productId, $productSku);
                $this->eventManager->dispatch('update_stock_by_vinculum', [
                    'product' => $productData,
                    'attribute_changes' => [],
                    'stock_status_changed' => $stockStatusChanged
                ]);
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
     * Normalize date format for comparison
     *
     * @param string $date
     * @return string|null
     */
    private function normalizeDate(string $date): ?string
    {
        try {
            if (empty($date)) {
                return null;
            }
            $date = str_replace('/', '-', $date);
            $dateTime = new DateTime($date);
            return $dateTime->format('Y-m-d');
        } catch (Exception $e) {
            $this->restApiLogger->error("Error normalizing date: " . $date . " | " . $e->getMessage());
            return $date;
        }
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
