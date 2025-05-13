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

use DateTime;
use Exception;
use Hyuga\WondersoftIntegration\Api\WondersoftApiInterface;
use Hyuga\WondersoftIntegration\Helper\Data as Helper;
use Hyuga\WondersoftIntegration\Logger\Logger;
use Magento\Catalog\Model\Product;
use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Magento\Framework\Stdlib\DateTime\TimezoneInterface;

class ProductSaveAfter implements ObserverInterface
{
    /**
     * Constructor
     *
     * @param WondersoftApiInterface $wondersoftApi
     * @param Logger $logger
     * @param Helper $helper
     * @param TimezoneInterface $timezone
     */
    public function __construct(
        private WondersoftApiInterface $wondersoftApi,
        private Logger                 $logger,
        private Helper                 $helper,
        private TimezoneInterface      $timezone
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
                    if (!$product->getCustomAttribute('ean_code')) {
                        $this->logger->error(
                            'EAN Missing | Failed to push new product to Wondersoft: ' . $product->getSku()
                        );
                    } else {
                        $result = $this->wondersoftApi->pushProduct($product);
                        if (!$result) {
                            $this->logger->error(
                                'Failed to push new product to Wondersoft: ' . $product->getSku()
                            );
                        } else {
                            $this->logger->info(
                                'Successfully pushed new product to Wondersoft: ' . $product->getSku()
                            );
                        }
                    }
                } catch (Exception $e) {
                    $this->logger->critical('Exception when pushing new product: ' . $e->getMessage());
                }
            }
        }

        // Process price changes
        $shouldSendPriceRevision = false;
        $shouldPushPrice = false;

        // Check for regular price changes
        if ($origData && isset($origData['price'])) {
            if ($product->getPrice() != $origData['price']) {
                $shouldSendPriceRevision = true;
                $shouldPushPrice = true;
                $this->logger->info('Price change detected for product: ' . $product->getSku());
            }
        } elseif ($product->getPrice() !== null) {
            $shouldSendPriceRevision = true;
            $shouldPushPrice = true;
        }

        // Check for special price changes
        if ($origData && isset($origData['special_price'])) {
            if ($product->getSpecialPrice() != $origData['special_price']) {
                $shouldSendPriceRevision = true;
                $shouldPushPrice = true;
                $this->logger->info('Special price change detected for product: ' . $product->getSku());
            }
        } elseif ($product->getSpecialPrice() !== null) {
            $shouldSendPriceRevision = true;
            $shouldPushPrice = true;
        }

        // Check for special_from_date changes
        if ($origData && isset($origData['special_from_date'])) {
            if ($product->getSpecialFromDate() != $origData['special_from_date']) {
                $shouldSendPriceRevision = true;
                $shouldPushPrice = true;
                $this->logger->info(
                    'Special from date change detected for product: ' . $product->getSku()
                );
            }
        } elseif ($product->getSpecialFromDate() !== null) {
            $shouldSendPriceRevision = true;
            $shouldPushPrice = true;
        }

        // Check for special_to_date changes
        if ($origData && isset($origData['special_to_date'])) {
            if ($product->getSpecialToDate() != $origData['special_to_date']) {
                $shouldSendPriceRevision = true;
                $shouldPushPrice = true;
                $this->logger->info(
                    'Special to date change detected for product: ' . $product->getSku()
                );
            }
        } elseif ($product->getSpecialToDate() !== null) {
            $shouldSendPriceRevision = true;
            $shouldPushPrice = true;
        }

        // Push price list if needed
        if ($shouldPushPrice && $this->helper->isPricePushEnabled()) {
            if (!$product->getCustomAttribute('ean_code')) {
                $this->logger->error(
                    'EAN Missing | Failed to push price update to Wondersoft: ' . $product->getSku()
                );
            } else {
                try {
                    $result = $this->wondersoftApi->pushPriceList($product);
                    if (!$result) {
                        $this->logger->error(
                            'Failed to push price update to Wondersoft: ' . $product->getSku()
                        );
                    } else {
                        $this->logger->info(
                            'Successfully pushed price update to Wondersoft: ' . $product->getSku()
                        );
                    }
                } catch (Exception $e) {
                    $this->logger->critical('Exception when pushing price update: ' . $e->getMessage());
                }
            }
        }

        // Send price revision if needed and enabled
        if ($shouldSendPriceRevision && $this->helper->isPriceRevisionPushEnabled()) {
            if (!$product->getCustomAttribute('ean_code')) {
                $this->logger->error(
                    'EAN Missing | Failed to push price revision to Wondersoft: ' . $product->getSku()
                );
            } else {
                try {
                    // Determine price to use
                    $price = $product->getPrice();
                    if ($product->getSpecialPrice() &&
                        (!$product->getSpecialFromDate() || strtotime($product->getSpecialFromDate()) <= time()) &&
                        (!$product->getSpecialToDate() || strtotime($product->getSpecialToDate() . ' 23:59:59') >= time())) {
                        $price = $product->getSpecialPrice();
                    }

                    // Prepare product data for revision
                    $productData = [
                        [
                            'sku' => $product->getSku(),
                            'price' => $price,
                            'cost' => $product->getFloorPrice() ?? 0,
                            'mrp' => $product->getPrice(),
                            'msp' => '',
                            'from_mrp' => '',
                            'quality_type' => 0,
                            'alpha_batch_id' => '',
                            'lot_number' => '',
                            'uom_code' => '',
                            'barcode' => ''
                        ]
                    ];

                    // Generate revision ID
                    $revisionId = $this->helper->generatePriceRevisionId();

                    // Set effective date to tomorrow
                    $effectiveDate = $this->getDateTimeBasedOnTimezone();

                    // Send the price revision
                    $result = $this->wondersoftApi->pushPriceRevision($productData, $revisionId, $effectiveDate);

                    if (!$result) {
                        $this->logger->error(
                            'Failed to push price revision to Wondersoft: ' . $product->getSku() .
                            ' (Revision ID: ' . $revisionId . ')'
                        );
                    } else {
                        $this->logger->info(
                            'Successfully pushed price revision to Wondersoft: ' . $product->getSku() .
                            ' (Revision ID: ' . $revisionId . ')'
                        );
                    }
                } catch (Exception $e) {
                    $this->logger->critical('Exception when pushing price revision: ' . $e->getMessage());
                }
            }
        }
    }

    /**
     * Get Time Based On Timezone for Email
     *
     * @param string $format
     * @return string
     */
    public function getDateTimeBasedOnTimezone(string $format = 'Y-m-d'): string
    {
        try {
            $locale = $this->helper->getLocale();
            return $this->timezone->date(new DateTime(), $locale)->format($format);
        } catch (Exception $e) {
            $this->logger->error($e->getMessage() . __METHOD__);
            return "";
        }
    }
}
