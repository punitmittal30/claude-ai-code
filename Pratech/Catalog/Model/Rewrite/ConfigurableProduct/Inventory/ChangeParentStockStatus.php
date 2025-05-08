<?php
/**
 * Pratech_Catalog
 *
 * PHP version 8.x
 *
 * @category  PHP
 * @package   Pratech\Catalog
 * @author    Akash Panwar <akash.panwar@pratechbrands.com>
 * @copyright 2024 Copyright (c) Pratech Brands Private Limited
 * @link      https://pratechbrands.com/
 **/
declare(strict_types=1);

namespace Pratech\Catalog\Model\Rewrite\ConfigurableProduct\Inventory;

use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\CatalogInventory\Api\Data\StockItemInterface;
use Magento\CatalogInventory\Api\StockConfigurationInterface;
use Magento\CatalogInventory\Api\StockItemCriteriaInterfaceFactory;
use Magento\CatalogInventory\Api\StockItemRepositoryInterface;
use Magento\ConfigurableProduct\Model\Product\Type\Configurable;

/***
 * Update stock status of configurable products based on children products stock status
 */
class ChangeParentStockStatus extends \Magento\ConfigurableProduct\Model\Inventory\ChangeParentStockStatus
{
    /**
     * @param Configurable $configurableType
     * @param StockItemCriteriaInterfaceFactory $criteriaInterfaceFactory
     * @param StockItemRepositoryInterface $stockItemRepository
     * @param StockConfigurationInterface $stockConfiguration
     * @param ProductRepositoryInterface $productRepository
     */
    public function __construct(
        private Configurable $configurableType,
        private StockItemCriteriaInterfaceFactory $criteriaInterfaceFactory,
        private StockItemRepositoryInterface $stockItemRepository,
        private StockConfigurationInterface $stockConfiguration,
        private ProductRepositoryInterface $productRepository
    ) {
        parent::__construct(
            $configurableType,
            $criteriaInterfaceFactory,
            $stockItemRepository,
            $stockConfiguration
        );
    }

    /**
     * Update stock status of configurable products based on children products stock status
     *
     * @param array $childrenIds
     * @return void
     */
    public function execute(array $childrenIds): void
    {
        $parentIds = $this->configurableType->getParentIdsByChild($childrenIds);
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
        bool $childrenIsInStock
    ): bool {
        return $parentStockItem->getIsInStock() !== $childrenIsInStock &&
            ($childrenIsInStock === false || $parentStockItem->getStockStatusChangedAuto());
    }
}
