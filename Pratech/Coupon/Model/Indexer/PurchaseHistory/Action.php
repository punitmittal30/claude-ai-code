<?php
/**
 * Pratech_Coupon
 *
 * @category  XML
 * @package   Pratech\Coupon
 * @author    Vivek Kumar <vivek.kumar@pratechbrands.com>
 * @copyright 2022 Copyright (c) Pratech Brands Private Limited
 * @link      https://pratechbrands.com/
 */

namespace Pratech\Coupon\Model\Indexer\PurchaseHistory;

use Pratech\Coupon\Model\ResourceModel\Indexer\Order;
use Psr\Log\LoggerInterface;

class Action
{
    /**
     * Indexer Action Constructor
     *
     * @param Order           $orderResource
     * @param LoggerInterface $logger
     */
    public function __construct(
        private Order $orderResource,
        private LoggerInterface $logger
    ) {
    }

    /**
     * Convert Order Ids To Customer Ids
     *
     * @param  array $orderIds
     * @return array
     */
    public function convertOrderIdsToCustomerIds(array $orderIds): array
    {
        return $this->orderResource->retrieveCustomerIdsByOrderIds($orderIds);
    }

    /**
     * Get Index Insert Itrator
     *
     * @param  array $ids
     * @return \Generator
     */
    public function getIndexInsertIterator(array $ids = []): \Generator
    {
        try {
            foreach ($this->orderResource->retrieveIndexData($ids) as $data) {
                if ($index = $this->formatIndexData($data)) {
                    yield $data['customer_id'] => $index;
                }
            }
        } catch (\Exception $exception) {
            $this->logger->error($exception->getMessage());
        }
    }

    /**
     * Format Index Data
     *
     * @param  array $data
     * @return array
     */
    private function formatIndexData(array $data): array
    {
        if (empty($data['customer_id'])) {
            return [];
        }

        return [
            IndexStructure::CUSTOMER_ID => (int)$data['customer_id'],
            IndexStructure::APP_ORDERS_COUNT => $data['a'] ?? .0,
            IndexStructure::WEB_ORDERS_COUNT => $data['w'] ?? 0
        ];
    }
}
