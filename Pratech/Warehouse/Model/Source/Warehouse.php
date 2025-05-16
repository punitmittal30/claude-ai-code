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

namespace Pratech\Warehouse\Model\Source;

use Magento\Framework\Data\OptionSourceInterface;
use Pratech\Warehouse\Model\ResourceModel\Warehouse\CollectionFactory;

class Warehouse implements OptionSourceInterface
{
    /**
     * @param CollectionFactory $collectionFactory
     */
    public function __construct(
        protected CollectionFactory $collectionFactory
    ) {
    }

    /**
     * To Option Array.
     *
     * @return array
     */
    public function toOptionArray(): array
    {
        $warehouses = $this->collectionFactory->create();
        $options = [];

        foreach ($warehouses as $warehouse) {
            $options[] = [
                'value' => $warehouse->getId(),
                'label' => $warehouse->getName() . ' (' . $warehouse->getWarehouseCode() . ')'
            ];
        }

        return $options;
    }
}
