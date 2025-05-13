<?php
/**
 * Pratech_CmsBlock
 *
 * PHP version 8.x
 *
 * @category  PHP
 * @package   Pratech\CmsBlock
 * @author    Puneet Mittal <puneet.mittal@pratechbrands.com>
 * @copyright 2022 Copyright (c) Pratech Brands Private Limited
 * @link      https://pratechbrands.com/
 **/

namespace Pratech\CmsBlock\Plugin\Model\Page;

use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\UrlInterface;
use Magento\Store\Model\StoreManagerInterface;
use Pratech\Base\Logger\Logger;

/**
 * Data Provider Class to load image data.
 */
class DataProvider
{
    /**
     * Constant for image location.
     */
    public const IMAGE_LOCATION = 'cms/image';

    /**
     * @param StoreManagerInterface $storeManager
     * @param Logger                $apiLogger
     */
    public function __construct(
        private StoreManagerInterface $storeManager,
        private Logger                $apiLogger
    ) {
    }

    /**
     * After Get Data.
     *
     * @param  \Magento\Cms\Model\Page\DataProvider $subject
     * @param  array                                $result
     * @return array
     */
    public function afterGetData(
        \Magento\Cms\Model\Page\DataProvider $subject,
        array                                $result
    ): array {
        try {
            $mediaUrl = $this->storeManager->getStore()->getBaseUrl(UrlInterface::URL_TYPE_MEDIA);
            foreach ($result as $key => $page) {
                if (isset($page['featured_image'])) {
                    $imgArr = [];
                    $name = $page['featured_image'];
                    $imgArr[0]['name'] = $name;
                    $imgArr[0]['url'] = $mediaUrl . self::IMAGE_LOCATION . '/' . $name;
                    $imgArr[0]['type'] = 'image';

                    unset($result[$key]['featured_image']);
                    $result[$key]['featured_image'] = $imgArr;
                }
                if (isset($page['thumbnail_image'])) {
                    $imgArr = [];
                    $name = $page['thumbnail_image'];
                    $imgArr[0]['name'] = $name;
                    $imgArr[0]['url'] = $mediaUrl . self::IMAGE_LOCATION . '/' . $name;
                    $imgArr[0]['type'] = 'image';

                    unset($result[$key]['thumbnail_image']);
                    $result[$key]['thumbnail_image'] = $imgArr;
                }
            }
        } catch (NoSuchEntityException $e) {
            $this->apiLogger->critical($e->getMessage() . __METHOD__);
        }
        return $result;
    }
}
