<?php
/**
 * Pratech_Coupon
 *
 * PHP version 8.x
 *
 * @category  PHP
 * @package   Pratech\Coupon
 * @author    Puneet Mittal <puneet.mittal@pratechbrands.com>
 * @copyright 2024 Copyright (c) Pratech Brands Private Limited
 * @link      https://pratechbrands.com/
 **/

namespace Pratech\Coupon\Plugin\Model;

use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\Stdlib\DateTime\TimezoneInterface;
use Magento\SalesRule\Model\ResourceModel\Rule\CollectionFactory;

class Config extends \Amasty\Coupons\Model\Config
{
    /**
     * @param ScopeConfigInterface $scopeConfig
     * @param TimezoneInterface $timezoneInterface
     * @param CollectionFactory $salesRuleCollectionFactory
     */
    public function __construct(
        ScopeConfigInterface      $scopeConfig,
        private TimezoneInterface $timezoneInterface,
        private CollectionFactory $salesRuleCollectionFactory
    ) {
        parent::__construct($scopeConfig);
    }

    /**
     * Get Unique Coupons
     *
     * @return string
     */
    public function getUniqueCoupons(): string
    {
        $couponCodes = [];
        $salesRules = $this->salesRuleCollectionFactory->create()
            ->addFieldToFilter('is_active', ['eq' => 1])
            ->addFieldToFilter('is_stackable', ['eq' => 0])
            ->addFieldToFilter('coupon_type', ['in' => [1, 2]])
            ->addFieldToFilter('use_auto_generation', ['eq' => 0])
            ->addFieldToFilter(
                'to_date',
                [
                    ['gteq' => $this->getCurrentDate()],
                    ['null' => true]
                ]
            )->setOrder('sort_order', 'asc');
        foreach ($salesRules->getData() as $salesRule) {
            if ($salesRule['code'] != null) {
                $couponCodes[] = $salesRule['code'];
            }
        }
        return empty($couponCodes) ? '' : implode(',', $couponCodes);
    }

    /**
     * Get Current Date
     *
     * @return string
     */
    public function getCurrentDate(): string
    {
        return $this->timezoneInterface->date()->format('Y-m-d');
    }
}
