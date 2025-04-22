<?php
/**
 * Pratech_StoreCredit
 *
 * PHP version 8.x
 *
 * @category  PHP
 * @package   Pratech\StoreCredit
 * @author    Vivek Kumar <vivek.kumar@pratechbrands.com>
 * @copyright 2023 Copyright (c) Pratech Brands Private Limited
 * @link      https://pratechbrands.com/
 **/

namespace Pratech\StoreCredit\Helper;

use DateTime;
use Exception;
use Magento\Customer\Api\CustomerRepositoryInterface;
use Magento\CustomerBalance\Model\Balance;
use Magento\CustomerBalance\Model\Balance\History;
use Magento\CustomerBalance\Model\Balance\HistoryFactory;
use Magento\CustomerBalance\Model\BalanceFactory;
use Magento\CustomerBalance\Model\ResourceModel\Balance as CustomerBalance;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Stdlib\DateTime\TimezoneInterface;
use Magento\Quote\Api\Data\TotalsInterface;
use Magento\Sales\Model\OrderFactory as SalesOrderFactory;
use Magento\SalesRule\Model\ResourceModel\Rule\CollectionFactory as RuleCollectionFactory;
use Magento\Store\Model\ScopeInterface;
use Magento\Store\Model\StoreManagerInterface;
use Pratech\Base\Logger\Logger;
use Pratech\SqsIntegration\Model\SqsEvent;
use Pratech\StoreCredit\Model\CreditPointsFactory;
use Pratech\StoreCredit\Model\ResourceModel\CreditPoints\CollectionFactory;

class Data
{
    public const WEBSITE_ID = 1;

    /**
     * StoreCredit Helper Constructor
     *
     * @param ScopeConfigInterface $scopeConfig
     * @param BalanceFactory $balanceFactory
     * @param HistoryFactory $historyFactory
     * @param StoreManagerInterface $storeManager
     * @param CreditPointsFactory $creditPointsFactory
     * @param RuleCollectionFactory $ruleCollectionFactory
     * @param Logger $apiLogger
     * @param TimezoneInterface $timezone
     * @param SqsEvent $sqsEvent
     * @param CustomerRepositoryInterface $customerRepository
     * @param \Pratech\Customer\Helper\Data $customerHelper
     * @param CustomerBalance $customerBalance
     * @param SalesOrderFactory $salesOrderFactory
     */
    public function __construct(
        private ScopeConfigInterface          $scopeConfig,
        private BalanceFactory                $balanceFactory,
        private HistoryFactory                $historyFactory,
        private StoreManagerInterface         $storeManager,
        private CreditPointsFactory           $creditPointsFactory,
        private RuleCollectionFactory         $ruleCollectionFactory,
        private Logger                        $apiLogger,
        private TimezoneInterface             $timezone,
        private SqsEvent                      $sqsEvent,
        private CustomerRepositoryInterface   $customerRepository,
        private \Pratech\Customer\Helper\Data $customerHelper,
        private CustomerBalance               $customerBalance,
        private SalesOrderFactory             $salesOrderFactory
    ) {
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
     * Get Store Credit Transaction
     *
     * @param integer $customerId
     * @return array
     * @throws NoSuchEntityException
     */
    public function getStoreCreditTransaction(int $customerId): array
    {
        $model = $this->balanceFactory->create()->setCustomerId($customerId)->loadByCustomer();
        $collection = $this->historyFactory->create()->getCollection()->addFieldToFilter(
            'customer_id',
            $customerId
        )->addFieldToFilter(
            'website_id',
            $this->storeManager->getStore()->getWebsiteId()
        )->addOrder(
            'updated_at',
            'DESC'
        )->addOrder(
            'history_id',
            'DESC'
        );

        $historyData = [
            'balance' => $model->getAmount(),
            'pending_balance' => $this->getPendingCreditPointsData($customerId)
        ];

        if ($collection->getSize()) {
            foreach ($collection as $history) {
                $historyData['history'][] = [
                    'action' => $this->getLabel($history->getAction()),
                    'balance_amount' => $history->getBalanceDelta(),
                    'updated_at' => $this->getTimeBasedOnTimezone($history->getUpdatedAt()),
                    'additional_info' => $history->getAdditionalInfo()
                ];
            }
        }

        return $historyData;
    }

    /**
     * Get Pending Credit Points Data
     *
     * @param int $customerId
     * @return array
     */
    public function getPendingCreditPointsData(int $customerId): array
    {
        $pointsData = [];
        $totalPoints = 0;
        $creditPoints = $this->creditPointsFactory->create()
            ->getCollection()
            ->addFieldToFilter('credited_status', ['neq' => 1])
            ->addFieldToFilter('customer_id', $customerId);

        foreach ($creditPoints as $point) {
            $pointsData['history'][] = [
                'action' => 'Created',
                'balance_amount' => $point->getCreditPoints(),
                'updated_at' => $this->getTimeBasedOnTimezone($point->getCreatedAt()),
                'additional_info' => $point->getAdditionalInfo()
            ];
            $totalPoints += $point->getCreditPoints();
        }
        $pointsData['balance'] = $totalPoints;

        return $pointsData;
    }

    /**
     * Get Time Based On Timezone
     *
     * @param string $date
     * @return string
     */
    private function getTimeBasedOnTimezone(string $date): string
    {
        try {
            $locale = $this->scopeConfig->getValue(
                'general/locale/timezone',
                ScopeInterface::SCOPE_STORE
            );
            return $this->timezone->date(new DateTime($date), $locale)->format('Y-m-d H:i:s');
        } catch (Exception $e) {
            $this->apiLogger->error($e->getMessage() . __METHOD__);
            return "";
        }
    }

    /**
     * Get Label
     *
     * @param int $action
     * @return string
     */
    public function getLabel(int $action): string
    {
        return match ($action) {
            1 => 'Updated',
            2 => 'Created',
            3 => 'Used',
            4 => 'Refunded',
            5 => 'Reverted',
            6 => 'Expired',
            default => '',
        };
    }

    /**
     * Add Fixed Amount of Store Credit
     *
     * @param int $customerId
     * @param float $fixedAmount
     * @param string $comment
     * @param array $event
     * @param int $expiryDays
     * @return void
     */
    public function addStoreCredit(
        int    $customerId,
        float  $fixedAmount,
        string $comment,
        array  $event,
        int $expiryDays = 0
    ): void {
        /** @var Balance $balance */
        $balance = $this->balanceFactory->create()->setCustomerId(
            $customerId
        )->setWebsiteId(
            self::WEBSITE_ID
        )->setAmountDelta(
            $fixedAmount
        )->setHistoryAction(
            History::ACTION_CREATED
        )->setUpdatedActionAdditionalInfo(
            $comment
        )->setExpiryDays(
            $expiryDays
        )->save();

        if ($balance->getId()) {
            $data = $this->getStoreCreditEventData($customerId, $fixedAmount, $comment, $event);
            $this->sqsEvent->sendStoreCreditEvent($data);
        }
    }

    /**
     * Get Store Credit Event Data.
     *
     * @param int $customerId
     * @param float $fixedAmount
     * @param string $comment
     * @param array $eventParams
     * @return array
     */
    public function getStoreCreditEventData(
        int    $customerId,
        float  $fixedAmount,
        string $comment,
        array  $eventParams,
    ): array {
        $data = [];
        try {
            $customer = $this->customerRepository->getById($customerId);

            $connection = $this->customerBalance->getConnection();
            $select = $connection->select()
                ->from($this->customerBalance->getMainTable(), 'amount')
                ->where('customer_id = ?', $customerId);

            $conversionRate = $this->scopeConfig->getValue(
                'store_credit/store_credit/conversion_rate',
                ScopeInterface::SCOPE_STORE
            );

            $convertedBalance = $connection->fetchOne($select) * $conversionRate;
            $convertedEarnedCashback = $fixedAmount * $conversionRate;
            $totalHcashBalance = number_format(
                $connection->fetchOne($select),
                2,
                '.',
                ''
            );

            $data['user_details'] = [
                'mage_id' => $customerId,
                'firstname' => $customer->getFirstname(),
                'lastname' => $customer->getLastname(),
                'email' => $customer->getEmail(),
                'mobile_number' => $customer->getCustomAttribute('mobile_number')
                    ? $customer->getCustomAttribute('mobile_number')->getValue()
                    : ''
            ];

            $data['total_hcash_balance'] = (float)$totalHcashBalance;
            $data['converted_hcash_balance'] = $convertedBalance;
            $data['earned_cashback'] = $fixedAmount;
            $data['converted_earned_cashback'] = $convertedEarnedCashback;
            $data['comment'] = $comment;

            switch ($eventParams['event_name']) {
                case 'registration':
                case 'review_approved':
                case 'import':
                case 'updateHcash':
                case 'quiz':
                    break;
                case 'try_before_you_buy':
                    $data['hcash_promo_code'] = $eventParams['promo_code'];
                    break;
                case 'order':
                    try {
                        if (!empty($eventParams['order_id'])) {
                            $data['order_details'] = $this->customerHelper
                                ->viewOrderDetails($customerId, $eventParams['order_id']);
                        }
                    } catch (Exception $e) {
                        $this->apiLogger->error("Get Store Credit Event Data Error for Order ID: "
                            . $eventParams['order_id'] . " | " . $e->getMessage() . __METHOD__);
                    }
                    break;
            }
        } catch (NoSuchEntityException|LocalizedException $e) {
            $this->apiLogger->error("Get Store Credit Event Data Error for Customer ID: " . $customerId . " | "
                . $e->getMessage() . __METHOD__);
            return $data;
        }
        return $data;
    }

    /**
     * Get Cashback Amount Method
     *
     * @param TotalsInterface $quoteTotals
     * @return int
     */
    public function getCashbackAmount(TotalsInterface $quoteTotals): int
    {
        $storeCreditPoints = 0;
        try {
            $grandTotal = $quoteTotals->getGrandTotal();
            $appliedRules = $quoteTotals->getExtensionAttributes()->getStoreCreditRule();
            foreach ($appliedRules as $appliedRule) {
                $ruleId = $appliedRule->getRuleId();
                $rules = $this->ruleCollectionFactory->create()->addFieldToFilter('is_active', 1)
                    ->addFieldToFilter('rule_id', $ruleId);
                foreach ($rules as $rule) {
                    if ($rule->getStorecreditApply() == 'percent' && $rule->getStoreCreditPoint() > 0) {
                        $storeCreditPoints += (int)(($rule->getStoreCreditPoint() / 100) * $grandTotal);
                    } else {
                        $storeCreditPoints += (int)$rule->getStoreCreditPoint();
                    }
                }
            }
        } catch (Exception $exception) {
            $this->apiLogger->error($exception->getMessage());
        }
        return $storeCreditPoints;
    }

    /**
     * Refund Store Credit
     *
     * @param int $customerId
     * @param float $fixedAmount
     * @param int $orderId
     * @return void
     */
    public function refundStoreCredit(
        int   $customerId,
        float $fixedAmount,
        int   $orderId
    ): void {
        $order = $this->salesOrderFactory->create()
            ->loadByIncrementId($orderId);

        /** @var Balance $balance */
        $balance = $this->balanceFactory->create()->setCustomerId(
            $customerId
        )->setWebsiteId(
            self::WEBSITE_ID
        )->setAmountDelta(
            $fixedAmount * -1
        )->setHistoryAction(
            History::ACTION_REFUNDED
        )->setOrder(
            $order
        )->setCreditMemo(
            $order
        )->save();
    }

    /**
     * Delete Store Credit
     *
     * @param int $customerId
     * @return void
     */
    public function deleteStoreCredit(int $customerId): void
    {
        /** @var Balance $balance */
        $balance = $this->balanceFactory->create()->deleteBalancesByCustomerId(
            $customerId
        )->save();
    }
}
