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

namespace Pratech\Warehouse\Model\Converter;

use Exception;
use Magento\Framework\Reflection\DataObjectProcessor;
use Magento\Framework\Serialize\Serializer\Json;
use Pratech\Warehouse\Api\Data\WarehouseProductResultInterface;

/**
 * Custom serializer for WarehouseProductResult
 */
class WarehouseProductResultSerializer
{
    /**
     * @param Json $serializer
     * @param DataObjectProcessor $dataObjectProcessor
     */
    public function __construct(
        private Json                $serializer,
        private DataObjectProcessor $dataObjectProcessor
    ) {
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

        // Fix available_filters that might be serialized JSON strings
        if (isset($data['available_filters']) && is_array($data['available_filters'])) {
            $fixedFilters = [];
            foreach ($data['available_filters'] as $key => $filter) {
                if (is_string($filter) && $this->isJson($filter)) {
                    $decodedFilter = $this->serializer->unserialize($filter);

                    // Handle different filter types - some might not have a code
                    if (isset($decodedFilter['code'])) {
                        // For attribute filters that have a code
                        $fixedFilters[$decodedFilter['code']] = $decodedFilter;
                    } elseif (isset($key) && is_string($key)) {
                        // For price ranges and other filters that might be indexed differently
                        $fixedFilters[$key] = $decodedFilter;
                    } else {
                        $fixedFilters[] = $decodedFilter;
                    }
                } else {
                    // It's already an object or array, keep it as is
                    if (is_numeric($key) || !is_string($key)) {
                        $fixedFilters[] = $filter;
                    } else {
                        $fixedFilters[$key] = $filter;
                    }
                }
            }

            $data['available_filters'] = array_values($fixedFilters);
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
        try {
            $result = $this->serializer->unserialize($string);
            return json_last_error() === JSON_ERROR_NONE && is_array($result);
        } catch (Exception $e) {
            return false;
        }
    }
}
