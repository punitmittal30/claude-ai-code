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

namespace Pratech\Warehouse\Model\Ui\Inventory;

use Magento\Catalog\Model\ResourceModel\Product\CollectionFactory as ProductCollectionFactory;
use Magento\Framework\App\Request\DataPersistorInterface;
use Magento\Ui\DataProvider\AbstractDataProvider;
use Pratech\Warehouse\Model\ResourceModel\WarehouseInventory\CollectionFactory;

class DataProvider extends AbstractDataProvider
{
    /**
     * @var CollectionFactory
     */
    protected $collection;

    /**
     * @var array
     */
    protected $loadedData;

    /**
     * @param string $name
     * @param string $primaryFieldName
     * @param string $requestFieldName
     * @param CollectionFactory $collectionFactory
     * @param DataPersistorInterface $dataPersistor
     * @param ProductCollectionFactory $productCollectionFactory
     * @param array $meta
     * @param array $data
     */
    public function __construct(
        $name,
        $primaryFieldName,
        $requestFieldName,
        CollectionFactory $collectionFactory,
        protected DataPersistorInterface $dataPersistor,
        protected ProductCollectionFactory $productCollectionFactory,
        array $meta = [],
        array $data = []
    ) {
        $this->collection = $collectionFactory->create();
        parent::__construct($name, $primaryFieldName, $requestFieldName, $meta, $data);
    }

    /**
     * Get data
     *
     * @return array
     */
    public function getData()
    {
        if (isset($this->loadedData)) {
            return $this->loadedData;
        }

        $items = $this->collection->getItems();
        foreach ($items as $inventory) {
            $this->loadedData[$inventory->getId()] = $inventory->getData();

            // Add product name if available
            if ($inventory->getSku()) {
                $productCollection = $this->productCollectionFactory->create();
                $productCollection->addFieldToFilter('sku', $inventory->getSku())
                    ->addAttributeToSelect('name');
                $product = $productCollection->getFirstItem();
                if ($product->getId()) {
                    $this->loadedData[$inventory->getId()]['product_name'] = $product->getName();
                }
            }
        }

        $data = $this->dataPersistor->get('warehouse_inventory');
        if (!empty($data)) {
            $inventory = $this->collection->getNewEmptyItem();
            $inventory->setData($data);
            $this->loadedData[$inventory->getId()] = $inventory->getData();
            $this->dataPersistor->clear('warehouse_inventory');
        }

        return $this->loadedData;
    }
}
