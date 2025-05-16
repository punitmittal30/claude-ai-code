<?php
/**
 * Pratech_Return
 *
 * PHP version 8.x
 *
 * @category  PHP
 * @package   Pratech\Return
 * @author    Vivek Kumar <vivek.kumar@pratechbrands.com>
 * @copyright 2024 Copyright (c) Pratech Brands Private Limited
 * @link      https://pratechbrands.com/
 **/

namespace Pratech\Return\Model\Order;

use Exception;
use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Catalog\Helper\Image;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Sales\Api\OrderItemRepositoryInterface;
use Pratech\Base\Logger\Logger;

class OrderItemImage
{
    /**
     * @param OrderItemRepositoryInterface $orderItemRepository
     * @param ProductRepositoryInterface $productRepository
     * @param Image $imageHelper
     * @param Logger $baseLogger
     */
    public function __construct(
        private OrderItemRepositoryInterface $orderItemRepository,
        private ProductRepositoryInterface   $productRepository,
        private Image                        $imageHelper,
        private Logger                       $baseLogger
    ) {
    }

    /**
     * @param int $orderItemId
     * @param string $imageId
     *
     * @return string
     */
    public function getUrl(int $orderItemId, $imageId = 'product_thumbnail_image'): string
    {
        if (!$orderItemId) {
            return '';
        }

        try {
            $orderItem = $this->orderItemRepository->get($orderItemId);
        } catch (Exception $e) {
            return $this->imageHelper->getDefaultPlaceholderUrl('small_image');
        }

        try {
            $product = $this->productRepository->getById($orderItem->getProductId());
            if ($product->getMediaGalleryEntries()) {
                return $this->imageHelper->init($product, $imageId)->getUrl();
            }
        } catch (NoSuchEntityException $e) {
            $this->baseLogger->error($e->getMessage() . __METHOD__);
        }

        if (!empty($orderItem->getParentItemId())) {
            try {
                $orderItem = $this->orderItemRepository->get($orderItem->getParentItemId());
            } catch (Exception $e) {
                return $this->imageHelper->getDefaultPlaceholderUrl('small_image');
            }

            try {
                $product = $this->productRepository->getById($orderItem->getProductId());
                if ($product->getMediaGalleryEntries()) {
                    return $this->imageHelper->init($product, $imageId)->getUrl();
                }
            } catch (NoSuchEntityException $e) {
                $this->baseLogger->error($e->getMessage() . __METHOD__);
            }
        }

        return $this->imageHelper->getDefaultPlaceholderUrl('small_image');
    }
}
