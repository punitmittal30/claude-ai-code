<?php
/**
 * Pratech_VideoContent
 *
 * PHP version 8.x
 *
 * @category  PHP
 * @package   Pratech\VideoContent
 * @author    Vivek Kumar <vivek.kumar@pratechbrands.com>
 * @copyright 2025 Copyright (c) Pratech Brands Private Limited
 * @link      https://pratechbrands.com/
 **/

namespace Pratech\VideoContent\Helper;

use Exception;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\UrlInterface;
use Magento\Store\Model\StoreManagerInterface;
use Pratech\Base\Logger\Logger;
use Pratech\Base\Helper\Data as BaseHelper;
use Pratech\Catalog\Helper\Product;
use Pratech\VideoContent\Model\ResourceModel\Slider\CollectionFactory as SliderCollectionFactory;
use Pratech\VideoContent\Model\ResourceModel\Video as VideoResource;
use Pratech\VideoContent\Model\ResourceModel\Video\CollectionFactory as VideoCollectionFactory;
use Pratech\Warehouse\Model\ResourceModel\Pincode\CollectionFactory;

/**
 * Video helper class to provide data to video api endpoints.
 */
class Video
{
    /**
     * Video Helper Constructor
     *
     * @param Logger $logger
     * @param BaseHelper $baseHelper
     * @param StoreManagerInterface $storeManager
     * @param SliderCollectionFactory $sliderCollectionFactory
     * @param VideoCollectionFactory $videoCollectionFactory
     * @param CollectionFactory $pincodeCollectionFactory
     * @param VideoResource $videoResource
     * @param Product $productHelper
     */
    public function __construct(
        private Logger                  $logger,
        private BaseHelper              $baseHelper,
        private StoreManagerInterface   $storeManager,
        private SliderCollectionFactory $sliderCollectionFactory,
        private VideoCollectionFactory  $videoCollectionFactory,
        private CollectionFactory       $pincodeCollectionFactory,
        private VideoResource           $videoResource,
        private Product                 $productHelper
    ) {
    }

    /**
     * Get Video Data
     *
     * @param string $platform
     * @param int $pincode
     * @return array
     * @throws NoSuchEntityException
     */
    public function getVideos(string $platform, int $pincode): array
    {
        $pincodeCollection = $this->pincodeCollectionFactory->create()
            ->addFieldToFilter('pincode', $pincode)
            ->getFirstItem();

        $city = $pincodeCollection->getCity();
        $result = [];
        try {
            $mediaUrl = $this->storeManager->getStore()
                ->getBaseUrl(UrlInterface::URL_TYPE_MEDIA);
        } catch (NoSuchEntityException $e) {
            $this->logger->error($e->getMessage() . __METHOD__ . __LINE__);
            $mediaUrl = "";
        }

        try {
            $videoCollection = $this->videoCollectionFactory->create()
                ->addFieldToFilter('is_active', 1)
                ->addFieldToFilter('platform', ['finset' => $platform])
                ->addFieldToFilter(
                    'cities',
                    [
                        ['finset' => $city],
                        ['finset' => 'all']
                    ]
                );
            foreach ($videoCollection as $video) {
                $result[] = [
                    'title' => $video->getTitle(),
                    'url' => $mediaUrl . $video->getUrl(),
                    'pages' => $video->getPage()
                        ? explode(",", $video->getPage()) : "",
                    'start_date' => $video->getStartDate()
                        ? $this->baseHelper->getDateTimeBasedOnTimezone($video->getStartDate()) : "",
                    'end_date' => $video->getEndDate()
                        ? $this->baseHelper->getDateTimeBasedOnTimezone($video->getEndDate()) : "",
                    'display_timing' => $video->getDisplayTiming(),
                    'shop_now_url' => $video->getShopNowUrl(),
                    'video_for' => $video->getVideoFor()
                        ? explode(",", $video->getVideoFor()) : "",
                ];
            }
        } catch (NoSuchEntityException|LocalizedException $exception) {
            $this->logger->error($exception->getMessage() . __METHOD__);
        }
        return $result;
    }

    /**
     * Get Video Carousel Data
     *
     * @param string $page
     * @param string $platform
     * @param int $pincode
     * @return array
     * @throws NoSuchEntityException
     */
    public function getVideosCarousel(string $page, string $platform, string $pincode): array
    {
        $pincodeCollection = $this->pincodeCollectionFactory->create()
            ->addFieldToFilter('pincode', $pincode)
            ->getFirstItem();

        $city = $pincodeCollection->getCity();
        $result = [];
        try {
            $mediaUrl = $this->storeManager->getStore()
                ->getBaseUrl(UrlInterface::URL_TYPE_MEDIA);
        } catch (NoSuchEntityException $e) {
            $this->logger->error($e->getMessage() . __METHOD__ . __LINE__);
            $mediaUrl = "";
        }

        try {
            $sliderCollection = $this->sliderCollectionFactory->create()
                ->addFieldToFilter('status', 1)
                ->addFieldToFilter('page', ['finset' => $page]);

            foreach ($sliderCollection as $slider) {
                $videoIds = $slider->getVideoIds();
                $videoCollection = $this->videoCollectionFactory->create()
                    ->addFieldToFilter('is_active', 1)
                    ->addFieldToFilter('video_id', ['in' => $videoIds])
                    ->addFieldToFilter('platform', ['finset' => $platform])
                    ->addFieldToFilter(
                        'cities',
                        [
                            ['finset' => $city],
                            ['finset' => 'all']
                        ]
                    );
                $videos = [];
                foreach ($videoCollection as $video) {
                    $products = [];
                    $associatedProductIdAndPosition = $this->videoResource->getProductsAndPosition($video->getId());
                    foreach ($associatedProductIdAndPosition as $associatedProductId => $associatedProductPosition) {
                        $products[] = $this->productHelper->formatProductForCarousel($associatedProductId, $pincode);
                    }
                    $videos[] = [
                        'title' => $video->getTitle(),
                        'url' => $mediaUrl . $video->getUrl(),
                        'display_timing' => $video->getDisplayTiming(),
                        'shop_now_url' => $video->getShopNowUrl(),
                        'platform' => $video->getPlatform()
                            ? explode(",", $video->getPlatform()) : null,
                        'video_for' => $video->getVideoFor()
                            ? explode(",", $video->getVideoFor()) : null,
                        'products' => $products
                    ];
                }
                $result[] = [
                    'title' => $slider->getTitle(),
                    'sub_title' => $slider->getSubTitle(),
                    'description' => $slider->getDescription(),
                    'priority' => $slider->getPriority(),
                    'start_date' => $slider->getStartDate()
                        ? $this->baseHelper->getDateTimeBasedOnTimezone($slider->getStartDate()) : "",
                    'end_date' => $slider->getEndDate()
                        ? $this->baseHelper->getDateTimeBasedOnTimezone($slider->getEndDate()) : "",
                    'videos' => $videos
                ];
            }
        } catch (NoSuchEntityException|LocalizedException $exception) {
            $this->logger->error($exception->getMessage() . __METHOD__);
        }
        return $result;
    }
}
