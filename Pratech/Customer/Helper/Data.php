<?php
/**
 * Pratech_Customer
 *
 * PHP version 8.x
 *
 * @category  PHP
 * @package   Pratech\Customer
 * @author    Puneet Mittal <puneet.mittal@pratechbrands.com>
 * @copyright 2022 Copyright (c) Pratech Brands Private Limited
 * @link      https://pratechbrands.com/
 **/

namespace Pratech\Customer\Helper;

use DateTime;
use Exception;
use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Customer\Api\AddressRepositoryInterface;
use Magento\Customer\Api\AddressRepositoryInterfaceFactory;
use Magento\Customer\Api\CustomerRepositoryInterface;
use Magento\Customer\Api\Data\AddressInterface;
use Magento\Framework\Api\SearchCriteriaInterface;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Stdlib\DateTime\TimezoneInterface;
use Magento\Sales\Api\Data\OrderAddressInterface;
use Magento\Sales\Api\Data\OrderItemInterface;
use Magento\Sales\Api\Data\OrderPaymentInterface;
use Magento\Sales\Api\OrderRepositoryInterface;
use Magento\Sales\Model\ResourceModel\Order\CollectionFactory as OrderCollectionFactory;
use Magento\Store\Model\ScopeInterface;
use Pratech\Base\Logger\Logger;
use Pratech\Catalog\Helper\Product as ProductHelper;
use Pratech\Customer\Model\ResourceModel\BlockedCustomers\Collection;
use Pratech\Customer\Model\ResourceModel\BlockedCustomers\CollectionFactory;

/**
 * Customer Helper Class
 */
class Data
{
    /**
     * PURCHASED PRODUCTS CAROUSEL TITLE CONFIGURATION PATH
     */
    public const PURCHASED_PRODUCTS_CAROUSEL_TITLE = 'product/purchased_products_carousel/carousel_title';

    /**
     * NO OF PRODUCTS TO SHOW IN CAROUSEL CONFIGURATION PATH
     */
    public const NO_OF_PRODUCTS_TO_SHOW_IN_CAROUSEL = 'product/purchased_products_carousel/no_of_products_in_carousel';

    /**
     * Customer Helper Constructor
     *
     * @param OrderRepositoryInterface $orderRepository
     * @param ProductRepositoryInterface $productRepository
     * @param AddressRepositoryInterfaceFactory $addressRepositoryInterfaceFactory
     * @param TimezoneInterface $timezone
     * @param ScopeConfigInterface $scopeConfig
     * @param CustomerRepositoryInterface $customerRepository
     * @param \Pratech\Base\Helper\Data $baseHelper
     * @param Logger $apiLogger
     * @param OrderCollectionFactory $orderCollectionFactory
     * @param ProductHelper $productHelper
     * @param CollectionFactory $blockedCustomersCollectionFactory
     */
    public function __construct(
        private OrderRepositoryInterface          $orderRepository,
        private ProductRepositoryInterface        $productRepository,
        private AddressRepositoryInterfaceFactory $addressRepositoryInterfaceFactory,
        private TimezoneInterface                 $timezone,
        private ScopeConfigInterface              $scopeConfig,
        private CustomerRepositoryInterface       $customerRepository,
        private \Pratech\Base\Helper\Data         $baseHelper,
        private Logger                            $apiLogger,
        private OrderCollectionFactory            $orderCollectionFactory,
        private ProductHelper                     $productHelper,
        private CollectionFactory                 $blockedCustomersCollectionFactory
    )
    {
    }

    /**
     * Get Customer Orders
     *
     * @param SearchCriteriaInterface $searchCriteria
     * @return array
     */
    public function getCustomerOrders(SearchCriteriaInterface $searchCriteria): array
    {
        $orderData = [];
        $deliveryDate = null;
        $orders = $this->orderRepository->getList($searchCriteria);
        foreach ($orders->getItems() as $order) {
            $orderStatus = "";
            try {
                $orderStatus = $order->getStatusLabel();
            } catch (LocalizedException $exception) {
                $this->apiLogger->error($exception->getMessage() . __METHOD__);
            }
            if ($order->getEstimatedDeliveryDate()) {
                $deliveryDate = $this->getTimeBasedOnTimezone($order->getEstimatedDeliveryDate());
            }
            $orderData[] = [
                "entity_id" => $order->getEntityId(),
                "increment_id" => $order->getIncrementId(),
                "created_at" => $this->getTimeBasedOnTimezone($order->getCreatedAt()),
                "updated_at" => $this->getTimeBasedOnTimezone($order->getUpdatedAt()),
                "grand_total" => $order->getGrandTotal(),
                "quote_id" => $order->getQuoteId(),
                "state" => $order->getState(),
                "status" => $orderStatus,
                "status_code" => $order->getStatus(),
                "total_item_count" => $order->getTotalItemCount(),
                "total_qty_ordered" => $order->getTotalQtyOrdered(),
                "total_due" => $order->getTotalDue(),
                "delivery_date" => $deliveryDate,
                "items" => $this->getOrderItems($order->getItems()),
                "address" => $this->getOrderBillingAddress($order->getBillingAddress()),
                "payment" => $this->getPaymentInformation($order->getPayment()),
                "earned_cashback" => $order->getEligibleCashback() ? $order->getEligibleCashback() : 0,
            ];
        }
        return [
            "items" => $orderData,
            "total_count" => $orders->getTotalCount(),
            "search_criteria" => $this->getSearchCriteria($orders->getSearchCriteria())
        ];
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
            return $this->timezone->date(new DateTime($date), $locale)->format('j M, o');
        } catch (Exception $e) {
            $this->apiLogger->error($e->getMessage() . __METHOD__);
            return "";
        }
    }

    /**
     * Get Order Items
     *
     * @param array $orderItems
     * @return array
     */
    private function getOrderItems(array $orderItems): array
    {
        $items = [];
        /** @var OrderItemInterface $orderItem */
        foreach ($orderItems as $orderItem) {
            $items[] = [
                "item_id" => $orderItem->getItemId(),
                "sku" => $orderItem->getSku(),
                "name" => $orderItem->getName(),
                "image" => $this->getProductImage($orderItem->getSku()),
                "short_description" => $this->getProductDescription($orderItem->getSku()),
                "slug" => $this->getProductSlug($orderItem->getSku()),
                "order_id" => $orderItem->getOrderId(),
                "product_id" => $orderItem->getProductId(),
                "product_type" => $orderItem->getProductType(),
                "qty_ordered" => $orderItem->getQtyOrdered(),
                "qty_refunded" => $orderItem->getQtyRefunded(),
                "qty_shipped" => $orderItem->getQtyShipped(),
                "quote_item_id" => $orderItem->getQuoteItemId(),
                "original_price" => $orderItem->getOriginalPrice(),
                "price" => $orderItem->getPrice(),
                "row_total" => $orderItem->getRowTotal(),
                "estimated_delivery_time" => $orderItem->getEstimatedDeliveryTime(),
                "warehouse_code" => $orderItem->getWarehouseCode()
            ];
        }
        return $items;
    }

    /**
     * Get Product Image
     *
     * @param string $sku
     * @return null|string
     */
    private function getProductImage(string $sku): ?string
    {
        try {
            return $this->productRepository->get($sku)->getImage();
        } catch (Exception $exception) {
            $this->apiLogger->error($exception->getMessage() . __METHOD__);
            return "";
        }
    }

    /**
     * Get Product Description
     *
     * @param string $sku
     * @return mixed|string
     */
    private function getProductDescription(string $sku): mixed
    {
        try {
            $shortDescription = $this->productRepository->get($sku)->getCustomAttribute('short_description');
            return $shortDescription ? $shortDescription->getValue() : "";
        } catch (Exception $exception) {
            $this->apiLogger->error($exception->getMessage() . __METHOD__);
            return "";
        }
    }

    /**
     * Get Product Slug
     *
     * @param string $sku
     * @return mixed|string
     */
    private function getProductSlug(string $sku): mixed
    {
        try {
            $slug = $this->productRepository->get($sku)->getCustomAttribute('url_key');
            return $slug ? $slug->getValue() : "";
        } catch (Exception $exception) {
            $this->apiLogger->error($exception->getMessage() . __METHOD__);
            return "";
        }
    }

    /**
     * Get Order Billing Address
     *
     * @param OrderAddressInterface|null $billingAddress
     * @return array
     */
    private function getOrderBillingAddress(OrderAddressInterface|null $billingAddress): array
    {
        return [
            "customer_address_type" => $billingAddress->getCustomerAddressType(),
            "city" => $billingAddress->getCity(),
            "country_id" => $billingAddress->getCountryId(),
            "email" => $billingAddress->getEmail(),
            "entity_id" => $billingAddress->getEntityId(),
            "firstname" => $billingAddress->getFirstname(),
            "lastname" => $billingAddress->getLastname(),
            "parent_id" => $billingAddress->getParentId(),
            "postcode" => $billingAddress->getPostcode(),
            "region" => $billingAddress->getRegion(),
            "region_code" => $billingAddress->getRegionCode(),
            "region_id" => $billingAddress->getRegionId(),
            "street" => $billingAddress->getStreet(),
            "telephone" => $billingAddress->getTelephone()
        ];
    }

    /**
     * Get Payment Information
     *
     * @param OrderPaymentInterface|null $payment
     * @return array
     */
    private function getPaymentInformation(OrderPaymentInterface|null $payment): array
    {
        return [
            "additional_information" => $payment->getAdditionalInformation(),
            "method" => $payment->getMethod(),
            "amount_ordered" => $payment->getAmountOrdered()
        ];
    }

    /**
     * Get Search Criteria
     *
     * @param SearchCriteriaInterface $searchCriteria
     * @return array
     */
    private function getSearchCriteria(SearchCriteriaInterface $searchCriteria): array
    {
        return [
            "current_page" => $searchCriteria->getCurrentPage(),
            "page_size" => $searchCriteria->getPageSize()
        ];
    }

    /**
     * Get Customer Address By Address ID
     *
     * @param int|null $addressId
     * @return array
     */
    public function getAddressByAddressId(?int $addressId): array
    {
        /** @var AddressRepositoryInterface $address */
        $address = $this->addressRepositoryInterfaceFactory->create();

        try {
            $customerAddress = $address->getById($addressId);
            return [
                'id' => $addressId,
                'firstname' => $customerAddress->getFirstname(),
                'lastname' => $customerAddress->getLastname(),
                'street' => $customerAddress->getStreet(),
                'city' => $customerAddress->getCity(),
                'pincode' => $customerAddress->getPostcode(),
                'contact_number' => $customerAddress->getTelephone()
            ];
        } catch (Exception $exception) {
            $this->apiLogger->error($exception->getMessage() . __METHOD__);
            return [];
        }
    }

    /**
     * Delete Address BY Address ID
     *
     * @param int $addressId
     * @return bool
     * @throws LocalizedException
     * @throws NoSuchEntityException
     */
    public function deleteAddressByAddressId(int $addressId): bool
    {
        return $this->getAddressRepository()->deleteById($addressId);
    }

    /**
     * Get Address Repository
     *
     * @return AddressRepositoryInterface
     */
    public function getAddressRepository(): AddressRepositoryInterface
    {
        return $this->addressRepositoryInterfaceFactory->create();
    }

    /**
     * Update Customer Address.
     *
     * @param AddressInterface $address
     * @return array
     * @throws LocalizedException
     */
    public function updateCustomerAddress(AddressInterface $address): array
    {
        $updatedAddress = $this->getAddressRepository()->save($address);
        return $this->formatAddress($updatedAddress);
    }

    /**
     * Format Address Response
     *
     * @param AddressInterface $address
     * @return array
     * @throws LocalizedException
     */
    public function formatAddress(AddressInterface $address): array
    {
        $isDefaultAddress = 0;
        $customerId = $address->getCustomerId();
        if ($customerId) {
            $defaultShippingAddressId = $this->customerRepository->getById($customerId)->getDefaultShipping();
            if ($address->getId() == $defaultShippingAddressId) {
                $isDefaultAddress = 1;
            }
        }
        $addressRepository = $this->getAddressRepository()->getById($address->getId());
        return [
            "id" => $addressRepository->getId(),
            "customer_id" => $addressRepository->getCustomerId(),
            "region" => [
                "region_code" => $addressRepository->getRegion()->getRegionCode(),
                "region" => $addressRepository->getRegion()->getRegion(),
                "region_id" => $addressRepository->getRegion()->getRegionId()
            ],
            "region_id" => $addressRepository->getRegionId(),
            "country_id" => $addressRepository->getCountryId(),
            "street" => $addressRepository->getStreet(),
            "company" => $addressRepository->getCompany(),
            "telephone" => $addressRepository->getTelephone(),
            "postcode" => $addressRepository->getPostcode(),
            "city" => $addressRepository->getCity(),
            "firstname" => $addressRepository->getFirstname(),
            "lastname" => $addressRepository->getLastname(),
            "default_billing" => $isDefaultAddress,
            "default_shipping" => $isDefaultAddress,
            'customer_address_type' => $addressRepository->getCustomAttribute('customer_address_type') ?
                $addressRepository->getCustomAttribute('customer_address_type')->getValue() : '',
            'email_address' => $addressRepository->getCustomAttribute('email_address') ?
                $addressRepository->getCustomAttribute('email_address')->getValue() : ''
        ];
    }

    /**
     * Get Customer Order By Order ID
     *
     * @param int $customerId
     * @param int $orderId
     * @return array
     * @throws Exception
     */
    public function getCustomerOrderById(int $customerId, int $orderId): array
    {
        $order = $this->orderRepository->get($orderId);
        if ($order->getCustomerId() == $customerId) {
            return $this->baseHelper->getOrderDetails($order);
        } else {
            throw new Exception(__('Customer is not authorized to access this resource'));
        }
    }

    /**
     * Get Customer Purchased Products By Order ID
     *
     * @param int $customerId
     * @param int|null $pincode
     * @return array
     */
    public function getCustomerPurchasedProducts(int $customerId, int $pincode = null): array
    {
        $result = [
            'name' => $this->getPurchasedProductsCarouselTitle(),
            'slug' => 'purchased-products',
            'products' => []
        ];
        try {
            $productIds = [];
            $maxNoOfProducts = $this->getNoOfProductsToShowInCarousel();
            $deliveredOrderCollection = $this->orderCollectionFactory->create()
                ->addFieldToFilter('customer_id', $customerId)
                ->addAttributeToSort('created_at', 'desc')
                ->addFieldToFilter('status', 'delivered');

            foreach ($deliveredOrderCollection as $order) {
                $orderItems = $order->getAllVisibleItems();
                foreach ($orderItems as $item) {
                    if (count($productIds) >= $maxNoOfProducts) {
                        break 2;
                    }
                    $productId = $item->getProductId();
                    if (!in_array($productId, $productIds)) {
                        $productIds[] = $productId;
                        try {
                            $formattedProduct = $this->productHelper->formatProductForCarousel($productId, $pincode);
                            if (!empty($formattedProduct)) {
                                $result['products'][] = $formattedProduct;
                            }
                        } catch (LocalizedException $exception) {
                            continue;
                        }
                    }
                }
            }

            usort($result['products'], function ($a, $b) {
                return (
                    $a['stock_info']['is_in_stock'] === $b['stock_info']['is_in_stock'])
                    ? 0 : ($a['stock_info']['is_in_stock'] ? -1 : 1
                    );
            });
            $result['product_count'] = count($productIds);
        } catch (LocalizedException $exception) {
            $this->apiLogger->error($exception->getMessage() . " | " . __METHOD__);
        }
        return $result;
    }

    /**
     * Get Purchased Products Carousel Title.
     *
     * @return mixed
     */
    public function getPurchasedProductsCarouselTitle(): mixed
    {
        return $this->scopeConfig->getValue(
            self::PURCHASED_PRODUCTS_CAROUSEL_TITLE,
            ScopeInterface::SCOPE_STORE
        );
    }

    /**
     * Get No of Products In Carousel.
     *
     * @return mixed
     */
    public function getNoOfProductsToShowInCarousel(): mixed
    {
        return $this->scopeConfig->getValue(
            self::NO_OF_PRODUCTS_TO_SHOW_IN_CAROUSEL,
            ScopeInterface::SCOPE_STORE
        );
    }

    /**
     * Get Customer Orders
     *
     * @param SearchCriteriaInterface $searchCriteria
     * @return array
     */
    public function getOrderHistory(SearchCriteriaInterface $searchCriteria): array
    {
        $orderData = [];
        $deliveryDate = null;
        $orders = $this->orderRepository->getList($searchCriteria);
        foreach ($orders->getItems() as $order) {
            $orderStatus = "";
            try {
                $orderStatus = $order->getStatusLabel();
            } catch (LocalizedException $exception) {
                $this->apiLogger->error($exception->getMessage() . __METHOD__);
            }
            if ($order->getEstimatedDeliveryDate()) {
                $deliveryDate = $this->getTimeBasedOnTimezone($order->getEstimatedDeliveryDate());
            }
            $orderData[] = [
                "entity_id" => $order->getEntityId(),
                "increment_id" => $order->getIncrementId(),
                "created_at" => $this->getTimeBasedOnTimezone($order->getCreatedAt()),
                "updated_at" => $this->getTimeBasedOnTimezone($order->getUpdatedAt()),
                "status" => $orderStatus,
                "status_code" => $order->getStatus(),
                "total_item_count" => $order->getTotalItemCount(),
                "total_qty_ordered" => $order->getTotalQtyOrdered(),
                "estimated_delivery_date" => $deliveryDate,
                "items" => $this->baseHelper->getCustomerOrderItems($order->getItems()),
                "shipments" => $this->baseHelper->getOrderShipment($order),
                "earned_cashback" => $order->getEligibleCashback() ? $order->getEligibleCashback() : 0
            ];
        }
        return [
            "items" => $orderData,
            "total_count" => $orders->getTotalCount(),
            "search_criteria" => $this->getSearchCriteria($orders->getSearchCriteria())
        ];
    }

    /**
     * Get Customer Order Detail By Order ID
     *
     * @param int $customerId
     * @param int $orderId
     * @return array
     * @throws Exception
     */
    public function viewOrderDetails(int $customerId, int $orderId): array
    {
        $order = $this->orderRepository->get($orderId);
        if ($order->getCustomerId() == $customerId) {
            $result = $this->baseHelper->getCustomerOrderDetails($order);
            return $result;
        } else {
            throw new Exception(__('Customer is not authorized to access this resource'));
        }
    }

    /**
     * Get Blocked Customer Data.
     *
     * @return array
     * @throws Exception
     */
    public function getBlockedCustomers(): array
    {
        /** @var Collection $collection */
        $collection = $this->blockedCustomersCollectionFactory->create();
        return $collection->getData();
    }
}
