<?php
/**
 * Pratech_Blog
 *
 * PHP version 8.x
 *
 * @category  PHP
 * @package   Pratech\Blog
 * @author    Akash Panwar <akash.panwar@pratechbrands.com>
 * @copyright 2024 Copyright (c) Pratech Brands Private Limited
 * @link      https://pratechbrands.com/
 **/
declare(strict_types=1);

namespace Pratech\Blog\Model\Category;

use Magento\Framework\App\Request\DataPersistorInterface;
use Magento\Framework\UrlInterface;
use Magento\Store\Model\StoreManagerInterface;
use Magento\Ui\DataProvider\AbstractDataProvider;
use Pratech\Blog\Model\ResourceModel\Category\CollectionFactory;

class DataProvider extends AbstractDataProvider
{
    /**
     * Constant for image location.
     */
    public const IMAGE_LOCATION = 'cms/image';

    /**
     * @var array
     */
    protected $loadedData;

    /**
     * @var \Pratech\Blog\Model\ResourceModel\Category\Collection
     */
    protected $collection;

    /**
     * @var DataPersistorInterface
     */
    protected $dataPersistor;

    /**
     * @param string $name
     * @param string $primaryFieldName
     * @param string $requestFieldName
     * @param CollectionFactory $collectionFactory
     * @param DataPersistorInterface $dataPersistor
     * @param StoreManagerInterface $storeManager
     * @param Logger $apiLogger
     * @param array $meta
     * @param array $data
     */
    public function __construct(
        $name,
        $primaryFieldName,
        $requestFieldName,
        CollectionFactory $collectionFactory,
        DataPersistorInterface $dataPersistor,
        private StoreManagerInterface $storeManager,
        private Logger $apiLogger,
        array $meta = [],
        array $data = []
    ) {
        $this->collection = $collectionFactory->create();
        $this->dataPersistor = $dataPersistor;
        parent::__construct($name, $primaryFieldName, $requestFieldName, $meta, $data);
    }

    /**
     * @inheritDoc
     */
    public function getData()
    {
        if (isset($this->loadedData)) {
            return $this->loadedData;
        }
        $mediaUrl = $this->storeManager->getStore()->getBaseUrl(UrlInterface::URL_TYPE_MEDIA);
        $items = $this->collection->getItems();
        foreach ($items as $model) {
            $categoryInfo = $model->getData();
            if (isset($categoryInfo['thumbnail_image'])) {
                $imgArr = [];
                $name = $categoryInfo['thumbnail_image'];
                $imgArr[0]['name'] = $name;
                $imgArr[0]['url'] = $mediaUrl . self::IMAGE_LOCATION . '/' . $name;
                $imgArr[0]['type'] = 'image';

                unset($model['thumbnail_image']);
                $categoryInfo['thumbnail_image'] = $imgArr;
            }
            if (isset($categoryInfo['thumbnail_image_mobile'])) {
                $imgArr = [];
                $name = $categoryInfo['thumbnail_image_mobile'];
                $imgArr[0]['name'] = $name;
                $imgArr[0]['url'] = $mediaUrl . self::IMAGE_LOCATION . '/' . $name;
                $imgArr[0]['type'] = 'image';

                unset($model['thumbnail_image_mobile']);
                $categoryInfo['thumbnail_image_mobile'] = $imgArr;
            }
            if (isset($categoryInfo['thumbnail_image_app'])) {
                $imgArr = [];
                $name = $categoryInfo['thumbnail_image_app'];
                $imgArr[0]['name'] = $name;
                $imgArr[0]['url'] = $mediaUrl . self::IMAGE_LOCATION . '/' . $name;
                $imgArr[0]['type'] = 'image';

                unset($model['thumbnail_image_app']);
                $categoryInfo['thumbnail_image_app'] = $imgArr;
            }
            if (isset($categoryInfo['banner_image'])) {
                $imgArr = [];
                $name = $categoryInfo['banner_image'];
                $imgArr[0]['name'] = $name;
                $imgArr[0]['url'] = $mediaUrl . self::IMAGE_LOCATION . '/' . $name;
                $imgArr[0]['type'] = 'image';

                unset($model['banner_image']);
                $categoryInfo['banner_image'] = $imgArr;
            }
            if (isset($categoryInfo['banner_image_mobile'])) {
                $imgArr = [];
                $name = $categoryInfo['banner_image_mobile'];
                $imgArr[0]['name'] = $name;
                $imgArr[0]['url'] = $mediaUrl . self::IMAGE_LOCATION . '/' . $name;
                $imgArr[0]['type'] = 'image';

                unset($model['banner_image_mobile']);
                $categoryInfo['banner_image_mobile'] = $imgArr;
            }
            if (isset($categoryInfo['banner_image_app'])) {
                $imgArr = [];
                $name = $categoryInfo['banner_image_app'];
                $imgArr[0]['name'] = $name;
                $imgArr[0]['url'] = $mediaUrl . self::IMAGE_LOCATION . '/' . $name;
                $imgArr[0]['type'] = 'image';

                unset($model['banner_image_app']);
                $categoryInfo['banner_image_app'] = $imgArr;
            }
            $this->loadedData[$model->getId()] = $categoryInfo;
        }
        $data = $this->dataPersistor->get('pratech_blog_category');
        
        if (!empty($data)) {
            $model = $this->collection->getNewEmptyItem();
            $model->setData($data);
            $this->loadedData[$model->getId()] = $model->getData();
            $this->dataPersistor->clear('pratech_blog_category');
        }
        
        return $this->loadedData;
    }
}
