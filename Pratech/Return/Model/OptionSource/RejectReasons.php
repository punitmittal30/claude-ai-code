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
use Pratech\Return\Api\Data\RejectReasonInterface;
use Pratech\Return\Model\RejectReason\ResourceModel\CollectionFactory as ReasonCollectionFactory;

class RejectReasons implements OptionSourceInterface
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
                [RejectReasonInterface::REASON_ID, RejectReasonInterface::TITLE]
            )->setOrder(RejectReasonInterface::POSITION, Collection::SORT_ORDER_ASC)
            ->addNotDeletedFilter()
            ->addFieldToFilter(RejectReasonInterface::STATUS, Status::ENABLED)
            ->getData();

        if (!empty($reasons)) {
            foreach ($reasons as $reason) {
                $result[] = [
                    'value' => $reason[RejectReasonInterface::REASON_ID],
                    'label' => $reason[RejectReasonInterface::TITLE]
                ];
            }
        }

        return $result;
    }
}
