<?php
/**
 * Pratech_WarehouseImportExport
 *
 * PHP version 8.x
 *
 * @category  PHP
 * @package   Pratech\WarehouseImportExport
 * @author    Puneet Mittal <puneet.mittal@pratechbrands.com>
 * @copyright 2025 Copyright (c) Pratech Brands Private Limited
 * @link      https://pratechbrands.com/
 **/

namespace Pratech\WarehouseImportExport\Model\Import;

use Exception;
use Magento\Framework\App\ResourceConnection;
use Magento\Framework\DB\Adapter\AdapterInterface;
use Magento\Framework\Json\Helper\Data as JsonHelper;
use Magento\ImportExport\Helper\Data as ImportHelper;
use Magento\ImportExport\Model\Import;
use Magento\ImportExport\Model\Import\Entity\AbstractEntity;
use Magento\ImportExport\Model\Import\ErrorProcessing\ProcessingErrorAggregatorInterface;
use Magento\ImportExport\Model\ResourceModel\Helper;
use Magento\ImportExport\Model\ResourceModel\Import\Data;

class Warehouse extends AbstractEntity
{
    public const ENTITY_CODE = 'warehouse';
    public const TABLE = 'pratech_warehouse';
    public const ENTITY_ID_COLUMN = 'warehouse_id';

    /**
     * If we should check column names
     *
     * @var bool
     */
    protected $needColumnCheck = true;

    /**
     * Need to log in import history
     *
     * @var bool
     */
    protected $logInHistory = true;

    /**
     * Permanent entity columns.
     *
     * @var array
     */
    protected $_permanentAttributes = [
        'warehouse_id'
    ];

    /**
     * Valid column names allowed
     *
     * @var array
     */
    protected $validColumnNames = [
        'warehouse_id',
        'warehouse_code',
        'name',
        'warehouse_url',
        'pincode',
        'address',
        'is_active',
        'is_dark_store'
    ];

    /**
     * @var string[]
     */
    protected $columnsToUpdate = [
        'warehouse_code',
        'name',
        'warehouse_url',
        'pincode',
        'address',
        'is_active',
        'is_dark_store'
    ];

    /**
     * @var AdapterInterface
     */
    protected $connection;

    /**
     * @var ResourceConnection
     */
    private $resource;

    /**
     * Constructor
     *
     * @param JsonHelper $jsonHelper
     * @param ImportHelper $importExportData
     * @param Data $importData
     * @param ResourceConnection $resource
     * @param Helper $resourceHelper
     * @param ProcessingErrorAggregatorInterface $errorAggregator
     */
    public function __construct(
        JsonHelper                         $jsonHelper,
        ImportHelper                       $importExportData,
        Data                               $importData,
        ResourceConnection                 $resource,
        Helper                             $resourceHelper,
        ProcessingErrorAggregatorInterface $errorAggregator
    ) {
        $this->jsonHelper = $jsonHelper;
        $this->_importExportData = $importExportData;
        $this->_resourceHelper = $resourceHelper;
        $this->_dataSourceModel = $importData;
        $this->resource = $resource;
        $this->connection = $resource->getConnection();
        $this->errorAggregator = $errorAggregator;
        $this->initMessageTemplates();
    }

    /**
     * Init Error Messages
     */
    private function initMessageTemplates(): void
    {
        $this->addMessageTemplate(
            'WarehouseCodeIsRequired',
            __('Warehouse Code is required')
        );
        $this->addMessageTemplate(
            'NameIsRequired',
            __('Name is required')
        );
        $this->addMessageTemplate(
            'PincodeIsRequired',
            __('Pincode is required')
        );
        $this->addMessageTemplate(
            'AddressIsRequired',
            __('Address is required')
        );
        $this->addMessageTemplate(
            'PincodeIsInvalid',
            __('Pincode must be exactly 6 digits')
        );
        $this->addMessageTemplate(
            'WarehouseCodeDuplicate',
            __('Warehouse Code already exists')
        );
        $this->addMessageTemplate(
            'PincodeDuplicate',
            __('Pincode already exists for another warehouse')
        );
    }

    /**
     * Entity type code getter.
     *
     * @return string
     */
    public function getEntityTypeCode(): string
    {
        return static::ENTITY_CODE;
    }

    /**
     * Get available columns
     *
     * @return array
     */
    public function getValidColumnNames(): array
    {
        return $this->validColumnNames;
    }

    /**
     * Import data
     *
     * @return bool
     * @throws Exception
     */
    protected function _importData(): bool
    {
        switch ($this->getBehavior()) {
            case Import::BEHAVIOR_DELETE:
                $this->deleteEntity();
                break;
            case Import::BEHAVIOR_APPEND:
            case Import::BEHAVIOR_REPLACE:
                $this->saveAndReplaceEntity();
                break;
        }
        return true;
    }

    /**
     * Delete entities
     *
     * @return void
     */
    private function deleteEntity(): void
    {
        $rows = [];
        while ($bunch = $this->_dataSourceModel->getNextBunch()) {
            foreach ($bunch as $rowNum => $rowData) {
                $this->validateRow($rowData, $rowNum);
                if (!$this->getErrorAggregator()->isRowInvalid($rowNum)) {
                    $rowId = $rowData[static::ENTITY_ID_COLUMN];
                    $rows[] = $rowId;
                }
                if ($this->getErrorAggregator()->hasToBeTerminated()) {
                    $this->getErrorAggregator()->addRowToSkip($rowNum);
                }
            }
        }
        if ($rows) {
            $this->deleteEntityFinish(array_unique($rows));
        }
    }

    /**
     * Row validation
     *
     * @param array $rowData
     * @param int $rowNum
     * @return bool
     */
    public function validateRow(array $rowData, $rowNum): bool
    {
        $warehouseCode = $rowData['warehouse_code'] ?? '';
        $name = $rowData['name'] ?? '';
        $pincode = $rowData['pincode'] ?? '';
        $address = $rowData['address'] ?? '';
        $isActive = isset($rowData['is_active']) ? (int)$rowData['is_active'] : 1;
        $isDarkStore = isset($rowData['is_dark_store']) ? (int)$rowData['is_dark_store'] : 0;

        // Check required fields
        if (!$warehouseCode) {
            $this->addRowError('WarehouseCodeIsRequired', $rowNum);
        }
        if (!$name) {
            $this->addRowError('NameIsRequired', $rowNum);
        }
        if (!$pincode) {
            $this->addRowError('PincodeIsRequired', $rowNum);
        } elseif (strlen($pincode) != 6 || !is_numeric($pincode)) {
            $this->addRowError('PincodeIsInvalid', $rowNum);
        }
        if (!$address) {
            $this->addRowError('AddressIsRequired', $rowNum);
        }

        // Check unique constraints if this is a new warehouse
        if (!isset($rowData[static::ENTITY_ID_COLUMN]) || empty($rowData[static::ENTITY_ID_COLUMN])) {
            // Check warehouse_code uniqueness
            $warehouseCodeExists = $this->connection->fetchOne(
                $this->connection->select()
                    ->from($this->connection->getTableName(static::TABLE), ['COUNT(*)'])
                    ->where('warehouse_code = ?', $warehouseCode)
            );

            if ($warehouseCodeExists) {
                $this->addRowError('WarehouseCodeDuplicate', $rowNum);
            }

            // Check pincode uniqueness
            $pincodeExists = $this->connection->fetchOne(
                $this->connection->select()
                    ->from($this->connection->getTableName(static::TABLE), ['COUNT(*)'])
                    ->where('pincode = ?', $pincode)
            );

            if ($pincodeExists) {
                $this->addRowError('PincodeDuplicate', $rowNum);
            }
        }

        if (isset($this->_validatedRows[$rowNum])) {
            return !$this->getErrorAggregator()->isRowInvalid($rowNum);
        }

        $this->_validatedRows[$rowNum] = true;
        return !$this->getErrorAggregator()->isRowInvalid($rowNum);
    }

    /**
     * Delete entities
     *
     * @param array $entityIds
     * @return bool
     */
    private function deleteEntityFinish(array $entityIds): bool
    {
        if ($entityIds) {
            try {
                $this->countItemsDeleted += $this->connection->delete(
                    $this->connection->getTableName(static::TABLE),
                    $this->connection->quoteInto(static::ENTITY_ID_COLUMN . ' IN (?)', $entityIds)
                );
                return true;
            } catch (Exception $e) {
                return false;
            }
        }
        return false;
    }

    /**
     * Save and replace entities
     *
     * @return void
     */
    private function saveAndReplaceEntity(): void
    {
        $behavior = $this->getBehavior();
        $rows = [];
        while ($bunch = $this->_dataSourceModel->getNextBunch()) {
            $entityList = [];
            foreach ($bunch as $rowNum => $row) {
                if (!$this->validateRow($row, $rowNum)) {
                    continue;
                }
                if ($this->getErrorAggregator()->hasToBeTerminated()) {
                    $this->getErrorAggregator()->addRowToSkip($rowNum);
                    continue;
                }
                $rowId = $row[static::ENTITY_ID_COLUMN];
                $rows[] = $rowId;
                $columnValues = [];
                foreach ($this->getAvailableColumns() as $columnKey) {
                    $columnValues[$columnKey] = $row[$columnKey];
                }
                $entityList[$rowId][] = $columnValues;
                $this->countItemsCreated += (int)!isset($row[static::ENTITY_ID_COLUMN]);
                $this->countItemsUpdated += (int)isset($row[static::ENTITY_ID_COLUMN]);
            }
            if (Import::BEHAVIOR_REPLACE === $behavior) {
                if ($rows && $this->deleteEntityFinish(array_unique($rows))) {
                    $this->saveEntityFinish($entityList);
                }
            } elseif (Import::BEHAVIOR_APPEND === $behavior) {
                $this->saveEntityFinish($entityList);
            }
        }
    }

    /**
     * Get available columns
     *
     * @return array
     */
    private function getAvailableColumns(): array
    {
        return $this->validColumnNames;
    }

    /**
     * Save entities
     *
     * @param array $entityData
     * @return void
     */
    private function saveEntityFinish(array $entityData): void
    {
        if ($entityData) {
            $tableName = $this->connection->getTableName(static::TABLE);
            $rows = [];
            foreach ($entityData as $entityRows) {
                foreach ($entityRows as $row) {
                    $rows[] = $row;
                }
            }
            if ($rows) {
                $this->connection->insertOnDuplicate($tableName, $rows, $this->getColumnsToUpdate());
            }
        }
    }

    /**
     * Get columns to update.
     *
     * @return array
     */
    private function getColumnsToUpdate(): array
    {
        return $this->columnsToUpdate;
    }
}
