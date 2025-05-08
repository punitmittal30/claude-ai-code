<?php
/**
 * Pratech_PrepaidDiscount
 *
 * PHP version 8.x
 *
 * @category  PHP
 * @package   Pratech\PrepaidDiscount
 * @author    Puneet Mittal <puneet.mittal@pratechbrands.com>
 * @copyright 2023 Copyright (c) Pratech Brands Private Limited
 * @link      https://pratechbrands.com/
 **/

namespace Pratech\PrepaidDiscount\Model\Total;

use Exception;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\Event\ManagerInterface;
use Magento\Framework\Phrase;
use Magento\Quote\Api\Data\ShippingAssignmentInterface;
use Magento\Quote\Model\Quote;
use Magento\Quote\Model\Quote\Address\Total;
use Magento\Quote\Model\Quote\Address\Total\AbstractTotal;
use Magento\Store\Model\ScopeInterface;
use Pratech\Base\Logger\Logger;

/**
 * Prepaid Discount Total Segment
 */
class PrepaidDiscount extends AbstractTotal
{
    /**
     * Constant for online payment methods.
     */
    public const PREPAID_PAYMENT_METHODS = [
        'upi',
        'netbanking',
        'card',
        'wallet',
        'online_payment_app',
    ];

    /**
     * Prepaid Discount Constructor
     *
     * @param ScopeConfigInterface $scopeConfig
     * @param ManagerInterface $eventManager
     * @param Logger $apiLogger
     */
    public function __construct(
        private ScopeConfigInterface $scopeConfig,
        private ManagerInterface     $eventManager,
        private Logger               $apiLogger
    ) {
        $this->setCode('prepaid_discount');
    }

    /**
     * Collect Totals
     *
     * @param Quote $quote
     * @param ShippingAssignmentInterface $shippingAssignment
     * @param Total $total
     * @return $this
     */
    public function collect(
        Quote                       $quote,
        ShippingAssignmentInterface $shippingAssignment,
        Total                       $total
    ) {
        parent::collect($quote, $shippingAssignment, $total);
        $items = $shippingAssignment->getItems();
        if (!count($items)) {
            return $this;
        }

        $amount = 0;

        if (in_array($quote->getPayment()->getMethod(), self::PREPAID_PAYMENT_METHODS)) {
            $amount = $this->getDiscount($total);
        }

        $total->setTotalAmount('prepaid_discount', $amount);
        $total->setBaseTotalAmount('base_prepaid_discount', $amount);
        $total->setPrepaidDiscount($amount);
        $total->setBasePrepaidDiscount($amount);
        $quote->setPrepaidDiscount($amount);
        $quote->setBasePrepaidDiscount($amount);

        $itemCount = count($quote->getAllVisibleItems());
        if ($itemCount) {
            $totalAmount = $this->getBaseAmount($total);
            foreach ($quote->getAllVisibleItems() as $item) {
                if ($totalAmount > 0) {
                    $discountPerItem = $amount * (($item->getRowTotal() - $item->getDiscountAmount()) / $totalAmount);

                    // Set the discount amount for each item
                    $item->setDiscountAmount($item->getDiscountAmount() - $discountPerItem);
                    $item->setBaseDiscountAmount($item->getBaseDiscountAmount() - $discountPerItem);
                    try {
                        $item->save();

                        // Add the discount data in discount report logs
                        $this->eventManager->dispatch(
                            'pratech_discountreport_process',
                            [
                                'item' => $item,
                                'quote' => $quote,
                                'discount_type' => 'prepaid_discount',
                                'discount_amount' => $discountPerItem
                            ]
                        );
                    } catch (Exception $e) {
                        $this->apiLogger->error("Unable to save discount amount for Quote ID : "
                            . $item->getQuoteId() . " | Item ID : " . $item->getItemId() . " | " . $e->getMessage());
                    }
                }
            }
        }

        return $this;
    }

    /**
     * Get Discount.
     *
     * @param Total $total
     * @return float
     */
    public function getDiscount(Total $total): float
    {
        $types = $this->scopeConfig->getValue('prepaid_discount/general/ranges', ScopeInterface::SCOPE_STORE);
        if ($types) {
            $items = json_decode($types, true);
            foreach ($items as $item) {
                if ($this->getBaseAmount($total) <= $item['to_price']
                    && $this->getBaseAmount($total) >= $item['from_price']) {
                    if ($item['discount_type'] == 'percent') {
                        return -($this->getBaseAmount($total) * $item['discount'] / 100);
                    } else {
                        return -$item['discount'];
                    }
                }
            }
        }
        return 0;
    }

    /**
     * Get Base Amount.
     *
     * @param Total $total
     * @return mixed
     */
    private function getBaseAmount(Total $total): mixed
    {
        $totals = $total->getAllTotalAmounts();
        return ($totals['subtotal'] + $totals['discount']);
    }

    /**
     * Fetch Totals Segment
     *
     * @param Quote $quote
     * @param Total $total
     * @return array
     */
    public function fetch(Quote $quote, Total $total): array
    {
        return [
            'code' => $this->getCode(),
            'title' => $this->getLabel(),
            'value' => $total->getPrepaidDiscount()
        ];
    }

    /**
     * Get Label
     *
     * @return Phrase
     */
    public function getLabel(): Phrase
    {
        return __('Prepaid Discount');
    }
}
