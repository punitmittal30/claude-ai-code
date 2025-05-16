<?php
/**
 * Pratech_DiscountReport
 *
 * PHP version 8.x
 *
 * @category  PHP
 * @package   Pratech\DiscountReport
 * @author    Akash Panwar <akash.panwar@pratechbrands.com>
 * @copyright 2024 Copyright (c) Pratech Brands Private Limited
 * @link      https://pratechbrands.com/
 **/
declare(strict_types=1);

namespace Pratech\DiscountReport\Observer\Salesrule;

use Exception;
use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Pratech\Base\Logger\Logger;
use Pratech\DiscountReport\Model\LogFactory as DiscountLogFactory;
use Pratech\DiscountReport\Model\ResourceModel\Log\CollectionFactory as DiscountLogCollectionFactory;

class DiscountProcess implements ObserverInterface
{
    /**
     * @param Logger $apiLogger
     * @param DiscountLogFactory $discountLogFactory
     * @param DiscountLogCollectionFactory $discountLogCollectionFactory
     */
    public function __construct(
        private Logger                       $apiLogger,
        private DiscountLogFactory           $discountLogFactory,
        private DiscountLogCollectionFactory $discountLogCollectionFactory
    ) {
    }

    /**
     * Execute observer
     *
     * @param Observer $observer
     * @return void
     */
    public function execute(
        Observer $observer
    ) {
        try {
            $eventData = $observer->getEvent()->getData();

            $quote = $eventData['quote'];
            $item = $eventData['item'];
            $quoteId = $quote->getId();
            $itemSku = $item->getSku();
            $discountType = $discountKey = '';
            if (isset($eventData['discount_type'])) {
                $discountType = $eventData['discount_type'];
                $discountKey = $eventData['discount_type'];
                $discountAmount = $eventData['discount_amount'];
            } else {
                $rule = $eventData['rule'];
                $discountInfo = $eventData['result'];
                $ruleId = $rule->getId();
                $ruleName = $rule->getName();
                $couponCode = $rule->getCouponCode() ?: $rule->getCode();
                $discountType = empty($couponCode) ? 'rule' : 'coupon';
                $discountKey = $ruleId;
                $discountAmount = $discountInfo->getBaseAmount();
            }
            $discountAmount = abs($discountAmount);
            if (empty((float)$discountAmount) && $discountType == 'customerbalance') {
                return true;
            }

            $discountData = [
                'amount' => $discountAmount
            ];
            if (!empty($couponCode)) {
                $discountData['coupon_code'] = $couponCode;
            }

            $discountLogCollection = $this->discountLogCollectionFactory->create()
                ->addFieldToFilter('quote_id', ['eq' => $quoteId])
                ->addFieldToFilter('item_sku', ['eq' => $itemSku]);

            if ($discountLogCollection->getSize()) {
                $discountLog = $discountLogCollection->getFirstItem();
                $discountDataArray = $discountLog->getDiscountData()
                    ? json_decode($discountLog->getDiscountData(), true)
                    : [];

                $discountDataArray[$discountType][$discountKey] = $discountData;
                $discountDataToSave = json_encode($discountDataArray);
                $discountLog->setDiscountData($discountDataToSave)->save();
            } else {
                $discountDataArray = [];
                $discountDataArray[$discountType][$discountKey] = $discountData;
                $discountDataToSave = json_encode($discountDataArray);
                $this->discountLogFactory->create()
                    ->setQuoteId($quoteId)
                    ->setItemSku($itemSku)
                    ->setDiscountData($discountDataToSave)
                    ->save();
            }
        } catch (Exception $e) {
            $this->apiLogger->error("Unable to save discount report data for Quote ID : " . $quoteId
                . " | Item ID : " . $itemSku
                . " | Discount Type : " . $discountType
                . " | Discount Key : " . $discountKey
                . ". Exception : " . $e->getMessage());
        }
    }
}
