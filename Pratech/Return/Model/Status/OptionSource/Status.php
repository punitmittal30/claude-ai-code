<?php
/**
 * Pratech_Return
 *
 * PHP version 8.x
 *
 * @category  PHP
 * @package   Pratech\Return
 * @author    Vivek Kumar <vivek.kumar@pratechbrands.com>
 * @copyright 2024 Copyright (c) Pratech Brands Private Limited
 * @link      https://pratechbrands.com/
 **/

namespace Pratech\Return\Model\Status\OptionSource;

use Magento\Framework\Data\Collection;
use Magento\Framework\Option\ArrayInterface;
use Pratech\Return\Api\Data\StatusInterface;
use Pratech\Return\Model\Status\Repository;

class Status implements ArrayInterface
{
    public function __construct(
        private Repository $repository
    ) {
    }

    /**
     * @return array
     */
    public function toOptionArray()
    {
        $optionArray = [];
        foreach ($this->toArray() as $value => $label) {
            $optionArray[] = ['value' => $value, 'label' => $label];
        }
        return $optionArray;
    }

    /**
     * Get options in "key-value" format
     *
     * @return array
     */
    public function toArray()
    {
        $result = [];

        $statusCollection = $this->repository->getEmptyStatusCollection()
            ->addFieldToFilter(StatusInterface::IS_ENABLED, 1)
            ->addNotDeletedFilter()
            ->addFieldToSelect([StatusInterface::STATUS_ID, StatusInterface::TITLE])
            ->setOrder(StatusInterface::PRIORITY, Collection::SORT_ORDER_ASC);

        foreach ($statusCollection->getData() as $status) {
            $result[$status[StatusInterface::STATUS_ID]] = $status[StatusInterface::TITLE];
        }

        return $result;
    }
}
