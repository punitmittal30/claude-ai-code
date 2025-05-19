<?php
/**
 * Pratech_Catalog
 *
 * PHP version 8.x
 *
 * @category  PHP
 * @package   Pratech\Catalog
 * @author    Puneet Mittal <puneet.mittal@pratechbrands.com>
 * @copyright 2023 Copyright (c) Pratech Brands Private Limited
 * @link      https://pratechbrands.com/
 **/

namespace Pratech\Catalog\Helper;

use Magento\Catalog\Api\Data\ProductInterface;
use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\CatalogInventory\Api\Data\StockItemInterface;
use Magento\CatalogInventory\Api\StockConfigurationInterface;
use Magento\CatalogInventory\Api\StockItemCriteriaInterfaceFactory;
use Magento\CatalogInventory\Api\StockItemRepositoryInterface;
use Magento\ConfigurableProduct\Model\Product\Type\Configurable;

class ConfigurableProduct
{
    /**
     * Configurable Product Helper Constructor
     *
     * @param Configurable $configurableType
     * @param StockItemCriteriaInterfaceFactory $criteriaInterfaceFactory
     * @param StockItemRepositoryInterface $stockItemRepository
     * @param StockConfigurationInterface $stockConfiguration
     * @param ProductRepositoryInterface $productRepository
     */
    public function __construct(
        private Configurable                      $configurableType,
        private StockItemCriteriaInterfaceFactory $criteriaInterfaceFactory,
        private StockItemRepositoryInterface      $stockItemRepository,
        private StockConfigurationInterface       $stockConfiguration,
        private ProductRepositoryInterface        $productRepository
    ) {
    }

    /**
     * Update Parent Stock
     *
     * @param ProductInterface $product
     * @return void
     */
    public function updateParentStock(ProductInterface $product): void
    {
        $parentIds = $this->configurableType->getParentIdsByChild([$product->getId()]);
        foreach (array_unique($parentIds) as $productId) {
            $this->processStockForParent((int)$productId);
        }
    }

    /**
     * Update stock status of configurable product based on children products stock status
     *
     * @param int $productId
     * @return void
     */
    private function processStockForParent(int $productId): void
    {
        $criteria = $this->criteriaInterfaceFactory->create();
        $criteria->setScopeFilter($this->stockConfiguration->getDefaultScopeId());

        $criteria->setProductsFilter($productId);
        $stockItemCollection = $this->stockItemRepository->getList($criteria);
        $allItems = $stockItemCollection->getItems();
        if (empty($allItems)) {
            return;
        }
        $parentStockItem = array_shift($allItems);

        $childrenIds = $this->configurableType->getChildrenIds($productId);
        $criteria->setProductsFilter($childrenIds);
        $stockItemCollection = $this->stockItemRepository->getList($criteria);
        $allItems = $stockItemCollection->getItems();

        $childrenIsInStock = false;

        foreach ($allItems as $childItem) {
            $childProduct = $this->productRepository->getById($childItem->getProductId());
            if ($childItem->getIsInStock() === true && $childProduct->getStatus() == 1) {
                $childrenIsInStock = true;
                break;
            }
        }

        if ($this->isNeedToUpdateParent($parentStockItem, $childrenIsInStock)) {
            $parentStockItem->setIsInStock($childrenIsInStock);
            $parentStockItem->setStockStatusChangedAuto(1);
            $parentStockItem->setStockStatusChangedAutomaticallyFlag(true);
            $this->stockItemRepository->save($parentStockItem);
        }
    }

    /**
     * Check if parent item should be updated
     *
     * @param StockItemInterface $parentStockItem
     * @param bool $childrenIsInStock
     * @return bool
     */
    private function isNeedToUpdateParent(
        StockItemInterface $parentStockItem,
        bool               $childrenIsInStock
    ): bool {
        return $parentStockItem->getIsInStock() !== $childrenIsInStock;
    }
}
