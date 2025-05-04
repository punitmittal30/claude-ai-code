<?php
/**
 * Pratech_Catalog
 *
 * PHP version 8.x
 *
 * @category  PHP
 * @package   Pratech\Catalog
 * @author    Vivek Kumar <vivek.kumar@pratechbrands.com>
 * @copyright 2023 Copyright (c) Pratech Brands Private Limited
 * @link      https://pratechbrands.com/
 **/
namespace Pratech\Catalog\Plugin\Model\Product\Type;

use Magento\Catalog\Api\Data\ProductTierPriceExtensionFactory;
use Magento\Customer\Api\GroupManagementInterface;
use Magento\Framework\Pricing\PriceCurrencyInterface;
use Magento\Framework\Stdlib\DateTime;
use Magento\Framework\Stdlib\DateTime\TimezoneInterface;
use Pratech\Base\Helper\Data as BaseHelper;

class Price extends \Magento\Catalog\Model\Product\Type\Price
{
    /**
     * Catalog Price Constructor
     *
     * @param \Magento\CatalogRule\Model\ResourceModel\RuleFactory       $ruleFactory
     * @param \Magento\Store\Model\StoreManagerInterface                 $storeManager
     * @param \Magento\Customer\Model\Session                            $customerSession
     * @param \Magento\Framework\Event\ManagerInterface                  $eventManager
     * @param PriceCurrencyInterface                                     $priceCurrency
     * @param GroupManagementInterface                                   $groupManagement
     * @param \Magento\Catalog\Api\Data\ProductTierPriceInterfaceFactory $tierPriceFactory
     * @param \Magento\Framework\App\Config\ScopeConfigInterface         $config
     * @param ProductTierPriceExtensionFactory                           $tierPriceExtensionFactory
     * @param TimezoneInterface                                          $localeDate
     * @param DateTime                                                   $dateTime
     * @param BaseHelper                                                 $baseHelper
     */
    public function __construct(
        \Magento\CatalogRule\Model\ResourceModel\RuleFactory $ruleFactory,
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        \Magento\Customer\Model\Session $customerSession,
        \Magento\Framework\Event\ManagerInterface $eventManager,
        PriceCurrencyInterface $priceCurrency,
        GroupManagementInterface $groupManagement,
        \Magento\Catalog\Api\Data\ProductTierPriceInterfaceFactory $tierPriceFactory,
        \Magento\Framework\App\Config\ScopeConfigInterface $config,
        ProductTierPriceExtensionFactory $tierPriceExtensionFactory,
        protected TimezoneInterface $localeDate,
        protected DateTime $dateTime,
        protected BaseHelper $baseHelper
    ) {
        parent::__construct(
            $ruleFactory,
            $storeManager,
            $localeDate,
            $customerSession,
            $eventManager,
            $priceCurrency,
            $groupManagement,
            $tierPriceFactory,
            $config,
            $tierPriceExtensionFactory
        );
    }

    /**
     * Calculate and apply special price
     *
     * @param  float            $finalPrice
     * @param  float            $specialPrice
     * @param  string           $specialPriceFrom
     * @param  string           $specialPriceTo
     * @param  int|string|Store $store
     * @return float
     */
    public function calculateSpecialPrice(
        $finalPrice,
        $specialPrice,
        $specialPriceFrom,
        $specialPriceTo,
        $store = null
    ) {
        if ($specialPrice !== null && $specialPrice != false) {
            $specialPriceFrom = $specialPriceFrom
                ? $this->baseHelper->getDateTimeBasedOnTimezone($specialPriceFrom)
                : '';
            $specialPriceTo = $specialPriceTo ? $this->baseHelper->getDateTimeBasedOnTimezone($specialPriceTo) : '';
            if ($this->isDateInInterval($specialPriceFrom, $specialPriceTo)) {
                $finalPrice = min($finalPrice, (float) $specialPrice);
            }
        }
        return $finalPrice;
    }

    /**
     * Is Date In Interval
     *
     * @param  string $dateFrom
     * @param  string $dateTo
     * @return boolean
     */
    public function isDateInInterval($dateFrom = null, $dateTo = null): bool
    {
        $dateFrom = $dateFrom ?? '';
        $dateTo = $dateTo ?? '';
        $today = $this->localeDate->date()->format('Y-m-d H:i:s');

        $todayTimeStamp = strtotime($today);
        $fromTimeStamp = strtotime($dateFrom);
        $toTimeStamp = strtotime($dateTo);

        return !(!$this->dateTime->isEmptyDate($dateFrom) && $todayTimeStamp < $fromTimeStamp ||
               !$this->dateTime->isEmptyDate($dateTo) && $todayTimeStamp > $toTimeStamp);
    }
}
