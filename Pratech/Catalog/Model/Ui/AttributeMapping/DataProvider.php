<?php
/**
 * Pratech_Catalog
 *
 * PHP version 8.x
 *
 * @category  PHP
 * @package   Pratech\Catalog
 * @author    Vivek Kumar <vivek.kumar@pratechbrands.com>
 * @copyright 2024 Copyright (c) Pratech Brands Private Limited
 * @link      https://pratechbrands.com/
 **/

namespace Pratech\Catalog\Model\Ui\AttributeMapping;

use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Ui\DataProvider\AbstractDataProvider;
use Pratech\Catalog\Model\AttributeMapping;
use Pratech\Catalog\Model\ResourceModel\AttributeMapping\CollectionFactory;

/**
 * Class DataProvider of AttributeMapping Management
 */
class DataProvider extends AbstractDataProvider
{

    /**
     * @var $loadedData
     */
    protected $loadedData;

    /**
     * @var AttributeMapping
     */
    protected $mappingModel;

    /**
     * DataProvider constructor
     *
     * @param string            $name
     * @param string            $primaryFieldName
     * @param string            $requestFieldName
     * @param CollectionFactory $collectionFactory
     * @param AttributeMapping  $mappingModel
     * @param array             $meta
     * @param array             $data
     */
    public function __construct(
        $name,
        $primaryFieldName,
        $requestFieldName,
        CollectionFactory $collectionFactory,
        AttributeMapping $mappingModel,
        array $meta = [],
        array $data = []
    ) {
        $this->collection = $collectionFactory->create();
        $this->mappingModel = $mappingModel;
        parent::__construct($name, $primaryFieldName, $requestFieldName, $meta, $data);
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

        foreach ($items as $mappingData) {
            $authorInfo = $mappingData->getData();

            $mappingData->setData($authorInfo);

            $this->loadedData[$mappingData->getId()] = $mappingData->getData();
            $this->loadedData[$mappingData->getId()]['attributes'] = explode(',', $mappingData->getAttributes());
        }
        return $this->loadedData;
    }
}
