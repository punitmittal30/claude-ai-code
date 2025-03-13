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

namespace Pratech\Warehouse\Model\Converter;

use Magento\Framework\Reflection\DataObjectProcessor;
use Magento\Framework\Serialize\Serializer\Json;
use Magento\Framework\Webapi\ServiceOutputProcessor;
use Pratech\Warehouse\Api\Data\WarehouseProductResultInterface;

/**
 * Custom serializer for WarehouseProductResult
 */
class WarehouseProductResultSerializer
{
    /**
     * @var ServiceOutputProcessor
     */
    private $serviceOutputProcessor;

    /**
     * @var Json
     */
    private $serializer;

    /**
     * @var DataObjectProcessor
     */
    private $dataObjectProcessor;

    /**
     * @param ServiceOutputProcessor $serviceOutputProcessor
     * @param Json $serializer
     * @param DataObjectProcessor $dataObjectProcessor
     */
    public function __construct(
        ServiceOutputProcessor $serviceOutputProcessor,
        Json $serializer,
        DataObjectProcessor $dataObjectProcessor
    ) {
        $this->serviceOutputProcessor = $serviceOutputProcessor;
        $this->serializer = $serializer;
        $this->dataObjectProcessor = $dataObjectProcessor;
    }

    /**
     * Process warehouse product result for REST API output
     *
     * @param WarehouseProductResultInterface $result
     * @return array
     */
    public function process(WarehouseProductResultInterface $result): array
    {
        // Convert the result to an array first
        $data = $this->dataObjectProcessor->buildOutputDataArray(
            $result,
            WarehouseProductResultInterface::class
        );

        // Fix items that might be serialized JSON strings
        if (isset($data['items']) && is_array($data['items'])) {
            foreach ($data['items'] as $key => $item) {
                if (is_string($item) && $this->isJson($item)) {
                    $data['items'][$key] = $this->serializer->unserialize($item);
                }
            }
        }

        return $data;
    }

    /**
     * Check if a string is valid JSON
     *
     * @param string $string
     * @return bool
     */
    private function isJson(string $string): bool
    {
        $this->serializer->unserialize($string);
        return json_last_error() === JSON_ERROR_NONE;
    }
}
