<?php
/**
 * Pratech_Search
 *
 * PHP version 8.x
 *
 * @category  PHP
 * @package   Pratech\Search
 * @author    Vivek Kumar <vivek.kumar@pratechbrands.com>
 * @copyright 2023 Copyright (c) Pratech Brands Private Limited
 * @link      https://pratechbrands.com/
 **/

namespace Pratech\Search\Model\Ui\SearchTerms;

use Magento\Framework\UrlInterface;
use Magento\Ui\DataProvider\AbstractDataProvider;
use Magento\Framework\App\RequestInterface;
use Magento\Store\Model\StoreManagerInterface;
use Pratech\Search\Model\ResourceModel\SearchTerms\CollectionFactory;
use Pratech\Search\Model\SearchTerms;

/**
 * Class DataProvider of Search Terms
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
    protected $searchTermsModel;

    /**
     * DataProvider constructor
     *
     * @param string                $name
     * @param string                $primaryFieldName
     * @param string                $requestFieldName
     * @param CollectionFactory     $collectionFactory
     * @param Banner                $searchTermsModel
     * @param StoreManagerInterface $storeManager
     * @param array                 $meta
     * @param array                 $data
     */
    public function __construct(
        $name,
        $primaryFieldName,
        $requestFieldName,
        CollectionFactory $collectionFactory,
        SearchTerms $searchTermsModel,
        StoreManagerInterface $storeManager,
        array $meta = [],
        array $data = []
    ) {
        $this->collection = $collectionFactory->create();
        $this->searchTermsModel = $searchTermsModel;
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
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function getData()
    {
        if (isset($this->loadedData)) {
            return $this->loadedData;
        }
        $items = $this->collection->getItems();

        $this->loadedData = [];

        foreach ($items as $termsData) {
            $this->loadedData[$termsData->getId()] = $termsData->getData();
        }
        return $this->loadedData;
    }
}
