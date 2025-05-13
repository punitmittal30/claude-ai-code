<?php
/**
 * Pratech_Order
 *
 * PHP version 8.x
 *
 * @category  PHP
 * @package   Pratech\Order
 * @author    Vivek Kumar <vivek.kumar@pratechbrands.com>
 * @copyright 2024 Copyright (c) Pratech Brands Private Limited
 * @link      https://pratechbrands.com/
 **/

namespace Pratech\Order\Setup\Patch\Data;

use Exception;
use Magento\Framework\Setup\Patch\DataPatchInterface;
use Magento\Framework\Setup\ModuleDataSetupInterface;
use Magento\Framework\Setup\ModuleContextInterface;
use Magento\Framework\File\Csv;
use Magento\Framework\App\Filesystem\DirectoryList;

class InsertShipmentStatusDataFromCsv implements DataPatchInterface
{
    /**
     * @param ModuleDataSetupInterface $moduleDataSetup
     * @param DirectoryList            $directoryList
     * @param Csv                      $csvReader
     */
    public function __construct(
        private ModuleDataSetupInterface $moduleDataSetup,
        private DirectoryList $directoryList,
        private Csv $csvReader
    ) {
    }

    /**
     * Insert Shipment Status Data
     *
     * @return void
     */
    public function apply()
    {
        $this->moduleDataSetup->startSetup();

        $filePath = $this->directoryList->getPath(DirectoryList::APP)
                   . '/code/Pratech/Order/Data/import_data.csv';
        
        if (!file_exists($filePath)) {
            throw new Exception("File does not exist: " . $filePath);
        }
        $data = $this->csvReader->getData($filePath);

        array_shift($data);

        // Insert data into the table
        $table = $this->moduleDataSetup->getTable('sales_shipment_status');
        $columns = [
            'clickpost_status_code',
            'clickpost_status',
            'status_code',
            'status',
            'description',
            'journey',
            'comments'
        ];

        foreach ($data as $row) {
            $rowData = array_combine($columns, $row);
            $this->moduleDataSetup->getConnection()->insert($table, $rowData);
        }

        $this->moduleDataSetup->endSetup();
    }

    /**
     * @inheritDoc
     */
    public static function getDependencies()
    {
        return [];
    }

    /**
     * @inheritDoc
     */
    public function getAliases()
    {
        return [];
    }
}
