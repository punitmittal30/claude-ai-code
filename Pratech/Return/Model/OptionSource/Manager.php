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

namespace Pratech\Return\Model\OptionSource;

use Magento\Framework\Option\ArrayInterface;
use Magento\User\Model\ResourceModel\User\CollectionFactory;

class Manager implements ArrayInterface
{
    /**
     * @param CollectionFactory $managerCollectionFactory
     */
    public function __construct(
        private CollectionFactory $managerCollectionFactory
    ) {
    }

    /**
     * @return array
     */
    public function toOptionArray(): array
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
    public function toArray(): array
    {
        $result = [0 => __('Unassigned')];
        $managerCollection = $this->managerCollectionFactory->create();
        $managerCollection->addFieldToFilter('main_table.is_active', 1)
            ->addFieldToSelect(['user_id', 'username']);

        foreach ($managerCollection->getData() as $manager) {
            $result[$manager['user_id']] = $manager['username'];
        }

        return $result;
    }
}
