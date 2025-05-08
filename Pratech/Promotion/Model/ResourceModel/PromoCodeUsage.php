<?php
/**
 * Pratech_Promotion
 *
 * PHP version 8.x
 *
 * @category  PHP
 * @package   Pratech\Promotion
 * @author    Puneet Mittal <puneet.mittal@pratechbrands.com>
 * @copyright 2024 Copyright (c) Pratech Brands Private Limited
 * @link      https://pratechbrands.com/
 **/

namespace Pratech\Promotion\Model\ResourceModel;

use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Model\ResourceModel\Db\AbstractDb;

class PromoCodeUsage extends AbstractDb
{
    /**
     * Construct
     *
     * @return void
     */
    protected function _construct(): void
    {
        $this->_init('pratech_promotion_code_usage', 'code_id');
    }

    /**
     * Update Customer Promo Code Times Used.
     *
     * @param int $customerId
     * @param int $codeId
     * @return void
     * @throws LocalizedException
     */
    public function updateCustomerPromoCodeTimesUsed(int $customerId, int $codeId): void
    {
        $connection = $this->getConnection();
        $select = $connection->select();
        $select->from(
            $this->getMainTable(),
            ['times_used']
        )->where(
            'code_id = :code_id'
        )->where(
            'customer_id = :customer_id'
        );

        $timesUsed = $connection->fetchOne($select, [':code_id' => $codeId, ':customer_id' => $customerId]);

        if ($timesUsed !== false) {
            $this->getConnection()->update(
                $this->getMainTable(),
                ['times_used' => $timesUsed + 1],
                ['code_id = ?' => $codeId, 'customer_id = ?' => $customerId]
            );
        } else {
            $this->getConnection()->insert(
                $this->getMainTable(),
                ['code_id' => $codeId, 'customer_id' => $customerId, 'times_used' => 1]
            );
        }
    }
}
