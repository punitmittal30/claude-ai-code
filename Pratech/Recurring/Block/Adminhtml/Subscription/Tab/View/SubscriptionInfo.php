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
namespace Pratech\Recurring\Block\Adminhtml\Subscription\Tab\View;

use Magento\Backend\Block\Template\Context;
use Magento\Framework\Registry;
use Magento\Framework\UrlInterface;
use Magento\Catalog\Model\ProductFactory;
use Magento\Customer\Model\CustomerFactory;
use Magento\Sales\Model\OrderFactory;
use Magento\Sales\Model\Order\Address\Renderer;
use Magento\Framework\Pricing\Helper\Data as FormatPrice;
use Pratech\Recurring\Model\Config\Source\Status as SubscriptionStatus;

/**
 * Adminhtml customer view personal information sales block.
 */
class SubscriptionInfo extends \Magento\Backend\Block\Template
{
    /**
     * @param Context $context
     * @param Registry $coreRegistry
     * @param UrlInterface $urlBuilder
     * @param ProductFactory $productFactory
     * @param CustomerFactory $customerFactory
     * @param OrderFactory $orderFactory
     * @param FormatPrice $priceHelper
     * @param Renderer $addressRenderer
     * @param SubscriptionStatus $subscriptionStatus
     * @param array $data
     */
    public function __construct(
        Context $context,
        protected Registry $coreRegistry,
        protected UrlInterface $urlBuilder,
        protected ProductFactory $productFactory,
        protected CustomerFactory $customerFactory,
        protected OrderFactory $orderFactory,
        private FormatPrice $priceHelper,
        private Renderer $addressRenderer,
        private SubscriptionStatus $subscriptionStatus,
        array $data = []
    ) {
        parent::__construct($context, $data);
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
     * @return \Magento\Catalog\Model\Product
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
     * @param string $status
     * @return string
     */
    public function getStatus($status)
    {
        return $this->subscriptionStatus->getStatusLabel($status);
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
