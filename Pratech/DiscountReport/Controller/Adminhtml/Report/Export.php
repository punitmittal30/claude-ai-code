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
namespace Pratech\DiscountReport\Controller\Adminhtml\Report;

use Exception;
use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\App\Filesystem\DirectoryList;
use Magento\Framework\App\Response\Http\FileFactory;
use Magento\Framework\App\Response\RedirectInterface;
use Magento\Framework\App\ResponseInterface;
use Magento\Sales\Model\ResourceModel\Order\CollectionFactory as OrderCollectionFactory;
use Pratech\DiscountReport\Model\ResourceModel\Log\CollectionFactory as DiscountLogCollectionFactory;

class Export extends Action
{
    /**
     * @var string[]
     */
    protected array $header = [
        'Order id',
        'Sku',
        'Qty',
        'Amt Before Discount',
        'Amt After Discount',
        'Prepaid discount',
        'Srore credit'
    ];

    /**
     * @var array
     */
    protected array $records = [];

    /**
     * @var array
     */
    protected array $ruleRecords = [];

    /**
     * @var array
     */
    protected array $couponRecords = [];

    /**
     * @var int
     */
    protected int $ruleCount = 0;

    /**
     * @var int
     */
    protected int $couponCount = 0;

    /**
     * @var int
     */
    protected int $pageSize = 5000;

    /**
     * Constructor
     *
     * @param Context $context
     * @param FileFactory $fileFactory
     * @param RedirectInterface $redirect
     * @param OrderCollectionFactory $orderCollectionFactory
     * @param DiscountLogCollectionFactory $discountLogCollectionFactory
     */
    public function __construct(
        protected Context                      $context,
        protected FileFactory                  $fileFactory,
        protected RedirectInterface            $redirect,
        protected OrderCollectionFactory       $orderCollectionFactory,
        protected DiscountLogCollectionFactory $discountLogCollectionFactory
    ) {
        parent::__construct($context);
    }

    /**
     * @inheritDoc
     */
    public function execute(): ResponseInterface
    {
        try {
            $fromDate = $this->getRequest()->getParam('from');
            $toDate = $this->getRequest()->getParam('to');
            $fromDate = date("Y-m-d 00:00:00", strtotime($fromDate));
            $toDate = date("Y-m-d 23:59:59", strtotime($toDate));

            $orders = $this->orderCollectionFactory->create()
                ->addAttributeToFilter('created_at', ['from' => $fromDate, 'to' => $toDate]);
            $orders->setPageSize($this->pageSize);
            $pageCount = $orders->getLastPageNumber();

            for ($pageNum = 1; $pageNum <= $pageCount; $pageNum++) {
                $orders = $this->orderCollectionFactory->create()
                    ->addAttributeToFilter('created_at', ['from' => $fromDate, 'to' => $toDate]);
                $orders->setPageSize($this->pageSize);
                $orders->setCurPage($pageNum);

                $this->loadRecords($orders);
            }
            $this->manageRecords();

            $output = array_merge([$this->header], array_values($this->records));

            $fileContent = $this->fileFactory->create(
                'discountreport.csv',
                $this->getCsvContent($output),
                DirectoryList::VAR_DIR,
                'application/octet-stream'
            );

            return $fileContent;
        } catch (Exception $e) {
            $this->messageManager->addError($e->getMessage());
        }
    }

    /**
     * Load Discount Record Data
     *
     * @param \Magento\Sales\Model\ResourceModel\Order\Collection $orders
     * @return void
     */
    private function loadRecords(\Magento\Sales\Model\ResourceModel\Order\Collection $orders)
    {
        foreach ($orders as $order) {
            $quoteId = $order->getQuoteId();
            $orderAppliedRuleIds = explode(',', $order->getAppliedRuleIds() ?? '');
            foreach ($order->getAllItems() as $item) {
                $itemSku = $item->getSku();

                $discountLogCollection = $this->discountLogCollectionFactory->create()
                    ->addFieldToFilter('quote_id', ['eq' => $quoteId])
                    ->addFieldToFilter('item_sku', ['eq' => $itemSku]);
                if (!$discountLogCollection->getSize()) {
                    continue;
                }
                $discountLog = $discountLogCollection->getFirstItem();
                $discountDataArray = $discountLog->getDiscountData()
                    ? json_decode($discountLog->getDiscountData(), true)
                    : [];

                $appliedCouponCodes = explode(',', $order->getCouponCode() ?? '');
                $orderItemAppliedRuleIds = explode(',', $item->getAppliedRuleIds() ?? '');
                $appliedRuleIds = array_intersect($orderAppliedRuleIds, $orderItemAppliedRuleIds);
                $discountAmount = $item->getBaseDiscountAmount();
                $subTotal = $item->getBaseRowTotal();
                $rowTotalInclTax = $item->getBaseRowTotalInclTax();

                $record = [
                    (string) $order->getIncrementId(),
                    $itemSku,
                    (int) $item->getQtyOrdered(),
                    $rowTotalInclTax,
                    $rowTotalInclTax - $discountAmount
                ];

                $prepaidDiscountArray = $discountDataArray['prepaid_discount'] ?? [];
                $prepaidDiscountAmount = '';
                foreach ($prepaidDiscountArray as $discountKey => $discountData) {
                    if (!empty((float)$order->getBasePrepaidDiscount())) {
                        $prepaidDiscountAmount = $discountData['amount'];
                    }
                }
                $record[] = $prepaidDiscountAmount;

                $customerBalanceArray = $discountDataArray['customerbalance'] ?? [];
                $customerBalanceAmount = '';
                foreach ($customerBalanceArray as $discountKey => $discountData) {
                    if (!empty((float)$order->getCustomerBalanceAmount())) {
                        $customerBalanceAmount = $discountData['amount'];
                    }
                }
                $record[] = $customerBalanceAmount;

                $ruleDiscountArray = $discountDataArray['rule'] ?? [];
                $ruleAmountArray = [];
                foreach ($ruleDiscountArray as $discountKey => $discountData) {
                    if (in_array($discountKey, $appliedRuleIds) && !empty($discountData['amount'])) {
                        $ruleAmountArray[] = $discountData['amount'] . ' @' . $discountKey;
                    }
                }
                $this->ruleRecords[] = $ruleAmountArray;
                if (count($ruleAmountArray) > $this->ruleCount) {
                    $this->ruleCount = count($ruleAmountArray);
                }

                $couponDiscountArray = $discountDataArray['coupon'] ?? [];
                $couponAmountArray = [];
                foreach ($couponDiscountArray as $discountKey => $discountData) {
                    if (in_array($discountKey, $appliedRuleIds)
                        && in_array($discountData['coupon_code'], $appliedCouponCodes)) {
                        $couponAmountArray[] = $discountData['amount'] . ' @' . $discountData['coupon_code'];
                    }
                }
                $this->couponRecords[] = $couponAmountArray;
                if (count($couponAmountArray) > $this->couponCount) {
                    $this->couponCount = count($couponAmountArray);
                }

                $this->records[] = $record;
            }
        }
    }

    /**
     * Manage records
     *
     * @return void
     */
    private function manageRecords()
    {
        for ($ruleNum = 1; $ruleNum <= $this->ruleCount; $ruleNum++) {
            $this->header[] = "Cart rule $ruleNum amt";
        }
        for ($couponNum = 1; $couponNum <= $this->couponCount; $couponNum++) {
            $this->header[] = "Coupon $couponNum amt";
        }

        foreach ($this->ruleRecords as &$ruleRecord) {
            for ($ruleNum = 0; $ruleNum < $this->ruleCount; $ruleNum++) {
                if (!isset($ruleRecord[$ruleNum])) {
                    $ruleRecord[$ruleNum] = '';
                }
            }
        }
        foreach ($this->couponRecords as &$couponRecord) {
            for ($couponNum = 0; $couponNum < $this->couponCount; $couponNum++) {
                if (!isset($couponRecord[$couponNum])) {
                    $couponRecord[$couponNum] = '';
                }
            }
        }

        $totalRecords = count($this->records);
        for ($i = 0; $i < $totalRecords; $i++) {
            $ruleCouponCombinedRecord = $this->mergeArray($this->ruleRecords[$i], $this->couponRecords[$i]);
            $this->records[$i] = $this->mergeArray($this->records[$i], $ruleCouponCombinedRecord);
        }
    }

    /**
     * Merge two array
     *
     * @param array $firstArray
     * @param array $secondArray
     * @return array
     */
    private function mergeArray(array $firstArray, array $secondArray): array
    {
        return array_merge($firstArray, $secondArray);
    }

    /**
     * Get Csv Content
     *
     * @param  array $data
     * @return string
     */
    protected function getCsvContent(array $data) : string
    {
        $csvContent = '';
        foreach ($data as $row) {
            $csvContent .= implode(',', array_map([$this, 'encloseCsvField'], $row)) . "\n";
        }
        return $csvContent;
    }

    /**
     * Enclose Csv Field
     *
     * @param  string $field
     * @return string
     */
    protected function encloseCsvField(string $field): string
    {
        // If the field contains a comma, double-quote, or newline, enclose it in double quotes
        if (preg_match('/[",\n]/', $field)) {
            return '"' . str_replace('"', '""', $field) . '"';
        }
        return $field;
    }
}
