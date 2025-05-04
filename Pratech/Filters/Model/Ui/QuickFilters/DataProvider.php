<?php
/**
 * Pratech_Filters
 *
 * PHP version 8.x
 *
 * @category  PHP
 * @package   Pratech\Filters
 * @author    Vivek Kumar <vivek.kumar@pratechbrands.com>
 * @copyright 2024 Copyright (c) Pratech Brands Private Limited
 * @link      https://pratechbrands.com/
 **/

namespace Pratech\Filters\Model\Ui\QuickFilters;

use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Store\Model\StoreManagerInterface;
use Magento\Ui\DataProvider\AbstractDataProvider;
use Pratech\Filters\Model\QuickFilters;
use Pratech\Filters\Model\ResourceModel\QuickFilters\CollectionFactory;

/**
 * Class DataProvider of Quick Filters
 */
class DataProvider extends AbstractDataProvider
{

    /**
     * @var CollectionFactory
     */
    protected $collectionFactory;

    /**
     * @var $loadedData
     */
    protected $loadedData;

    /**
     * @var StoreManagerInterface
     */
    protected $storeManager;

    /**
     * @var Banner
     */
    protected $filtersModel;

    /**
     * DataProvider constructor
     *
     * @param string $name
     * @param string $primaryFieldName
     * @param string $requestFieldName
     * @param CollectionFactory $collectionFactory
     * @param Banner $filtersModel
     * @param StoreManagerInterface $storeManager
     * @param array $meta
     * @param array $data
     */
    public function __construct(
        $name,
        $primaryFieldName,
        $requestFieldName,
        CollectionFactory $collectionFactory,
        QuickFilters $filtersModel,
        StoreManagerInterface $storeManager,
        array $meta = [],
        array $data = []
    ) {
        $this->collection = $collectionFactory->create();
        $this->filtersModel = $filtersModel;
        $this->storeManager = $storeManager;
        parent::__construct(
            $name,
            $primaryFieldName,
            $requestFieldName,
            $meta,
            $data
        );
    }

    /**
     * Get data
     *
     * @return array
     * @throws NoSuchEntityException
     */
    public function getData()
    {
        if (isset($this->loadedData)) {
            return $this->loadedData;
        }

        $items = $this->collection->getItems();
        $this->loadedData = [];

        foreach ($items as $filterData) {
            $data = $filterData->getData();

            if (isset($data['filters_data'])) {
                $filtersData = json_decode($data['filters_data'], true);

                foreach ($filtersData as &$filter) {
                    if (isset($filter['attribute_value']) && is_array($filter['attribute_value'])) {
                        $filter['attribute_value'] = array_column($filter['attribute_value'], 'value');
                    }
                }

                $data['filters_data'] = $filtersData;
            }

            $this->loadedData[$filterData->getId()] = $data;
        }
        return $this->loadedData;
    }
}
