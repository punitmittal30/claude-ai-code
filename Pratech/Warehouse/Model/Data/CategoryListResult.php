<?php
/**
 * Pratech_Warehouse
 *
 * @category  PHP
 * @package   Pratech\Warehouse
 * @author    Puneet Mittal <puneet.mittal@pratechbrands.com>
 * @copyright 2025 Copyright (c) Pratech Brands Private Limited
 * @link      https://pratechbrands.com/
 */
declare(strict_types=1);

namespace Pratech\Warehouse\Model\Data;

use Magento\Framework\Api\AbstractExtensibleObject;
use Pratech\Warehouse\Api\Data\CategoryListResultInterface;

/**
 * Category list result implementation
 */
class CategoryListResult extends AbstractExtensibleObject implements CategoryListResultInterface
{
    /**
     * @inheritDoc
     */
    public function getIsCached(): bool
    {
        return $this->_get('is_cached');
    }

    /**
     * @inheritDoc
     */
    public function setIsCached(bool $isCached)
    {
        return $this->setData('is_cached', $isCached);
    }

    /**
     * @inheritDoc
     */
    public function getTitle()
    {
        return $this->_get('title');
    }

    /**
     * @inheritDoc
     */
    public function setTitle(string $title)
    {
        return $this->setData('title', $title);
    }

    /**
     * @inheritDoc
     */
    public function getWarehouseCode()
    {
        return $this->_get('warehouse_code');
    }

    /**
     * @inheritDoc
     */
    public function setWarehouseCode(string $warehouseCode)
    {
        return $this->setData('warehouse_code', $warehouseCode);
    }

    /**
     * @inheritDoc
     */
    public function getWarehouseName()
    {
        return $this->_get('warehouse_name');
    }

    /**
     * @inheritDoc
     */
    public function setWarehouseName(string $warehouseName)
    {
        return $this->setData('warehouse_name', $warehouseName);
    }

    /**
     * @inheritDoc
     */
    public function getCategories()
    {
        $categories = $this->_get('categories');

        // Ensure we're returning an array, not a serialized string
        if (is_string($categories) && !empty($categories)) {
            try {
                $decodedCategories = json_decode($categories, true);
                if (json_last_error() === JSON_ERROR_NONE && is_array($decodedCategories)) {
                    return $decodedCategories;
                }
            } catch (\Exception $e) {
                return [];
            }
        }

        return is_array($categories) ? $categories : [];
    }

    /**
     * @inheritDoc
     */
    public function setCategories(array $categories)
    {
        // Make sure we're storing the raw array, not a JSON string
        return $this->setData('categories', $categories);
    }

    /**
     * @inheritDoc
     */
    public function getTotalCount()
    {
        return (int)$this->_get('total_count');
    }

    /**
     * @inheritDoc
     */
    public function setTotalCount(int $totalCount)
    {
        return $this->setData('total_count', $totalCount);
    }
}
