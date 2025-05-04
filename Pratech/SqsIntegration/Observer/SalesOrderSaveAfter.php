<?php
/**
 * Pratech_SqsIntegration
 *
 * PHP version 8.x
 *
 * @category  PHP
 * @package   Pratech\SqsIntegration
 * @author    Vivek Kumar <vivek.kumar@pratechbrands.com>
 * @copyright 2023 Copyright (c) Pratech Brands Private Limited
 * @link      https://pratechbrands.com/
 **/

namespace Pratech\SqsIntegration\Observer;

use Amasty\RulesPro\Model\ResourceModel\RuleUsageCounter;
use Exception;
use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\UrlInterface;
use Magento\Sales\Model\Order;
use Magento\Sales\Model\Order\Address;
use Magento\SalesRule\Model\Coupon\UpdateCouponUsages;
use Magento\SalesRule\Model\CouponFactory;
use Magento\Store\Model\StoreManagerInterface;
use Pratech\Base\Helper\Data as BaseHelper;
use Pratech\Base\Logger\Logger;
use Pratech\SqsIntegration\Model\SqsEvent;

/**
 * Observer to sent sqs event for order and restore coupon usages
 */
class SalesOrderSaveAfter implements ObserverInterface
{
    /**
     * Send Sqs Event Constructor
     *
     * @param SqsEvent $sqsEvent
     * @param StoreManagerInterface $storeManager
     * @param Logger $apiLogger
     * @param BaseHelper $baseHelper
     * @param UpdateCouponUsages $updateCouponUsages
     * @param RuleUsageCounter $ruleUsageCounter
     * @param CouponFactory $couponFactory
     */
    public function __construct(
        private SqsEvent              $sqsEvent,
        private StoreManagerInterface $storeManager,
        private Logger                $apiLogger,
        private BaseHelper            $baseHelper,
        private UpdateCouponUsages    $updateCouponUsages,
        private RuleUsageCounter      $ruleUsageCounter,
        private CouponFactory         $couponFactory
    ) {
    }

    /**
     * @inheritDoc
     */
    public function execute(Observer $observer)
    {
        $order = $observer->getEvent()->getOrder();
        try {
            if ($order->getStatus() == 'processing') {
                $emailData = $this->getOrderDataForEmail($order, 'ORDER_CONFIRMED');
                $smsData = $this->getOrderDataForSms($order, 'ORDER_CONFIRMED');
                $this->sqsEvent->sentSmsEventToSqs($smsData);
                $this->sqsEvent->sentEmailEventToSqs($emailData);

                // send data for Food Darzee email
                $emailDataForFD = $this->getOrderDataForFoodDarzeeEmail($order, 'ORDER_CONFIRMED_FD');
                $this->sqsEvent->sentEmailEventToSqs($emailDataForFD);
            } elseif ($order->getStatus() == 'canceled') {
                $emailData = $this->getOrderDataForEmail($order, 'ORDER_CANCELLED');
                $comment = $order->getStatusHistoryCollection();
                $emailData['order_cancellation_date'] = $this->baseHelper
                    ->getDateTimeBasedOnTimezone($order->getUpdatedAt(), 'd/m/y H:i:s');
                $emailData['cancellation_reason'] = $comment ? $comment->getFirstItem()->getComment() : '';
                $this->sqsEvent->sentEmailEventToSqs($emailData);

                $smsData = $this->getOrderDataForSms($order, 'ORDER_CANCELLED');
                $smsData['cancellation_reason'] = $comment ? $comment->getFirstItem()->getComment() : '';
                $this->sqsEvent->sentSmsEventToSqs($smsData);

                // send data for Food Darzee email
                $emailDataForFD = $this->getOrderDataForFoodDarzeeEmail($order, 'ORDER_CANCELLED_FD');
                $emailDataForFD['order_cancellation_date'] = $emailData['order_cancellation_date'];
                $emailDataForFD['cancellation_reason'] = $emailData['cancellation_reason'];
                $this->sqsEvent->sentEmailEventToSqs($emailDataForFD);
            } elseif ($order->getStatus() == 'closed' && $order->getReturnRequests() == null) {
                $emailData = $this->getOrderDataForEmail($order, 'ORDER_CLOSED');
                // Additional data specific to 'ORDER_CLOSED' event
                $this->sqsEvent->sentEmailEventToSqs($emailData);
                $smsData = $this->getOrderDataForSms($order, 'ORDER_CLOSED');
                // Additional data specific to 'ORDER_CLOSED' event
                $this->sqsEvent->sentSmsEventToSqs($smsData);
            } elseif ($order->getStatus() == 'pending' && $order->getState() == 'pending') {
                $emailData = $this->getOrderDataForEmail($order, 'ORDER_PENDING');
                $emailData['type'] = 'event';
                $this->sqsEvent->sentEmailEventToSqs($emailData);
            } elseif ($order->getStatus() == 'shipped') {
                $emailData = $this->getOrderDataForEmail($order, 'ORDER_SHIPPED');
                $emailData['type'] = 'event';
                $emailData['trackings'] = $this->getTrackingDetails($order);
                $this->sqsEvent->sentEmailEventToSqs($emailData);
            } elseif (strtolower($order->getStatus()) == 'delivered') {
                $emailData = $this->getOrderDataForEmail($order, 'ORDER_DELIVERED');
                $emailData['type'] = 'event';
                $emailData['trackings'] = $this->getTrackingDetails($order);
                $this->sqsEvent->sentEmailEventToSqs($emailData);
            }

            $this->restoreCouponUsages($order);
        } catch (Exception $exception) {
            $this->apiLogger->error($exception->getMessage() . __METHOD__);
        }
    }

    /**
     * Get Order Data For Email Event
     *
     * @param Order $order
     * @param string $eventName
     * @return array
     * @throws LocalizedException
     */
    private function getOrderDataForEmail(Order $order, string $eventName): array
    {
        $shippingAddress = $order->getShippingAddress();
        return [
            'type' => 'email',
            'event_name' => $eventName,
            'id' => $order->getId(),
            'name' => ucfirst($shippingAddress->getFirstname()) . " " . ucfirst($shippingAddress->getLastname()),
            'email' => $shippingAddress->getEmail(),
            'order_id' => $order->getIncrementId(),
            'phone_number' => $shippingAddress->getTelephone(),
            'payment_method' => $order->getPayment()->getMethodInstance()->getTitle(),
            'shipping_address' => $this->getShippingAddressData($shippingAddress),
            'order_placement_date' => $this->baseHelper
                ->getDateTimeBasedOnTimezone($order->getCreatedAt(), 'd/m/y H:i:s'),
            'items' => $this->getOrderItemsData($order),
            'mrp_total' => number_format($order->getMrpTotal(), 2),
            'bag_discount' => $order->getBagDiscount() ? number_format($order->getBagDiscount(), 2) : '0.0',
            'shipping_amount' => number_format($order->getDeliveryCharges() ? $order->getDeliveryCharges() : 0, 2),
            'discount' => $order->getBaseDiscountAmount() ? number_format($order->getBaseDiscountAmount(), 2) : '0.0',
            'prepaid_discount' => number_format($order->getPrepaidDiscount() ? $order->getPrepaidDiscount() : 0, 2),
            'grand_total_without_prepaid' => number_format(
                $order->getGrandTotalWithoutPrepaid()
                    ? $order->getGrandTotalWithoutPrepaid() : 0,
                2
            ),
            'grand_total' => number_format($order->getGrandTotal(), 2),
            'customer_id' => $order->getCustomerId() ? $order->getCustomerId() : '',
            'platform' => $order->getPlatform() ? $order->getPlatform() : null,
            'eligible_cashback' => $order->getEligibleCashback() ? $order->getEligibleCashback() : 0,
            'customerbalance' => number_format(
                $order->getCustomerBalanceAmount() ?
                    $order->getCustomerBalanceAmount() : 0,
                2
            ),
            'utm' => $this->getOrderUtmParamsData($order)
        ];
    }

    /**
     * Get Shipping Address Data
     *
     * @param Address $shippingAddress
     * @return string
     */
    private function getShippingAddressData(Address $shippingAddress): string
    {
        return implode(', ', $shippingAddress->getStreet()) . ", " . $shippingAddress->getCity() . ", "
            . $shippingAddress->getRegion() . " - " . $shippingAddress->getPostcode();
    }

    /**
     * Get Order Items Data
     *
     * @param Order $order
     * @return array
     * @throws NoSuchEntityException
     */
    private function getOrderItemsData(Order $order): array
    {
        $mediaBaseUrl = $this->storeManager->getStore()->getBaseUrl(UrlInterface::URL_TYPE_MEDIA);

        $orderItems = [];
        foreach ($order->getAllItems() as $item) {
            $product = $item->getProduct();
            $orderItems[] = [
                'image' => $mediaBaseUrl . 'catalog/product' . $product->getImage(),
                'name' => $item->getName(),
                'qty' => (int)$item->getQtyOrdered(),
                'cost' => $item->getBaseCost() ? number_format($item->getBaseCost(), 2) : 0,
                'price' => $item->getPrice() ? number_format($item->getPrice(), 2) : 0,
                'sku' => $item->getSku(),
                'brand' => $product->getCustomAttribute('brand') ?
                    $this->baseHelper->getProductAttributeLabel(
                        'brand',
                        $product->getCustomAttribute('brand')->getValue()
                    ) : "",
                'primary_l1_category' => $product->getCustomAttribute('primary_l1_category') ?
                    $this->baseHelper->getCategoryData(
                        $product->getCustomAttribute('primary_l1_category')->getValue()
                    ) : "",
                'primary_l2_category' => $product->getCustomAttribute('primary_l2_category') ?
                    $this->baseHelper->getCategoryData(
                        $product->getCustomAttribute('primary_l2_category')->getValue()
                    ) : "",
            ];
        }
        return $orderItems;
    }

    /**
     * Get Order UTM Params Data
     *
     * @param Order $order
     * @return array
     */
    private function getOrderUtmParamsData(Order $order): array
    {
        return [
            'utm_source' => $order->getUtmSource() ? $order->getUtmSource() : null,
            'utm_campaign' => $order->getUtmCampaign() ? $order->getUtmCampaign() : null,
            'utm_medium' => $order->getUtmMedium() ? $order->getUtmMedium() : null,
            'utm_term' => $order->getUtmTerm() ? $order->getUtmTerm() : null
        ];
    }

    /**
     * Get Order Data For Sms Event
     *
     * @param Order $order
     * @param string $eventName
     * @return array
     */
    private function getOrderDataForSms(Order $order, string $eventName): array
    {
        $shippingAddress = $order->getShippingAddress();

        return [
            'type' => 'sms',
            'event_name' => $eventName,
            'name' => $shippingAddress->getFirstname() . " " . $shippingAddress->getLastname(),
            'order_id' => $order->getIncrementId(),
            'phone_number' => $shippingAddress->getTelephone()
        ];
    }

    /**
     * Get Order Data For Food Darzee Email Event
     *
     * @param Order $order
     * @param string $eventName
     * @return array
     * @throws LocalizedException
     */
    private function getOrderDataForFoodDarzeeEmail(Order $order, string $eventName): array
    {
        $shippingAddress = $order->getShippingAddress();
        return [
            'type' => 'email',
            'event_name' => $eventName,
            'name' => ucfirst($shippingAddress->getFirstname()) . " " . ucfirst($shippingAddress->getLastname()),
            'email' => $shippingAddress->getEmail(),
            'order_id' => $order->getIncrementId(),
            'phone_number' => $shippingAddress->getTelephone(),
            'payment_method' => $order->getPayment()->getMethodInstance()->getTitle(),
            'shipping_address' => $this->getShippingAddressData($shippingAddress),
            'order_placement_date' => $this->baseHelper
                ->getDateTimeBasedOnTimezone($order->getCreatedAt(), 'd/m/y H:i:s'),
            'items' => $this->getOrderItemsDataForFoodDarzee($order),
            'customer_id' => $order->getCustomerId() ? $order->getCustomerId() : '',
            'platform' => $order->getPlatform() ? $order->getPlatform() : null
        ];
    }

    /**
     * Get Order Items Data For Food Darzee
     *
     * @param Order $order
     * @return array
     * @throws NoSuchEntityException
     */
    private function getOrderItemsDataForFoodDarzee(Order $order): array
    {
        $mediaBaseUrl = $this->storeManager->getStore()->getBaseUrl(UrlInterface::URL_TYPE_MEDIA);

        $orderItems = [];
        foreach ($order->getAllItems() as $item) {
            $product = $item->getProduct();
            $brand = $product->getCustomAttribute('brand') ?
                $this->baseHelper->getProductAttributeLabel(
                    'brand',
                    $product->getCustomAttribute('brand')->getValue()
                ) : "";
            if (!isset($brand) || $brand != 'Food Darzee') {
                continue;
            }
            $orderItems[] = [
                'image' => $mediaBaseUrl . 'catalog/product' . $product->getImage(),
                'name' => $item->getName(),
                'qty' => (int)$item->getQtyOrdered(),
                'cost' => $item->getBaseCost() ? number_format($item->getBaseCost(), 2) : 0,
                'price' => $item->getPrice() ? number_format($item->getPrice(), 2) : 0,
                'sku' => $item->getSku(),
                'brand' => $brand,
                'primary_l1_category' => $product->getCustomAttribute('primary_l1_category') ?
                    $this->baseHelper->getCategoryData(
                        $product->getCustomAttribute('primary_l1_category')->getValue()
                    ) : "",
                'primary_l2_category' => $product->getCustomAttribute('primary_l2_category') ?
                    $this->baseHelper->getCategoryData(
                        $product->getCustomAttribute('primary_l2_category')->getValue()
                    ) : "",
            ];
        }
        return $orderItems;
    }

    /**
     * Get Tracking Details
     *
     * @param Order $order
     * @return array
     */
    private function getTrackingDetails(Order $order): array
    {
        $trackingDetails = [];

        foreach ($order->getShipmentsCollection() as $shipment) {
            foreach ($shipment->getAllTracks() as $track) {
                $trackingDetails[] = [
                    'title' => $track->getTitle(),
                    'tracking_number' => $track->getTrackNumber()
                ];
            }
        }

        return $trackingDetails;
    }

    /**
     * Restore Coupon Usages on order cancel or payment failed
     *
     * @param Order $order
     * @return void
     */
    private function restoreCouponUsages(Order $order): void
    {
        $cancelledOrderStatus = ['canceled', 'payment_failed'];
        if (in_array($order->getStatus(), $cancelledOrderStatus)) {
            $this->updateCouponUsages->execute($order, false);

            if ($order->getAppliedRuleIds()) {
                $appliedRuleIds = explode(',', $order->getAppliedRuleIds());
                foreach (array_unique($appliedRuleIds) as $ruleId) {
                    if (!(int)$ruleId) {
                        continue;
                    }
                    $this->ruleUsageCounter->saveUsageCount(
                        $ruleId,
                        $this->ruleUsageCounter->getCountByRuleId($ruleId) - 1
                    );
                }
            }
            if ($code = $order->getCouponCode()) {
                $couponCodes = explode(',', $code);
                foreach ($couponCodes as $couponCode) {
                    $couponCode = trim($couponCode);
                    if (empty($couponCode)) {
                        continue;
                    }
                    $coupon = $this->couponFactory->create()->load($couponCode, 'code');
                    if ($coupon->getId()) {
                        $coupon->setTimesUsed($coupon->getTimesUsed() - 1);
                        $coupon->save();
                    }
                }
            }
        }
    }
}
