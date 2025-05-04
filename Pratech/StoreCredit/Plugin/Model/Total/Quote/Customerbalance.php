<?php
/**
 * Pratech_StoreCredit
 *
 * PHP version 8.x
 *
 * @category  PHP
 * @package   Pratech\StoreCredit
 * @author    Vivek Kumar <vivek.kumar@pratechbrands.com>
 * @copyright 2022 Copyright (c) Pratech Brands Private Limited
 * @link      https://pratechbrands.com/
 **/

namespace Pratech\StoreCredit\Plugin\Model\Total\Quote;

use Exception;
use Magento\CustomerBalance\Helper\Data;
use Magento\CustomerBalance\Model\BalanceFactory;
use Magento\Framework\Event\ManagerInterface;
use Magento\Framework\Pricing\PriceCurrencyInterface;
use Magento\Quote\Api\Data\ShippingAssignmentInterface;
use Magento\Quote\Model\Quote;
use Magento\Quote\Model\Quote\Address;
use Magento\Quote\Model\Quote\Address\Total;
use Magento\Quote\Model\Quote\Address\Total\AbstractTotal;
use Magento\Store\Model\StoreManagerInterface;
use Pratech\Base\Logger\Logger;
use Pratech\StoreCredit\Helper\Data as StoreCreditHelper;

class Customerbalance extends AbstractTotal
{
    /**
     * STORE CREDIT CONVERSION RATE CONFIGURATION PATH
     */
    public const CONVERSION_RATE_CONFIG_PATH = 'store_credit/store_credit/conversion_rate';

    /**
     * STORE CREDIT APPLY LIMIT CONFIGURATION PATH
     */
    public const STORE_CREDIT_APPLY_LIMIT_CONFIG_PATH = 'store_credit/store_credit/store_credit_limit';

    /**
     * Add StoreCredit Title Config.
     */
    public const STORE_CREDIT_TITLE = 'store_credit/store_credit/title';

    /**
     * @var Data
     */
    protected $_customerBalanceData = null;

    /**
     * @var StoreManagerInterface
     */
    protected $_storeManager;

    /**
     * @var BalanceFactory
     */
    protected $_balanceFactory;

    /**
     * @var PriceCurrencyInterface
     */
    protected $priceCurrency;

    /**
     * @param StoreManagerInterface $storeManager
     * @param BalanceFactory $balanceFactory
     * @param Data $customerBalanceData
     * @param PriceCurrencyInterface $priceCurrency
     * @param ManagerInterface $eventManager
     * @param StoreCreditHelper $storeCreditHelper
     * @param Logger $apiLogger
     */
    public function __construct(
        StoreManagerInterface     $storeManager,
        BalanceFactory            $balanceFactory,
        Data                      $customerBalanceData,
        PriceCurrencyInterface    $priceCurrency,
        private ManagerInterface  $eventManager,
        private StoreCreditHelper $storeCreditHelper,
        private Logger            $apiLogger
    )
    {
        $this->priceCurrency = $priceCurrency;
        $this->_storeManager = $storeManager;
        $this->_balanceFactory = $balanceFactory;
        $this->_customerBalanceData = $customerBalanceData;
        $this->setCode('customerbalance');
    }

    /**
     * Collect customer balance totals for specified address
     *
     * @param Quote $quote
     * @param ShippingAssignmentInterface $shippingAssignment
     * @param Total $total
     * @return Customerbalance
     */
    public function collect(
        Quote                       $quote,
        ShippingAssignmentInterface $shippingAssignment,
        Total                       $total
    )
    {
        if (!$this->_customerBalanceData->isEnabled()) {
            return $this;
        }

        if ($shippingAssignment->getShipping()->getAddress()->getCustomerAddressType() == Address::TYPE_SHIPPING
            && $quote->isVirtual()
        ) {
            return $this;
        }

        try {
            $baseTotalUsed = $totalUsed = $baseUsed = $used = 0;
            $baseGrandTotalWithoutPrepaid = 0;

            $conversionRate = $this->storeCreditHelper->getConfig(self::CONVERSION_RATE_CONFIG_PATH);
            $applyLimit = $this->storeCreditHelper->getConfig(self::STORE_CREDIT_APPLY_LIMIT_CONFIG_PATH);

            $baseBalance = $balance = 0;
            if ($quote->getCustomer()->getId()) {
                if ($quote->getUseCustomerBalance()) {
                    $store = $this->_storeManager->getStore($quote->getStoreId());
                    $storeCreditPoints = $this->_balanceFactory->create()
                        ->setCustomer($quote->getCustomer())
                        ->setCustomerId($quote->getCustomer()->getId())
                        ->setWebsiteId($store->getWebsiteId())
                        ->loadByCustomer()
                        ->getAmount();

                    $baseGrandTotalWithoutPrepaid = $quote->getBaseGrandTotalWithoutPrepaid();

                    // Calculate the maximum amount that can be used from the customer's balance
                    $maxBalanceUsage = (int)(($baseGrandTotalWithoutPrepaid * $applyLimit) / 100);
                    $storeCreditPointsInAmount = (int)($storeCreditPoints * $conversionRate);

                    // Use the lesser of the customer's balance and the calculated maximum balance usage
                    $baseUsed = min($maxBalanceUsage, $storeCreditPointsInAmount);
                    $balance = $this->priceCurrency->convert($baseUsed, $quote->getStore());
                }
            }

            $baseAmountLeft = $baseUsed - $quote->getBaseCustomerBalAmountUsed();

            $amountLeft = $balance - $quote->getCustomerBalanceAmountUsed();

            if ($baseAmountLeft >= $total->getBaseGrandTotal()) {
                $baseUsed = $total->getBaseGrandTotal();
                $used = $total->getGrandTotal();

                $total->setBaseGrandTotal(0);
                $total->setGrandTotal(0);
            } else {
                $baseUsed = $baseAmountLeft;
                $used = $amountLeft;

                $total->setBaseGrandTotal($total->getBaseGrandTotal() - $balance);
                $total->setGrandTotal($total->getGrandTotal() - $balance);
            }

            $baseTotalUsed = $quote->getBaseCustomerBalAmountUsed() + $baseUsed;
            $totalUsed = $quote->getCustomerBalanceAmountUsed() + $used;

            $quote->setBaseCustomerBalAmountUsed($baseTotalUsed);
            $quote->setCustomerBalanceAmountUsed($totalUsed);

            $total->setBaseCustomerBalanceAmount($baseUsed);
            $total->setCustomerBalanceAmount($used);

            $itemCount = count($quote->getAllVisibleItems());
            if ($itemCount) {
                $totalAmount = $this->getBaseAmount($total);
                foreach ($quote->getAllVisibleItems() as $item) {
                    if ($totalAmount > 0) {
                        $discountPerItem = -$used * (($item->getRowTotal() - $item->getDiscountAmount()) /
                                $totalAmount);
                        // Set the discount amount for each item
                        $item->setDiscountAmount($item->getDiscountAmount() - $discountPerItem);
                        $item->setBaseDiscountAmount($item->getBaseDiscountAmount() - $discountPerItem);
                        try {
                            $item->save();

                            // Add the customer store credit balance in discount report logs
                            $this->eventManager->dispatch(
                                'pratech_discountreport_process',
                                [
                                    'item' => $item,
                                    'quote' => $quote,
                                    'discount_type' => 'customerbalance',
                                    'discount_amount' => $discountPerItem
                                ]
                            );
                        } catch (Exception $e) {
                            $this->apiLogger->error("Unable to save discount amount for Quote ID : "
                                . $item->getQuoteId() . " | Item ID : " . $item->getItemId()
                                . " | " . $e->getMessage());
                        }
                    }
                }
            }
        } catch (Exception $exception) {
            $this->apiLogger->error($exception->getMessage() . __METHOD__);
        }

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
        return ($totals['subtotal'] + $totals['discount'] + $totals['prepaid_discount']);
    }

    /**
     * Return shopping cart total row items
     *
     * @param Quote $quote
     * @param Total $total
     * @return array|null
     *
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function fetch(Quote $quote, Total $total)
    {
        if ($this->_customerBalanceData->isEnabled() && $total->getCustomerBalanceAmount()) {
            return [
                'code' => $this->getCode(),
                'title' => __($this->getStoreCreditLabel()),
                'value' => -$total->getCustomerBalanceAmount()
            ];
        }
        return null;
    }

    /**
     * Return Store Credit Title to be shown in FE.
     *
     * @return mixed
     */
    public function getStoreCreditLabel(): mixed
    {
        return $this->storeCreditHelper->getConfig(self::STORE_CREDIT_TITLE);
    }
}
