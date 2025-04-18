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

namespace Pratech\Warehouse\Plugin;

use Magento\Framework\Webapi\ServiceOutputProcessor;
use Pratech\Warehouse\Api\Data\WarehouseProductResultInterface;
use Pratech\Warehouse\Model\Converter\WarehouseProductResultSerializer;
use Pratech\Warehouse\Api\Data\CategoryListResultInterface;

/**
 * Plugin for handling WarehouseProductResult in the REST API
 */
class WarehouseProductResultPlugin
{
    /**
     * @param WarehouseProductResultSerializer $resultSerializer
     */
    public function __construct(
        private WarehouseProductResultSerializer $resultSerializer
    ) {
    }

    /**
     * Process the output data before it's converted to REST API format
     *
     * This plugin only applies to WarehouseProductResult objects
     *
     * @param ServiceOutputProcessor $subject
     * @param callable $proceed
     * @param object $data
     * @param string $serviceClassName
     * @param string $serviceMethodName
     * @return array
     */
    public function aroundProcess(
        ServiceOutputProcessor $subject,
        callable               $proceed,
        $data,
        $serviceClassName,
        $serviceMethodName
    ) {
        // Only apply to WarehouseProductResult objects
        if ($data instanceof WarehouseProductResultInterface) {
            return $this->resultSerializer->process($data);
        }

        // Only apply to CategoryListResult objects
        if ($data instanceof CategoryListResultInterface) {
            return [
                'title' => $data->getTitle(),
                'warehouse_code' => $data->getWarehouseCode(),
                'warehouse_name' => $data->getWarehouseName(),
                'categories' => $data->getCategories(),
                'total_count' => $data->getTotalCount()
            ];
        }

        // For all other objects, use the default processor
        return $proceed($data, $serviceClassName, $serviceMethodName);
    }
}
