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

namespace Pratech\Warehouse\Model\ResourceModel;

use Magento\Framework\Model\ResourceModel\Db\AbstractDb;

class Warehouse extends AbstractDb
{

    /**
     * Constant for table name.
     */
    public const TABLE_NAME = 'pratech_warehouse';

    /**
     * Constant for table name column.
     */
    public const WAREHOUSE_CODE_COLUMN = 'warehouse_code';

    /**
     * Constant for table name column pincode.
     */
    public const WAREHOUSE_PINCODE_COLUMN = 'pincode';

    /**
     * Construct Method.
     *
     * @return void
     */
    protected function _construct(): void
    {
        $this->_init('pratech_warehouse', 'warehouse_id');
    }

    /**
     * Get All Enabled Warehouse Codes.
     *
     * @return array
     */
    public function getAllEnabledWarehouseCodes(): array
    {
        $adapter = $this->getConnection();
        $select = $adapter->select()
            ->from(self::TABLE_NAME, self::WAREHOUSE_CODE_COLUMN)
            ->where('is_active = ?', 1);

        return $adapter->fetchCol($select);
    }

    /**
     * Get All Enabled Warehouse Codes.
     *
     * @param array $warehousePincodes
     * @return array
     */
    public function getWarehousesPinCodeAndCode(array $warehousePincodes): array
    {
        $adapter = $this->getConnection();
        $select = $adapter->select()
            ->from(self::TABLE_NAME, [self::WAREHOUSE_PINCODE_COLUMN, self::WAREHOUSE_CODE_COLUMN])
            ->where('pincode IN(?)', $warehousePincodes);

        return $adapter->fetchPairs($select);
    }
}
