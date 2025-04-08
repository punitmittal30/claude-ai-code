<?php
/**
 * Hyuga_WondersoftIntegration
 *
 * PHP version 8.x
 *
 * @category  PHP
 * @package   Hyuga\WondersoftIntegration
 * @author    Puneet Mittal <puneet.mittal@pratechbrands.com>
 * @copyright 2025 Copyright (c) Pratech Brands Private Limited
 * @link      https://pratechbrands.com/
 **/

namespace Hyuga\WondersoftIntegration\Observer;

use Exception;
use Hyuga\WondersoftIntegration\Api\WondersoftApiInterface;
use Hyuga\WondersoftIntegration\Helper\Data as Helper;
use Hyuga\WondersoftIntegration\Logger\Logger;
use Magento\Catalog\Model\Product;
use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;

class ProductSaveAfter implements ObserverInterface
{
    /**
     * Constructor
     *
     * @param WondersoftApiInterface $wondersoftApi
     * @param Logger $logger
     * @param Helper $helper
     */
    public function __construct(
        private WondersoftApiInterface $wondersoftApi,
        private Logger                 $logger,
        private Helper                 $helper
    ) {
    }

    /**
     * Execute observer
     *
     * @param Observer $observer
     * @return void
     */
    public function execute(Observer $observer)
    {
        /** @var Product $product */
        $product = $observer->getEvent()->getProduct();
        $origData = $product->getOrigData();

        // Process product push if enabled
        if ($this->helper->isProductPushEnabled()) {
            // Check if this is a new product
            $isNewProduct = $product->isObjectNew();

            if ($isNewProduct) {
                $this->logger->info('New product detected: ' . $product->getSku());

                try {
                    $result = $this->wondersoftApi->pushProduct($product);
                    if (!$result) {
                        $this->logger->error('Failed to push new product to Wondersoft: ' . $product->getSku());
                    } else {
                        $this->logger->info('Successfully pushed new product to Wondersoft: ' . $product->getSku());
                    }
                } catch (Exception $e) {
                    $this->logger->critical('Exception when pushing new product: ' . $e->getMessage());
                }
            }
        }

        // Process price push if enabled
        if ($this->helper->isPricePushEnabled()) {
            $shouldPushPrice = false;

            // Check for regular price changes
            if ($origData && isset($origData['price'])) {
                if ($product->getPrice() != $origData['price']) {
                    $shouldPushPrice = true;
                    $this->logger->info('Price change detected for product: ' . $product->getSku());
                }
            } elseif ($product->getPrice() !== null) {
                $shouldPushPrice = true;
            }

            // Check for special price changes
            if ($origData && isset($origData['special_price'])) {
                if ($product->getSpecialPrice() != $origData['special_price']) {
                    $shouldPushPrice = true;
                    $this->logger->info('Special price change detected for product: ' . $product->getSku());
                }
            } elseif ($product->getSpecialPrice() !== null) {
                $shouldPushPrice = true;
            }

            // Check for special_from_date changes
            if ($origData && isset($origData['special_from_date'])) {
                if ($product->getSpecialFromDate() != $origData['special_from_date']) {
                    $shouldPushPrice = true;
                    $this->logger->info('Special from date change detected for product: ' . $product->getSku());
                }
            } elseif ($product->getSpecialFromDate() !== null) {
                $shouldPushPrice = true;
            }

            // Check for special_to_date changes
            if ($origData && isset($origData['special_to_date'])) {
                if ($product->getSpecialToDate() != $origData['special_to_date']) {
                    $shouldPushPrice = true;
                    $this->logger->info('Special to date change detected for product: ' . $product->getSku());
                }
            } elseif ($product->getSpecialToDate() !== null) {
                $shouldPushPrice = true;
            }

            // Push price list if any price-related attributes have changed
            if ($shouldPushPrice) {
                try {
                    $result = $this->wondersoftApi->pushPriceList($product);
                    if (!$result) {
                        $this->logger->error('Failed to push price update to Wondersoft: ' . $product->getSku());
                    } else {
                        $this->logger->info('Successfully pushed price update to Wondersoft: ' . $product->getSku());
                    }
                } catch (Exception $e) {
                    $this->logger->critical('Exception when pushing price update: ' . $e->getMessage());
                }
            }
        }
    }
}
