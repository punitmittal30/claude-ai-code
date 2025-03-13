<?php
/**
 * Pratech_Warehouse
 *
 * PHP version 8.x
 *
 * @category  PHP
 * @package   Pratech\Warehouse
 * @author    Your Name <your.email@pratechbrands.com>
 * @copyright 2025 Copyright (c) Pratech Brands Private Limited
 * @link      https://pratechbrands.com/
 **/

namespace Pratech\Warehouse\Plugin;

use Magento\Framework\Webapi\ServiceOutputProcessor;
use Pratech\Warehouse\Api\Data\WarehouseProductResultInterface;
use Pratech\Warehouse\Model\Converter\WarehouseProductResultSerializer;

/**
 * Plugin for handling WarehouseProductResult in the REST API
 */
class WarehouseProductResultPlugin
{
    /**
     * @var WarehouseProductResultSerializer
     */
    private $resultSerializer;

    /**
     * @param WarehouseProductResultSerializer $resultSerializer
     */
    public function __construct(
        WarehouseProductResultSerializer $resultSerializer
    ) {
        $this->resultSerializer = $resultSerializer;
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

        // For all other objects, use the default processor
        return $proceed($data, $serviceClassName, $serviceMethodName);
    }
}
