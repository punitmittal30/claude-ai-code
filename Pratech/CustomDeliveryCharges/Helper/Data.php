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

namespace Pratech\CustomDeliveryCharges\Helper;

use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\Stdlib\DateTime\TimezoneInterface;
use Magento\Store\Model\ScopeInterface;

/**
 * Delivery Charges Helper Class.
 */
class Data
{
    /**
     * Constant for module enabled.
     */
    public const IS_MODULE_ENABLED = 'delivery/delivery_charges/status';

    /**
     * Constant for minimum order value.
     */
    public const MINIMUM_ORDER_VALUE = 'delivery/delivery_charges/minimum_order_value';

    /**
     * Constant for delivery charges.
     */
    public const DELIVERY_CHARGES = 'delivery/delivery_charges/amount';

    /**
     * Constant for delivery charges label.
     */
    public const DELIVERY_CHARGES_LABEL = 'delivery/delivery_charges/label';

    /**
     * Free Shipping Day Constant
     */
    public const FREE_SHIPPING_DAY = 'delivery/delivery_charges/free_shipping_day';

    /**
     * @param ScopeConfigInterface $scopeConfig
     * @param TimezoneInterface $timezoneInterface
     */
    public function __construct(
        protected ScopeConfigInterface $scopeConfig,
        protected TimezoneInterface    $timezoneInterface
    ) {
    }

    /**
     * Is Module Enabled
     *
     * @return mixed
     */
    public function isModuleEnabled()
    {
        return $this->scopeConfig->getValue(
            self::IS_MODULE_ENABLED,
            ScopeInterface::SCOPE_STORE
        );
    }

    /**
     * Get Minimum Order Value
     *
     * @return mixed
     */
    public function getMinimumOrderValue()
    {
        return $this->scopeConfig->getValue(
            self::MINIMUM_ORDER_VALUE,
            ScopeInterface::SCOPE_STORE
        );
    }

    /**
     * Get Delivery Charges
     *
     * @return mixed
     */
    public function getDeliveryChargesAmount()
    {
        return $this->scopeConfig->getValue(
            self::DELIVERY_CHARGES,
            ScopeInterface::SCOPE_STORE
        );
    }

    /**
     * Get Delivery Charges Label
     *
     * @return mixed
     */
    public function getDeliveryChargesLabel()
    {
        return $this->scopeConfig->getValue(
            self::DELIVERY_CHARGES_LABEL,
            ScopeInterface::SCOPE_STORE
        );
    }

    /**
     * Get Free Shipping Days
     *
     * @return mixed
     */
    public function getFreeShippingDays(): mixed
    {
        return $this->scopeConfig->getValue(
            self::FREE_SHIPPING_DAY,
            ScopeInterface::SCOPE_STORE
        );
    }

    /**
     * Validate if free delivery is applicable for the cart or not.
     *
     * @return int
     */
    public function getIsFreeDelivery(): int
    {
        $isFreeDelivery = 0;
        $freeDeliveryDay = $this->getFreeShippingDays();
        if (isset($freeDeliveryDay)) {
            $days = explode(',', $freeDeliveryDay);
            $todayInNumeric = $this->timezoneInterface->date()->format('w');
            foreach ($days as $day) {
                if ($day != null && $day == $todayInNumeric) {
                    $isFreeDelivery = 1;
                }
            }
        }
        return $isFreeDelivery;
    }
}
