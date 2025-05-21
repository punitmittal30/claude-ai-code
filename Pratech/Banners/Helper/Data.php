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

namespace Pratech\Banners\Helper;

use Magento\Catalog\Model\ResourceModel\Category\CollectionFactory;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\UrlInterface;
use Magento\Store\Model\StoreManagerInterface;
use Pratech\Banners\Model\BannerFactory;
use Pratech\Banners\Model\ResourceModel\Banner;
use Pratech\Banners\Model\ResourceModel\Slider\Collection;
use Pratech\Base\Logger\Logger;
use Pratech\Catalog\Helper\Product;

/**
 * Banner Helper Class
 */
class Data
{
    /**
     * Constant for web
     */
    public const WEB = 'web';

    /**
     * Constant for mWeb
     */
    public const M_WEB = 'mWeb';

    /**
     * Constant for app
     */
    public const APP = 'app';

    /**
     * Constant for image location.
     */
    public const IMAGE_LOCATION = 'banner/feature/';

    /**
     * @param CollectionFactory $categoryCollectionFactory
     * @param Collection $sliderCollection
     * @param BannerFactory $bannerFactory
     * @param Product $productHelper
     * @param Banner $bannerResource
     * @param StoreManagerInterface $storeManager
     * @param Logger $apiLogger
     */
    public function __construct(
        private CollectionFactory     $categoryCollectionFactory,
        private Collection            $sliderCollection,
        private BannerFactory         $bannerFactory,
        private Product               $productHelper,
        private Banner                $bannerResource,
        private StoreManagerInterface $storeManager,
        private Logger                $apiLogger
    )
    {
    }

    /**
     * Get Banners By Category ID
     *
     * @param int $categoryId
     * @param string $type
     * @param int|null $pincode
     * @return array
     * @throws NoSuchEntityException
     */
    public function getBannersByCategoryId(int $categoryId, string $type, int $pincode = null): array
    {
        return $this->getSlider($categoryId, $type, $pincode);
    }

    /**
     * Get Slider
     *
     * @param int $categoryId
     * @param string $type
     * @param int|null $pincode
     * @return array
     * @throws NoSuchEntityException
     */
    private function getSlider(int $categoryId, string $type, int $pincode = null): array
    {
        $sliderData = [];
        if (empty($type)) {
            $collection = $this->sliderCollection
                ->addFieldToFilter('location', ['eq' => $categoryId])
                ->addFieldToFilter('status', ['eq' => 1]);
        } else {
            $collection = $this->sliderCollection
                ->addFieldToFilter('location', ['eq' => $categoryId])
                ->addFieldToFilter('type', ['eq' => $type])
                ->addFieldToFilter('status', ['eq' => 1]);
        }

        if ($collection->getSize() < 1) {
            throw new NoSuchEntityException(
                __("The category that was requested doesn't exist. Verify the category and try again.")
            );
        } elseif ($collection->getSize() == 1) {
            $slider = $collection->getFirstItem()->getData();
            $banners = $this->getPlatformWiseBanner($slider['slider_id'], $pincode);

            $sliderData = [
                'name' => $slider['name'],
                'title' => $slider['title'],
                'sub_title' => $slider['sub_title'],
                'template' => $slider['template'],
                'description' => $slider['description'],
                'priority' => $slider['priority'],
                'banners' => $banners
            ];
        } else {
            foreach ($collection as $sliderItem) {
                $slider = $sliderItem->getData();
                $banners = $this->getPlatformWiseBanner($slider['slider_id'], $pincode);
                $sliderData[] = [
                    'name' => $slider['name'],
                    'title' => $slider['title'],
                    'sub_title' => $slider['sub_title'],
                    'template' => $slider['template'],
                    'description' => $slider['description'],
                    'priority' => $slider['priority'],
                    'banners' => $banners
                ];
            }
        }
        return $sliderData;
    }

    /**
     * Get Platform Wise Banner
     *
     * @param int $sliderId
     * @param int|null $pincode
     * @return array
     * @throws NoSuchEntityException
     */
    private function getPlatformWiseBanner(int $sliderId, int $pincode = null): array
    {
        $webBanners = $mWebBanners = $appBanners = $products = [];
        $bannerPlatformItem = [];
        $banners = $this->sliderCollection->getResource()->getBanners($sliderId);
        $i = 0;
        foreach ($banners as $bannerId) {
            $bannerInfo = $this->bannerFactory->create()->load($bannerId)->getData();
            $associatedProductIdAndPosition = $this->bannerResource->getProductsAndPosition($bannerId);
            foreach ($associatedProductIdAndPosition as $associatedProductId => $associatedProductPosition) {
                $product = $this->productHelper->formatProductForCarousel($associatedProductId, $pincode);
                if (!empty($product)) {
                    $products[] = $product;
                }
            }
            if ($bannerInfo['status']) {
                $webBanners[$i] = $this->getBanner($bannerInfo, self::WEB);
                $mWebBanners[$i] = $this->getBanner($bannerInfo, self::M_WEB);
                $appBanners[$i] = $this->getBanner($bannerInfo, self::APP);
                $i++;
            }
        }
        $bannerPlatformItem['web'] = $webBanners;
        $bannerPlatformItem['m_web'] = $mWebBanners;
        $bannerPlatformItem['app'] = $appBanners;
        $bannerPlatformItem['products'] = $products;
        return $bannerPlatformItem;
    }

    /**
     * Get Banner
     *
     * @param array $bannerInfo
     * @param string $platform
     * @return array
     */
    private function getBanner(array $bannerInfo, string $platform): array
    {
        $banner = [
            'title' => $bannerInfo['title'],
            'description' => $bannerInfo['description'],
            'action_url' => $bannerInfo['url'],
            'term_and_conditions' => $bannerInfo['term_and_conditions'],
            'new_tab' => (int)$bannerInfo['new_tab'],
            'priority' => $bannerInfo['priority']
        ];

        try {
            $mediaUrl = $this->storeManager->getStore()
                ->getBaseUrl(UrlInterface::URL_TYPE_MEDIA);
        } catch (NoSuchEntityException $e) {
            $this->apiLogger->error($e->getMessage() . __METHOD__ . __LINE__);
            $mediaUrl = "";
        }

        $bannerLocation = $mediaUrl . self::IMAGE_LOCATION;

        switch ($platform) {
            case 'web':
                $banner['url'] = $bannerInfo['desktop_image'];
                if ($bannerInfo['desktop_image']) {
                    $banner['image_url'] = $bannerLocation . str_replace('/', '', $bannerInfo['desktop_image']);
                }
                break;
            case 'mWeb':
                $banner['url'] = $bannerInfo['mobile_image'];
                if ($bannerInfo['mobile_image']) {
                    $banner['image_url'] = $bannerLocation . str_replace('/', '', $bannerInfo['mobile_image']);
                }
                break;
            case 'app':
                $banner['url'] = $bannerInfo['app_image'];
                if ($bannerInfo['app_image']) {
                    $banner['image_url'] = $bannerLocation . str_replace('/', '', $bannerInfo['app_image']);
                }
                break;
        }
        return $banner;
    }

    /**
     * Get Banners By Category Slug
     *
     * @param string $slug
     * @param string $type
     * @param int|null $pincode
     * @return array
     * @throws LocalizedException
     * @throws NoSuchEntityException
     */
    public function getBannersByCategorySlug(string $slug, string $type, int $pincode = null): array
    {
        $category = $this->categoryCollectionFactory->create()
            ->addAttributeToSelect('*')
            ->addAttributeToFilter('url_key', ['eq' => $slug])
            ->addAttributeToFilter('is_active', ['eq' => 1])
            ->getFirstItem();
        if (!$category->getId()) {
            throw new NoSuchEntityException(
                __("The category that was requested doesn't exist. Verify the category and try again.")
            );
        }
        return $this->getSlider($category->getId(), $type, $pincode);
    }
}
