<?php

namespace Pratech\Customer\Model\ResourceModel;

use Magento\Framework\Model\ResourceModel\Db\AbstractDb;

class BlockedCustomers extends AbstractDb
{
    public const PRIMARY_TABLE = 'blocked_customers';

    /**
     * Construct
     *
     * @return void
     */
    protected function _construct(): void
    {
        $this->_init('blocked_customers', 'entity_id');
    }

    /**
     * Get Blocked Customer By Mobile Number.
     *
     * @param string $mobileNumber
     * @return array
     */
    public function getBlockedCustomerByMobileNumber(string $mobileNumber): array
    {
        $adapter = $this->getConnection();

        $select = $adapter->select()
            ->from(self::PRIMARY_TABLE, 'mobile_number')
            ->where('mobile_number = ?', $mobileNumber);

        return $adapter->fetchCol($select);
    }
}
