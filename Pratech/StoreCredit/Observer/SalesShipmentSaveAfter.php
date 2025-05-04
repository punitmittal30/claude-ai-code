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

namespace Pratech\StoreCredit\Observer;

use Exception;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Stdlib\DateTime;
use Magento\Sales\Api\OrderItemRepositoryInterface;
use Magento\Sales\Model\Order\Shipment;
use Magento\Store\Model\ScopeInterface;
use Pratech\Base\Logger\Logger;
use Pratech\RedisIntegration\Model\CustomerRedisCache;
use Pratech\StoreCredit\Model\CreditPointsFactory;
use Pratech\StoreCredit\Model\ResourceModel\CreditPoints;

/**
 * Observer to sent sqs event for order and restore coupon usages
 */
class SalesShipmentSaveAfter implements ObserverInterface
{
    /**
     * Add StoreCredit Additional Info Config.
     */
    public const CASHBACK_AGAINST_ORDER_ADDITIONAL_INFO = 'store_credit/cashback_against_order/additional_info';

    /**
     * Exclude UTM Source Config
     */
    public const EXCLUDE_UTM_SOURCE = 'store_credit/store_credit/exclude_from_utm_source';

    /**
     * @param Logger $apiLogger
     * @param OrderItemRepositoryInterface $orderItemRepository
     * @param CreditPointsFactory $creditPointsFactory
     * @param ScopeConfigInterface $scopeConfig
     * @param CustomerRedisCache $customerRedisCache
     * @param CreditPoints $creditPointsResource
     * @param DateTime $dateTime
     */
    public function __construct(
        private Logger                       $apiLogger,
        private OrderItemRepositoryInterface $orderItemRepository,
        private CreditPointsFactory          $creditPointsFactory,
        private ScopeConfigInterface         $scopeConfig,
        private CustomerRedisCache           $customerRedisCache,
        private CreditPoints                 $creditPointsResource,
        private DateTime                     $dateTime
    ) {
    }

    /**
     * @inheritDoc
     */
    public function execute(Observer $observer): void
    {
        $shipment = $observer->getEvent()->getShipment();
        $order = $shipment->getOrder();
        $utmSource = $order->getData('utm_source') ?? '';

        try {
            if ($this->checkExcludedUTMSource($utmSource)) {
                $status = $shipment->getShipmentStatus();
                $isCreditPointsCredited = $this->getCreditPointsByShipmentId($shipment->getId());
                $this->apiLogger->info(
                    'ShipmentId =>' . $shipment->getId() .
                    'OrderId =>' . $order->getIncrementId() .
                    ' IsCreditPointsCredited=>' . $isCreditPointsCredited .
                    ' Status => ' . $status .
                    __METHOD__
                );
                // Shipment status 1 = 'shipped', 4 = 'delivered, 12 = 'rto'
                if ($status == 1 && $isCreditPointsCredited == "") {
                    $this->creditStoreCreditPoints($shipment);
                    $this->apiLogger->info('credited store credit ' . __METHOD__);
                } elseif ($status == 4) {
                    if ($isCreditPointsCredited == "") {
                        $this->creditStoreCreditPoints($shipment);
                    }
                    $this->updateStoreCreditPointsStatus($shipment);
                } elseif ($status == 12) {
                    $this->revertStoreCreditPoints($shipment);
                }
                $this->customerRedisCache->deleteCustomerStoreCreditTransactions($order->getCustomerId());
            }
        } catch (Exception $exception) {
            $this->apiLogger->error($exception->getMessage() . __METHOD__);
        }
    }

    /**
     * Check Excluded UTM Source
     *
     * @param string $source
     * @return bool
     */
    public function checkExcludedUTMSource(string $source): bool
    {
        $excludedUtmSources = $this->scopeConfig->getValue(
            self::EXCLUDE_UTM_SOURCE,
            ScopeInterface::SCOPE_STORE
        );
        if (empty($excludedUtmSources) || empty($source)) {
            return true;
        }
        $excludedUtmSources = explode(",", $excludedUtmSources);

        foreach ($excludedUtmSources as $utmSource) {
            if (stripos($utmSource, $source) !== false) {
                return false;
            }
        }

        return true;
    }

    /**
     * Get Credit Points By Shipment Id.
     *
     * @param int $shipmentId
     * @return string
     * @throws LocalizedException
     */
    public function getCreditPointsByShipmentId(int $shipmentId): string
    {
        $connection = $this->creditPointsResource->getConnection();

        $select = $connection->select()
            ->from($this->creditPointsResource->getMainTable(), 'storecredit_id')
            ->where('shipment_id = ?', $shipmentId);

        return $connection->fetchOne($select);
    }

    /**
     * Credit Store Credit Points
     *
     * @param Shipment $shipment
     * @return void
     */
    public function creditStoreCreditPoints(Shipment $shipment): void
    {
        $points = $this->calculateStoreCreditPoints($shipment);
        $order = $shipment->getOrder();
        $msg = $this->scopeConfig->getValue(
            self::CASHBACK_AGAINST_ORDER_ADDITIONAL_INFO,
            ScopeInterface::SCOPE_STORE
        );
        $additionalInfo = str_replace("%s", $order->getIncrementId(), $msg);

        $creditPoints = $this->creditPointsFactory->create()->setCustomerId(
            $order->getCustomerId()
        )->setOrderId(
            $order->getId()
        )->setShipmentId(
            $shipment->getId()
        )->setCreditPoints(
            $points
        )->setCreditedStatus(
            false
        )->setAdditionalInfo(
            $additionalInfo
        )->setCanCredit(
            0
        )->save();
    }

    /**
     * Calculate StoreCredit Points
     *
     * @param Shipment $shipment
     * @return float
     */
    public function calculateStoreCreditPoints(Shipment $shipment): float
    {
        $order = $shipment->getOrder();
        $orderTotalWithoutShipping = $order->getGrandTotal() - $order->getDeliveryCharges();
        $shipmentTotal = $storeCreditPoints = 0;
        if ($order->getGrandTotal() && $order->getEligibleCashback()) {
            foreach ($shipment->getItems() as $item) {
                $itemId = $item->getOrderItemId();
                $orderItem = $this->orderItemRepository->get($itemId);
                $orderItemRowTotal = (($orderItem->getRowTotal() - $orderItem->getDiscountAmount()) /
                        $orderItem->getQtyOrdered()) * $item->getQty();
                $shipmentTotal += $orderItemRowTotal;
            }
            $storeCreditPoints = ($shipmentTotal / $orderTotalWithoutShipping) * $order->getEligibleCashback();
        }
        $this->apiLogger->info(
            'StoreCreditPoints =>' . $storeCreditPoints .
            ' ShipmentTotal=>' . $shipmentTotal .
            ' EligibleCashback => ' . $order->getEligibleCashback() .
            __METHOD__
        );
        return (int)$storeCreditPoints;
    }

    /**
     * Update Store Credit Points Status
     *
     * @param Shipment $shipment
     * @return void
     */
    public function updateStoreCreditPointsStatus(Shipment $shipment): void
    {
        $creditPoints = $this->creditPointsFactory->create()
            ->load($shipment->getId(), 'shipment_id');
        if ($creditPoints->getStorecreditId()) {
            $creditPoints->setCanCredit(1)
                ->setCreatedAt($this->dateTime->formatDate(time()));
            $creditPoints->save();
        }
    }

    /**
     * Revert Store Credit Points(Might be used later on)
     *
     * @param Shipment $shipment
     * @return void
     */
    public function revertStoreCreditPoints(Shipment $shipment): void
    {
        $creditPoints = $this->creditPointsFactory->create()
            ->load($shipment->getId(), 'shipment_id');
        $creditPoints->delete();
    }
}
