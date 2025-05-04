<?php
/**
 * Pratech_CustomDeliveryCharges
 *
 * PHP version 8.x
 *
 * @category  PHP
 * @package   Pratech\CustomDeliveryCharges
 * @author    Puneet Mittal <puneet.mittal@pratechbrands.com>
 * @copyright 2023 Copyright (c) Pratech Brands Private Limited
 * @link      https://pratechbrands.com/
 **/

namespace Pratech\CustomDeliveryCharges\Model\Total;

use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\Phrase;
use Magento\Quote\Api\Data\ShippingAssignmentInterface;
use Magento\Quote\Model\Quote;
use Magento\Quote\Model\Quote\Address\Total;
use Magento\Quote\Model\Quote\Address\Total\AbstractTotal;
use Pratech\CustomDeliveryCharges\Helper\Data;

/**
 * Delivery Charges Total Segment to include delivery charges for customer with lower order value.
 */
class DeliveryCharges extends AbstractTotal
{
    /**
     * DeliveryCharges Constructor
     *
     * @param Data $deliveryChargesHelper
     * @param ScopeConfigInterface $scopeConfig
     */
    public function __construct(
        protected Data                 $deliveryChargesHelper,
        protected ScopeConfigInterface $scopeConfig
    ) {
        $this->setCode('delivery_charges');
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

        $minimumOrderValue = $this->deliveryChargesHelper->getMinimumOrderValue();

        if ($this->getBaseTotal($total) < $minimumOrderValue &&
            !$this->getIsFreeDelivery()) {
            $amount = $this->deliveryChargesHelper->getDeliveryChargesAmount();
        } else {
            $amount = 0;
        }

        $total->setTotalAmount('delivery_charges', $amount);
        $total->setBaseTotalAmount('base_delivery_charges', $amount);
        $total->setDeliveryCharges($amount);
        $total->setBaseDeliveryCharges($amount);
        $quote->setDeliveryCharges($amount);
        $quote->setBaseDeliveryCharges($amount);

        return $this;
    }

    /**
     * Get Base Total.
     *
     * @param Total $total
     * @return mixed
     */
    private function getBaseTotal(Total $total): mixed
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
//        $minOrderValue = $this->deliveryChargesHelper->getMinimumOrderValue();
//        $value = ($this->getBaseTotal($total) < $minOrderValue && !$this->getIsFreeDelivery())
//            ? $this->deliveryChargesHelper->getDeliveryChargesAmount()
//            : 0;

        return [
            'code' => $this->getCode(),
            'title' => $this->getLabel(),
            'value' => $total->getDeliveryCharges()
        ];
    }

    /**
     * Validate if free delivery is applicable for the cart or not.
     *
     * @return int
     */
    public function getIsFreeDelivery(): int
    {
        return $this->deliveryChargesHelper->getIsFreeDelivery();
    }

    /**
     * Get Label
     *
     * @return Phrase
     */
    public function getLabel(): Phrase
    {
        return __($this->deliveryChargesHelper->getDeliveryChargesLabel());
    }

    /**
     * Clear Totals Value
     *
     * @param Total $total
     */
    protected function clearValues(Total $total)
    {
        $total->setTotalAmount('subtotal', 0);
        $total->setBaseTotalAmount('subtotal', 0);
        $total->setTotalAmount('tax', 0);
        $total->setBaseTotalAmount('tax', 0);
        $total->setTotalAmount('discount_tax_compensation', 0);
        $total->setBaseTotalAmount('discount_tax_compensation', 0);
        $total->setTotalAmount('shipping_discount_tax_compensation', 0);
        $total->setBaseTotalAmount('shipping_discount_tax_compensation', 0);
        $total->setSubtotalInclTax(0);
        $total->setBaseSubtotalInclTax(0);
    }
}
