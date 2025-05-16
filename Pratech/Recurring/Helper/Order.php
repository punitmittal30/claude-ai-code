<?php
/**
 * Pratech_Recurring
 *
 * PHP version 8.x
 *
 * @category  PHP
 * @package   Pratech\Recurring
 * @author    Akash Panwar <akash.panwarr@pratechbrands.com>
 * @copyright 2025 Copyright (c) Pratech Brands Private Limited
 * @link      https://pratechbrands.com/
 **/
namespace Pratech\Recurring\Helper;

use Exception;
use Magento\Catalog\Api\Data\ProductInterface;
use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Customer\Api\CustomerRepositoryInterface;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\Json\Helper\Data as JsonHelper;
use Magento\Framework\Stdlib\DateTime\DateTime;
use Magento\Sales\Model\Order as SalesOrder;
use Magento\Store\Model\ScopeInterface;
use Magento\Store\Model\App\Emulation;
use Magento\Quote\Api\CartManagementInterface;
use Magento\Quote\Api\CartRepositoryInterface;
use Magento\Quote\Api\CartTotalRepositoryInterface;
use Magento\Sales\Api\OrderRepositoryInterface;
use Pratech\Base\Logger\CronLogger;
use Pratech\Order\Helper\Order as OrderHelper;
use Pratech\Recurring\Helper\Recurring as RecurringHelper;
use Pratech\Recurring\Model\Subscription;
use Pratech\StoreCredit\Helper\Data as StoreCreditHelper;

/**
 * Pratech Recurring Helper Order
 */
class Order
{
    /**
     * @param Emulation $emulate
     * @param ProductRepositoryInterface $productRepository
     * @param CartRepositoryInterface $cartRepositoryInterface
     * @param CartManagementInterface $cartManagementInterface
     * @param CartTotalRepositoryInterface $cartTotalRepository
     * @param JsonHelper $jsonHelper
     * @param DateTime $date
     * @param CustomerRepositoryInterface $customerRepository
     * @param ScopeConfigInterface $scopeConfig
     * @param OrderRepositoryInterface $orderRepository
     * @param CronLogger $cronLogger
     * @param StoreCreditHelper $storeCreditHelper
     */
    public function __construct(
        private Emulation $emulate,
        private ProductRepositoryInterface $productRepository,
        private CartRepositoryInterface $cartRepositoryInterface,
        private CartManagementInterface $cartManagementInterface,
        private CartTotalRepositoryInterface $cartTotalRepository,
        private JsonHelper $jsonHelper,
        private DateTime $date,
        private CustomerRepositoryInterface $customerRepository,
        private ScopeConfigInterface $scopeConfig,
        private OrderRepositoryInterface $orderRepository,
        private CronLogger $cronLogger,
        private StoreCreditHelper $storeCreditHelper
    ) {
    }

    /**
     * Get Product By SKU
     *
     * @param  string $sku
     * @return ProductInterface
     * @throws NoSuchEntityException
     */
    public function getProduct(string $sku): ProductInterface
    {
        return $this->productRepository->get($sku);
    }
    
    /**
     * Create Order On Your Store
     *
     * @param SalesOrder $order
     * @param Subscription $subscription
     * @return array
     */
    public function createMageOrder(SalesOrder $order, Subscription $subscription)
    {
        $result = [];
        try {
            $storeId = $order->getStoreId();
            $cartId = $this->cartManagementInterface->createEmptyCart(); //Create empty cart
            $quote = $this->cartRepositoryInterface->get($cartId); // load empty cart quote
           
            $quote->setStoreId($storeId);
            $environment  = $this->emulate->startEnvironmentEmulation($storeId);
    
            $customerId = $subscription->getCustomerId();
            
            $shippingAddress = ($order->getShippingAddress() && count($order->getShippingAddress()->getData())) ?
                                $order->getShippingAddress() :
                                $order->getBillingAddress();
            $billingAddress = $order->getBillingAddress();
            // if you have allready buyer id then you can load customer directly
            $customer = $this->customerRepository->getById($customerId);
            // if you have allready buyer id then you can load customer directly
            
            $quote->setCurrency();
            $quote->assignCustomer($customer); //Assign quote to customer
    
            $additionalOptions [] = [
                'label' => __("Subscription"),
                'value' => "Every " . $subscription->getDuration() . " " . $subscription->getDurationType() . "s"
            ];
            //add items in quote
            foreach ($order->getAllVisibleItems() as $item) {
                if ($item->getItemId() == $subscription->getOrderItemId()) {
                    $product = $this->getProduct($subscription->getProductSku());
                    $product->setPrice($subscription->getLockedPrice());
                    $quote->addProduct($product, (int)($subscription->getProductQty()));
                }
            }
    
            $cartData = $quote->getAllVisibleItems();
            foreach ($cartData as $cartItem) {
                $cartItem->addOption(
                    [
                        'product_id' => $cartItem->getProductId(),
                        'code' => 'custom_additional_options',
                        'value' => $this->jsonHelper->jsonEncode($additionalOptions)
                    ]
                );
            }
            
            //Set Address to quote
            $quote->getBillingAddress()->addData($billingAddress->getData());
            $quote->getShippingAddress()->addData($shippingAddress->getData());
     
            // Collect Rates and Set Shipping & Payment Method
     
            $paymentMethod = $subscription->getPaymentCode();
            $shippingAddress = $quote->getShippingAddress();
            $shippingAddress->setCollectShippingRates(true)
                            ->collectShippingRates()
                            ->setShippingMethod($order->getShippingMethod()); //shipping method
            $quote->setPaymentMethod($paymentMethod); //payment method
            $quote->setInventoryProcessed(false); //not effect inventory
            
            $quote->setCustomerIsGuest(0);
            // Set Sales Order Payment
            $quote->getPayment()->importData(['method' => $paymentMethod]);
            $quote->save(); //Now Save quote and your quote is ready
            
            // Collect Totals
            $quote->collectTotals();

            // Create Order From Quote
            $result = $this->placeCustomerOrder($quote->getId(), $paymentMethod);
            $this->emulate->stopEnvironmentEmulation($environment);
        } catch (Exception $e) {
            $this->cronLogger->error($e->getMessage() . __METHOD__);
            $result['msg'] = $e->getMessage();
        }
        return $result;
    }

    /**
     * Place Customer Order
     *
     * @param int $cartId
     * @param string $paymentCode
     * @return array
     * @throws CouldNotSaveException
     * @throws LocalizedException
     */
    public function placeCustomerOrder(
        int $cartId,
        string $paymentCode
    ): array {
        $eligibleCashbackAmount = 0;
        $isCashbackEnabled = $this->scopeConfig->getValue(
            RecurringHelper::IS_CASHBACK_ENABLED,
            ScopeInterface::SCOPE_STORE
        );
        if ($isCashbackEnabled) {
            try {
                $totals = $this->cartTotalRepository->get($cartId);
                $eligibleCashbackAmount = $this->storeCreditHelper->getCashbackAmount($totals);
            } catch (Exception $exception) {
                $this->cronLogger->error($exception->getMessage()  . __METHOD__);
            }
        }
        $orderId = $this->cartManagementInterface->placeOrder($cartId);
        $order = $this->orderRepository->get($orderId);
        $orderTotal = $order->getGrandTotal();
        switch ($paymentCode) {
            case "free":
            case "cashondelivery":
                $isEnable = $this->scopeConfig->getValue(
                    OrderHelper::COD_VERIFICATION_STATUS,
                    ScopeInterface::SCOPE_STORE
                );
                $codThreshold = $this->scopeConfig->getValue(
                    OrderHelper::COD_CONFIRM_THRESHOLD,
                    ScopeInterface::SCOPE_STORE
                );
                if (!$isEnable || $orderTotal < $codThreshold) {
                    $order->setStatus(SalesOrder::STATE_PROCESSING)
                        ->setState(SalesOrder::STATE_PROCESSING);
                    $order->addCommentToStatusHistory("System: Processing(processing)");
                } else {
                    $order->setStatus(OrderHelper::STATUS_PENDING)
                        ->setState(OrderHelper::STATUS_PENDING);
                    $order->addCommentToStatusHistory("System: Pending(pending)");
                }
                break;
            case "prepaid_dpanda":
                $order->setStatus(SalesOrder::STATE_PROCESSING)
                    ->setState(SalesOrder::STATE_PROCESSING);
                $order->addCommentToStatusHistory("DPanda: Processing(processing)");
                break;
            default:
                $order->setStatus(SalesOrder::STATE_PAYMENT_REVIEW)
                    ->setState(SalesOrder::STATE_PAYMENT_REVIEW);
                $order->addCommentToStatusHistory("System: Payment Review(payment_review)");
        }
        $order->setEstimatedDeliveryDate($this->getClickPostEdd($order->getShippingAddress()->getPostcode()));
        $order->setEligibleCashback($eligibleCashbackAmount);
        $this->orderRepository->save($order);
        if ($orderId) {
            return [
                'error' => 0,
                'order_id' => $orderId,
            ];
        }
        return [
            'error' => 1,
            'msg' => __('Error occured while creating subscription order.')
        ];
    }

    /**
     * Get Estimated Delivery Date
     *
     * @param string $postcode
     * @return string
     */
    private function getClickPostEdd(string $postcode): string
    {
        $days = OrderHelper::MAX_DELIVERY_DATE;
        $date = $this->date->date('Y-m-d');
        return $this->date->date('Y-m-d', strtotime($date . " +" . $days . "days"));
    }
}
