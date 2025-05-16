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
use Pratech\Order\Model\ResourceModel\ShipmentStatus\CollectionFactory;

class RefundStatus implements ArrayInterface
{

    /**
     * @param CollectionFactory $statusCollectionFactory
     */
    public function __construct(
        private CollectionFactory $statusCollectionFactory
    ) {
    }

    /**
     * @return array
     */
    public function toOptionArray(): array
    {
        $result = [];
        $status = $this->statusCollectionFactory->create()
            ->addFieldToSelect(
                ['status_id', 'status']
            )
            ->addFieldToFilter('journey', ['in' => ['Refund']])
            ->getData();

        if (!empty($status)) {
            foreach ($status as $refundStatus) {
                $result[] = [
                    'value' => $refundStatus['status_id'],
                    'label' => $refundStatus['status']
                ];
            }
        }

        return $result;
    }
}
