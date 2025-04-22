<?php

namespace Pratech\Recurring\Helper;

use Exception;
use Magento\Sales\Model\Order as SalesOrder;
use Magento\Sales\Model\OrderFactory;
use Magento\Store\Model\App\Emulation;
use Pratech\Recurring\Model\Subscription;

/**
 * Pratech Recurring Helper Order
 */
class Order extends \Magento\Framework\App\Helper\AbstractHelper
{
    /**
     * This variable is set the store scope for order
     *
     * @var Magento\Store\Model\App\Emulation;
     */
    private $emulate;

    /**
     * @var \Magento\Framework\Json\Helper\Data
     */
    private $jsonHelper;
    /**
     * @var Magento\Catalog\Model\ProductFactory
     */
    private $productFactory;
    /**
     * @var Magento\Quote\Api\CartRepositoryInterface
     */
    private $cartRepositoryInterface;
    /**
     * @var Magento\Quote\Api\CartManagementInterface
     */
    private $cartManagementInterface;
    /**
     * @var Magento\Customer\Api\CustomerRepositoryInterface
     */
    private $customerRepository;
    /**
     * @var OrderFactory
     */
    private $orderFactory;
    /**
     * @var \Pratech\Base\Logger\CronLogger;
     */
    protected $cronLogger;
    /**
     * @var \Magento\Quote\Api\CartRepositoryInterface
     */
    protected $quoteRepository;

    /**
     * @param \Magento\Framework\App\Helper\Context $context
     * @param Emulation $emulate
     * @param Magento\Catalog\Model\ProductFactory $productFactory
     * @param Magento\Quote\Api\CartRepositoryInterface $cartRepositoryInterface
     * @param Magento\Quote\Api\CartManagementInterface $cartManagementInterface
     * @param \Magento\Framework\Json\Helper\Data $jsonHelper
     * @param Magento\Customer\Api\CustomerRepositoryInterface $customerRepository
     * @param OrderFactory $orderFactory
     * @param \Pratech\Base\Logger\CronLogger $cronLogger
     * @param \Magento\Quote\Api\CartRepositoryInterface $quoteRepository
     */
    public function __construct(
        \Magento\Framework\App\Helper\Context $context,
        Emulation $emulate,
        \Magento\Catalog\Model\ProductFactory $productFactory,
        \Magento\Quote\Api\CartRepositoryInterface $cartRepositoryInterface,
        \Magento\Quote\Api\CartManagementInterface $cartManagementInterface,
        \Magento\Framework\Json\Helper\Data $jsonHelper,
        \Magento\Customer\Api\CustomerRepositoryInterface $customerRepository,
        OrderFactory $orderFactory,
        \Pratech\Base\Logger\CronLogger $cronLogger,
        \Magento\Quote\Api\CartRepositoryInterface $quoteRepository
    ) {
        $this->emulate                  = $emulate;
        $this->jsonHelper               = $jsonHelper;
        $this->productFactory           = $productFactory;
        $this->cartRepositoryInterface  = $cartRepositoryInterface;
        $this->cartManagementInterface  = $cartManagementInterface;
        $this->customerRepository       = $customerRepository;
        $this->orderFactory             = $orderFactory;
        $this->cronLogger = $cronLogger;
        $this->quoteRepository = $quoteRepository;
        parent::__construct($context);
    }
    
    /**
     * Get product
     *
     * @param integer $productId
     * @return \Magento\Catalog\Model\ProductFactory
     */
    private function getProduct($productId)
    {
        return $this->productFactory->create()->load($productId);
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
        try {
            $storeId = $order->getStoreId();
            $cartId = $this->cartManagementInterface->createEmptyCart(); //Create empty cart
            $quote = $this->cartRepositoryInterface->get($cartId); // load empty cart quote
           
            $quote->setStoreId($storeId);
            $environment  = $this->emulate->startEnvironmentEmulation($storeId);
    
            $customerId = $order->getCustomerId();
            
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
                $product = $this->getProduct($item->getProductId());
                $product->setPrice($subscription->getLockedPrice());
                $quote->addProduct($product, (int)($subscription->getProductQty()));
            }
    
            $cartData = $quote->getAllVisibleItems();
            foreach ($cartData as $item) {
                $item->addOption(
                    [
                        'product_id' => $item->getProductId(),
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
            $orderId = $this->cartManagementInterface->placeOrder($quote->getId());
            $createdOrder = $this->orderFactory->create()->load($orderId);
            
            $createdOrder->setEmailSent(0);
            
            if ($createdOrder->getEntityId()) {
                $result =   [
                    'error' => 0,
                    'order_id' => $createdOrder->getRealOrderId(),
                    'id' => $createdOrder->getId()
                ];
            } else {
                $result =   [
                    'error' => 1,
                    'msg' => __('Error occured while creating subscription order.')
                ];
            }
            $this->emulate->stopEnvironmentEmulation($environment);
        } catch (Exception $e) {
            $this->cronLogger->error($e->getMessage() . __METHOD__);
        }
        return $result;
    }
}
