<?php

namespace Pratech\Warehouse\Model\ResourceModel;

use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Model\ResourceModel\Db\AbstractDb;

class WarehouseSla extends AbstractDb
{
    /**
     * Constant for table name.
     */
    public const TABLE_NAME = 'pratech_warehouse_sla';

    /**
     * Construct Method.
     *
     * @return void
     */
    protected function _construct(): void
    {
        $this->_init('pratech_warehouse_sla', 'sla_id');
    }

    /**
     * Get Min Delivery Time By Pincode.
     *
     * @param int $customerPincode
     * @return string
     * @throws LocalizedException
     */
    public function getMinDeliveryTimeByPincode(int $customerPincode): string
    {
        try {
            $connection = $this->getConnection();

            $select = $connection->select()
                ->from(
                    ['main_table' => $this->getMainTable()],
                    ['min_delivery_time' => new \Zend_Db_Expr('MIN(delivery_time)')]
                )
                ->where('customer_pincode = ?', $customerPincode);

            return $connection->fetchOne($select);

        } catch (\Exception $e) {
            throw new LocalizedException(
                __(
                    'Could not fetch minimum delivery time for pincode %1: %2',
                    $customerPincode,
                    $e->getMessage()
                )
            );
        }
    }
}
