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

use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\Phrase;
use Magento\Quote\Api\Data\ShippingAssignmentInterface;
use Magento\Quote\Model\Quote;
use Magento\Quote\Model\Quote\Address\Total;
use Magento\Quote\Model\Quote\Address\Total\AbstractTotal;

/**
 * Grand Total Without Prepaid Total Segment
 */
class GrandTotalWithoutPrepaid extends AbstractTotal
{
    /**
     * Grand Total Without Prepaid Constructor
     *
     * @param ScopeConfigInterface $scopeConfig
     */
    public function __construct(
        private ScopeConfigInterface $scopeConfig,
    ) {
        $this->setCode('grand_total_without_prepaid');
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

        $amount = $this->getBaseAmount($total);

        $total->setGrandTotalWithoutPrepaid($amount);
        $total->setBaseGrandTotalWithoutPrepaid($amount);
        $quote->setGrandTotalWithoutPrepaid($amount);
        $quote->setBaseGrandTotalWithoutPrepaid($amount);

        return $this;
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
        return ($totals['subtotal'] + $totals['discount'] + $totals['delivery_charges']);
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
            'value' => $total->getGrandTotalWithoutPrepaid()
        ];
    }

    /**
     * Get Label
     *
     * @return Phrase
     */
    public function getLabel(): Phrase
    {
        return __('Total');
    }
}
