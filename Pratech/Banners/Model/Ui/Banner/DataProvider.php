<?php
/**
 * Pratech_Banners
 *
 * PHP version 8.x
 *
 * @category  PHP
 * @package   Pratech\Banners
 * @author    Puneet Mittal <puneet.mittal@pratechbrands.com>
 * @copyright 2023 Copyright (c) Pratech Brands Private Limited
 * @link      https://pratechbrands.com/
 **/

namespace Pratech\Banners\Model\Ui\Banner;

use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\UrlInterface;
use Magento\Store\Model\StoreManagerInterface;
use Magento\Ui\DataProvider\AbstractDataProvider;
use Pratech\Banners\Model\Banner;
use Pratech\Banners\Model\ResourceModel\Banner\CollectionFactory;

/**
 * Class DataProvider of Banner Management
 */
class DataProvider extends AbstractDataProvider
{
    /**
     * Constant for image location.
     */
    public const IMAGE_LOCATION = 'banner/feature/';

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
    protected $bannerModel;

    /**
     * DataProvider constructor
     *
     * @param string $name
     * @param string $primaryFieldName
     * @param string $requestFieldName
     * @param CollectionFactory $collectionFactory
     * @param Banner $bannerModel
     * @param StoreManagerInterface $storeManager
     * @param array $meta
     * @param array $data
     */
    public function __construct(
        $name,
        $primaryFieldName,
        $requestFieldName,
        CollectionFactory $collectionFactory,
        Banner $bannerModel,
        StoreManagerInterface $storeManager,
        array $meta = [],
        array $data = []
    ) {
        $this->collection = $collectionFactory->create();
        $this->bannerModel = $bannerModel;
        $this->storeManager = $storeManager;
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

        $mediaUrl = $this->storeManager->getStore()
            ->getBaseUrl(UrlInterface::URL_TYPE_MEDIA);

        $items = $this->collection->getItems();

        $this->loadedData = [];

        foreach ($items as $bannerData) {

            $bannerInfo = $bannerData->getData();

            if (isset($bannerInfo['desktop_image'])) {

                $imgArr = [];
                $name = $bannerInfo['desktop_image'];
                $imgPath = str_replace('//', '/', self::IMAGE_LOCATION . $name);
                $imgArr[0]['name'] = $name;
                $imgArr[0]['url'] = $mediaUrl . $imgPath;
                $imgArr[0]['type'] = 'image';

                unset($bannerData['desktop_image']);
                $bannerInfo['desktop_image'] = $imgArr;
            }

            if (isset($bannerInfo['mobile_image'])) {

                $imgArr = [];
                $name = $bannerInfo['mobile_image'];
                $imgPath = str_replace('//', '/', self::IMAGE_LOCATION . $name);
                $imgArr[0]['name'] = $name;
                $imgArr[0]['url'] = $mediaUrl . $imgPath;
                $imgArr[0]['type'] = 'image';

                unset($bannerData['mobile_image']);
                $bannerInfo['mobile_image'] = $imgArr;
            }

            if (isset($bannerInfo['app_image'])) {

                $imgArr = [];
                $name = $bannerInfo['app_image'];
                $imgPath = str_replace('//', '/', self::IMAGE_LOCATION . $name);
                $imgArr[0]['name'] = $name;
                $imgArr[0]['url'] = $mediaUrl . $imgPath;
                $imgArr[0]['type'] = 'image';

                unset($bannerData['app_image']);
                $bannerInfo['app_image'] = $imgArr;
            }

            $sliderId = $this->bannerModel->getSliderId($bannerInfo['banner_id']);

            $bannerInfo['assign_to_slider'] = $sliderId;
            $bannerInfo['position'] = $this->bannerModel->getBannerPosition();
            $bannerData->setData($bannerInfo);

            $this->loadedData[$bannerData->getId()] = $bannerData->getData();
        }
        return $this->loadedData;
    }
}
