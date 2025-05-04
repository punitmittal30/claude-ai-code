<?php
/**
 * Pratech_Warehouse
 *
 * PHP version 8.x
 *
 * @category  PHP
 * @package   Pratech\Warehouse
 * @author    Puneet Mittal <puneet.mittal@pratechbrands.com>
 * @copyright 2025 Copyright (c) Pratech Brands Private Limited
 * @link      https://pratechbrands.com/
 **/

namespace Pratech\Warehouse\Model;

use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Stdlib\DateTime\TimezoneInterface;
use Magento\Store\Model\ScopeInterface;
use Pratech\Quiz\Model\ResourceModel\Quiz\CollectionFactory as QuizCollectionFactory;
use Pratech\Warehouse\Api\PincodeRepositoryInterface;
use Pratech\Warehouse\Api\SystemConfigInterface;

class SystemConfig implements SystemConfigInterface
{
    /**
     * Product Entity Type
     */
    public const ENTITY_TYPE = 'catalog_product';

    /**
     * Maximum order total for COD orders.
     */
    public const COD_MAX_ORDER_TOTAL = 'payment/cashondelivery/max_order_total';

    /**
     * Minimum order total for COD orders.
     */
    public const COD_MIN_ORDER_TOTAL = 'payment/cashondelivery/min_order_total';

    /**
     * Constant for minimum order value.
     */
    public const MINIMUM_ORDER_VALUE = 'delivery/delivery_charges/minimum_order_value';

    /**
     * Constant for delivery charges.
     */
    public const DELIVERY_CHARGES = 'delivery/delivery_charges/amount';

    /**
     * Prepaid Discount Slabs.
     */
    public const PREPAID_DISCOUNT_SLAB = 'prepaid_discount/general/ranges';

    /**
     * STORE CREDIT APPLY LIMIT CONFIGURATION PATH
     */
    public const STORE_CREDIT_APPLY_LIMIT_CONFIG_PATH = 'store_credit/store_credit/store_credit_limit';

    /**
     * STORE CREDIT CONVERSION RATE CONFIGURATION PATH
     */
    public const CONVERSION_RATE_CONFIG_PATH = 'store_credit/store_credit/conversion_rate';
    /**
     * STORE CREDIT TITLE CONFIGURATION PATH
     */
    public const STORE_CREDIT_TITLE = 'store_credit/store_credit/title';
    /**
     * PRODUCT ADDITIONAL LABEL CONFIGURATION PATH
     */
    public const ADDITIONAL_LABEL_CONFIG_PATH = 'product/general/additional_label';

    /**
     * Free Shipping Day Constant
     */
    public const FREE_SHIPPING_DAY = 'delivery/delivery_charges/free_shipping_day';

    /**
     * Default Pincode for estimated delivery date.
     */
    public const DEFAULT_PINCODE = 'warehouse/general/default_pincode';

    /**
     * Default Pincode for estimated delivery date.
     */
    public const CUT_OFF_TIME = 'warehouse/general/cut_off_time';

    /**
     * Config Path for Return Period
     */
    public const CONFIG_PATH_RETURN_PERIOD = 'return/return/return_period_days';

    /**
     * Config Paths for Page Size
     */
    public const PAGE_SIZE = [
        'PLP_DWEB' => 'product/product_page_size/plp_page_size_dweb',
        'SEARCH_DWEB' => 'product/product_page_size/search_page_size_dweb',
        'PLP_MWEB' => 'product/product_page_size/plp_page_size_mweb',
        'SEARCH_MWEB' => 'product/product_page_size/search_page_size_mweb',
        'PLP_APP' => 'product/product_page_size/plp_page_size_app',
        'SEARCH_APP' => 'product/product_page_size/search_page_size_app',
    ];

    /**
     * @param ScopeConfigInterface $scopeConfig
     * @param TimezoneInterface $timezoneInterface
     * @param PincodeRepositoryInterface $pincodeRepository
     * @param QuizCollectionFactory $quizCollectionFactory
     */
    public function __construct(
        private ScopeConfigInterface       $scopeConfig,
        private TimezoneInterface          $timezoneInterface,
        private PincodeRepositoryInterface $pincodeRepository,
        private QuizCollectionFactory      $quizCollectionFactory
    ) {
    }

    /**
     * @inheritDoc
     */
    public function getSystemConfig(): array
    {
        return [
            'minimum_order_value' => (int)$this->getConfig(self::MINIMUM_ORDER_VALUE),
            'delivery_charges' => (int)$this->getConfig(self::DELIVERY_CHARGES),
            'cod_min_order_total' => (int)$this->getConfig(self::COD_MIN_ORDER_TOTAL),
            'cod_max_order_total' => (int)$this->getConfig(self::COD_MAX_ORDER_TOTAL),
            'wallet' => [
                'threshold' => (int)$this->getConfig(self::STORE_CREDIT_APPLY_LIMIT_CONFIG_PATH),
                'title' => $this->getConfig(self::STORE_CREDIT_TITLE),
                'conversion_rate' => $this->getConfig(self::CONVERSION_RATE_CONFIG_PATH)
            ],
            'additional_label' => $this->getConfig(self::ADDITIONAL_LABEL_CONFIG_PATH),
            'cut_off_time' => $this->getConfig(self::CUT_OFF_TIME),
            'prepaid_discount' => $this->getPrepaidDiscountSlabs(),
            'cod_limit' => (int)$this->getConfig(self::COD_MAX_ORDER_TOTAL),
            'is_free_shipping' => $this->getIsFreeDelivery(),
            'estimated_delivery_date' => [
                'default_pincode' => $this->getDefaultPincode()
            ],
            'quiz_ids' => $this->getActiveQuizIds(),
            'order_return_period_days' => $this->getConfig(self::CONFIG_PATH_RETURN_PERIOD),
            'page_size' => [
                'plp_dweb' => (int)$this->getConfig(self::PAGE_SIZE['PLP_DWEB']),
                'search_dweb' => (int)$this->getConfig(self::PAGE_SIZE['SEARCH_DWEB']),
                'plp_mweb' => (int)$this->getConfig(self::PAGE_SIZE['PLP_MWEB']),
                'search_mweb' => (int)$this->getConfig(self::PAGE_SIZE['SEARCH_MWEB']),
                'plp_app' => (int)$this->getConfig(self::PAGE_SIZE['PLP_APP']),
                'search_app' => (int)$this->getConfig(self::PAGE_SIZE['SEARCH_APP']),
            ]
        ];
    }

    /**
     * Get System Config
     *
     * @param string $configPath
     * @return mixed
     */
    public function getConfig(string $configPath): mixed
    {
        return $this->scopeConfig->getValue(
            $configPath,
            ScopeInterface::SCOPE_STORE
        );
    }

    /**
     * Get Prepaid Discount Slabs.
     *
     * @return array
     */
    public function getPrepaidDiscountSlabs(): array
    {
        $prepaidDiscountSlab = [];
        $prepaidDiscountRanges = $this->getConfig(self::PREPAID_DISCOUNT_SLAB);
        if ($prepaidDiscountRanges) {
            $items = json_decode($prepaidDiscountRanges, true);
            foreach ($items as $item) {
                $prepaidDiscountSlab[] = [
                    "from_price" => $item["from_price"],
                    "to_price" => $item["to_price"],
                    "discount_type" => $item["discount_type"],
                    "discount" => $item['discount']
                ];
            }
        }
        return $prepaidDiscountSlab;
    }

    /**
     * Validate if free delivery is applicable for the cart or not.
     *
     * @return int
     */
    public function getIsFreeDelivery(): int
    {
        $isFreeDelivery = 0;
        $freeDeliveryDay = $this->getConfig(self::FREE_SHIPPING_DAY);
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

    /**
     * Get Default Pincode Data.
     *
     * @return array
     */
    public function getDefaultPincode(): array
    {
        try {
            $defaultPincode = (int)$this->getConfig(self::DEFAULT_PINCODE);
            $pincode = $this->pincodeRepository->getPincodeServiceability($defaultPincode);
        } catch (NoSuchEntityException|LocalizedException $e) {
            return [
                'pincode' => '421302',
                'city' => 'Mumbai',
                'state' => 'Maharashtra',
                'is_serviceable' => 1
            ];
        }
        return $pincode;
    }

    /**
     * Get all active quiz IDs.
     *
     * @return array
     */
    public function getActiveQuizIds(): array
    {
        try {
            $quizCollection = $this->quizCollectionFactory->create()
                ->addFieldToFilter('status', ['eq' => 1])
                ->addFieldToSelect('quiz_id');

            $quizIds = $quizCollection->getColumnValues('quiz_id');

            return $quizIds;
        } catch (NoSuchEntityException|LocalizedException $e) {
            return [];
        }
    }
}
