<?php

namespace Pratech\Recurring\Block\Adminhtml\Subscription\Tab\View;

use Magento\Framework\UrlInterface;
use Magento\Sales\Model\OrderFactory;
use Magento\Framework\Pricing\Helper\Data as FormatPrice;
use Pratech\Recurring\Model\Config\Source\Status as SubscriptionStatus;

/**
 * Adminhtml customer view personal information sales block.
 */
class SubscriptionInfo extends \Magento\Backend\Block\Template
{
    /**
     * @var Magento\Sales\Model\ProductFactory
     */
    protected $productFactory;

    /**
     * @var SubscriptionFactory
     */
    protected $subscriptionFactory;

    /**
     * @var Order
     */
    protected $orderFactory;

    /**
     * @var \Magento\Customer\Model\CustomerFactory
     */
    protected $customerFactory;

    /**
     * @var \Magento\Framework\Registry
     */
    protected $coreRegistry;
    /**
     * @var UrlInterface
     */
    protected $urlBuilder;

    /**
     * @var FormatPrice
     */
    private $priceHelper;

    /**
     * @var \Magento\Sales\Model\Order\Address\Renderer
     */
    private $addressRenderer;

    /**
     * @param \Magento\Backend\Block\Template\Context $context
     * @param \Magento\Framework\Registry $registry
     * @param UrlInterface $urlBuilder
     * @param \Magento\Catalog\Model\ProductFactory $productFactory
     * @param \Magento\Customer\Model\CustomerFactory $customerFactory
     * @param OrderFactory $orderFactory
     * @param FormatPrice $priceHelper
     * @param \Magento\Sales\Model\Order\Address\Renderer $addressRenderer
     * @param array $data
     */
    public function __construct(
        \Magento\Backend\Block\Template\Context $context,
        \Magento\Framework\Registry $registry,
        UrlInterface $urlBuilder,
        \Magento\Catalog\Model\ProductFactory $productFactory,
        \Magento\Customer\Model\CustomerFactory $customerFactory,
        OrderFactory $orderFactory,
        FormatPrice $priceHelper,
        \Magento\Sales\Model\Order\Address\Renderer $addressRenderer,
        array $data = []
    ) {
        parent::__construct($context, $data);
        $this->orderFactory = $orderFactory;
        $this->productFactory = $productFactory;
        $this->customerFactory = $customerFactory;
        $this->coreRegistry = $registry;
        $this->urlBuilder = $urlBuilder;
        $this->priceHelper = $priceHelper;
        $this->addressRenderer = $addressRenderer;
    }

    /**
     * Get subscription started date
     *
     * @return string
     */
    public function getStartDate()
    {
        return $this->formatDate(
            $this->getSubscription()->getStartDate(),
            \IntlDateFormatter::FULL,
            false
        );
    }

    /**
     * Get subscription data
     *
     * @return \Magento\Framework\Registry
     */
    public function getSubscription()
    {
        return $this->coreRegistry->registry('subscription_data');
    }
    
    /**
     * Get order url
     *
     * @return string
     */
    public function getOrder()
    {
        return $this->orderFactory->create()->load(
            $this->getSubscription()->getOrderId()
        );
    }

    /**
     * Get Formatted price
     *
     * @param int $price
     * @return string
     */
    public function getFormattedPrice($price)
    {
        return $this->priceHelper->currency($price);
    }

    /**
     * Get Formatted address
     *
     * @param array $address
     * @return string
     */
    public function getFormattedAddress($address)
    {
        return $this->addressRenderer->format($address, 'html');
    }

    /**
     * Get order url
     *
     * @return string
     */
    public function getOrderUrl()
    {
        return $this->urlBuilder->getUrl(
            'sales/order/view',
            ['order_id' => $this->getSubscription()->getOrderId()]
        );
    }

    /**
     * Get order id
     *
     * @return string
     */
    public function getOrderId()
    {
        return '#'.$this->orderFactory->create()->load(
            $this->getSubscription()->getOrderId()
        )->getIncrementId();
    }

    /**
     * Get Product
     *
     * @return \Magento\Catalog\Model\ProductFactory
     */
    public function getProduct()
    {
        return $this->productFactory->create()->load(
            $this->getSubscription()->getProductId()
        );
    }

    /**
     * Get order url
     *
     * @return string
     */
    public function getProductUrl()
    {
        return $this->urlBuilder->getUrl(
            'catalog/product/edit',
            ['id' => $this->getSubscription()->getProductId()]
        );
    }

    /**
     * Get customer
     *
     * @return \Magento\Customer\Model\Customer
     */
    public function getCustomer()
    {
        return $this->customerFactory->create()->load(
            $this->getSubscription()->getCustomerId()
        );
    }

    /**
     * Get customer edit url
     *
     * @param int $id
     * @return string
     */
    public function getCustomerUrl($id)
    {
        return $this->urlBuilder->getUrl(
            'customer/index/edit',
            ['id' => $id]
        );
    }

    /**
     * Get frequency of Subscription
     *
     * @return string
     */
    public function getRecurringProductFrequency()
    {
        $frequency = "Every " . $this->getSubscription()->getDuration()
            . " " . $this->getSubscription()->getDurationType() . "s";
        return $frequency;
    }

    /**
     * Get name of Subscription
     *
     * @return string
     */
    public function getFrequencyMaxRepeat()
    {
        $maxRepeat = $this->getSubscription()->getMaxRepeat() . " times";
        return $maxRepeat;
    }

    /**
     * Get Subscription charge
     *
     * @return string
     */
    public function getSubscriptionCharge()
    {
        $subscriptionCharge = $this->getSubscription()->getLockedPrice();
        return $this->priceHelper->currency($subscriptionCharge);
    }

    /**
     * Get subscription creation date
     *
     * @return string
     */
    public function getCreateDate()
    {
        return $this->formatDate(
            $this->getSubscription()->getCreatedAt(),
            \IntlDateFormatter::FULL,
            false
        );
    }

    /**
     * Get Status
     *
     * @param boolean $status
     * @return string
     */
    public function getStatus($status)
    {
        if ($status == SubscriptionStatus::ENABLED) {
            return __("Subscribed");
        }
        return __("UnSubscribed");
    }

    /**
     * Get subscription next date
     *
     * @return string
     */
    public function getValidTill()
    {
        return $this->formatDate(
            $this->getSubscription()->getValidTill(),
            \IntlDateFormatter::FULL,
            false
        );
    }
}
