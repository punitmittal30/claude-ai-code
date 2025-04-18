<?php
/**
 * Pratech_CmsBlock
 *
 * PHP version 8.x
 *
 * @category  PHP
 * @package   Pratech\CmsBlock
 * @author    Vivek Kumar <vivek.kumar@pratechbrands.com>
 * @copyright 2022 Copyright (c) Pratech Brands Private Limited
 * @link      https://pratechbrands.com/
 **/

namespace Pratech\CmsBlock\Model\Ui\Author;

use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Ui\DataProvider\AbstractDataProvider;
use Pratech\CmsBlock\Model\Author;
use Pratech\CmsBlock\Model\ResourceModel\Author\CollectionFactory;

/**
 * Class DataProvider of Author Management
 */
class DataProvider extends AbstractDataProvider
{

    /**
     * @var $loadedData
     */
    protected $loadedData;

    /**
     * @var Author
     */
    protected $authorModel;

    /**
     * DataProvider constructor
     *
     * @param string            $name
     * @param string            $primaryFieldName
     * @param string            $requestFieldName
     * @param CollectionFactory $collectionFactory
     * @param Author            $authorModel
     * @param array             $meta
     * @param array             $data
     */
    public function __construct(
        $name,
        $primaryFieldName,
        $requestFieldName,
        CollectionFactory $collectionFactory,
        Author $authorModel,
        array $meta = [],
        array $data = []
    ) {
        $this->collection = $collectionFactory->create();
        $this->authorModel = $authorModel;
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

        foreach ($items as $authorData) {

            $authorInfo = $authorData->getData();

            $authorData->setData($authorInfo);

            $this->loadedData[$authorData->getId()] = $authorData->getData();
        }
        return $this->loadedData;
    }
}
