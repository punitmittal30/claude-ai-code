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

use Magento\Framework\Data\Collection;
use Magento\Framework\Data\OptionSourceInterface;
use Pratech\Return\Api\Data\ReasonInterface;
use Pratech\Return\Model\Reason\ResourceModel\CollectionFactory as ReasonCollectionFactory;

class Reason implements OptionSourceInterface
{
    /**
     * @param ReasonCollectionFactory $reasonCollectionFactory
     */
    public function __construct(
        private ReasonCollectionFactory $reasonCollectionFactory
    ) {
    }

    /**
     * Get Order Return Reasons Option Array
     *
     * @return array
     */
    public function toOptionArray(): array
    {
        $result = [];
        $reasons = $this->reasonCollectionFactory->create()
            ->addFieldToSelect(
                [ReasonInterface::REASON_ID, ReasonInterface::TITLE]
            )->setOrder(ReasonInterface::POSITION, Collection::SORT_ORDER_ASC)
            ->addNotDeletedFilter()
            ->addFieldToFilter(ReasonInterface::STATUS, Status::ENABLED)
            ->getData();

        if (!empty($reasons)) {
            $result[] = ['value' => '', 'label' => __('Please choose')];
            foreach ($reasons as $reason) {
                $result[] = [
                    'value' => $reason[ReasonInterface::REASON_ID],
                    'label' => $reason[ReasonInterface::TITLE]
                ];
            }
        }

        return $result;
    }
}
