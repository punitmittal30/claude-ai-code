<?php
/**
 * Pratech_ThirdPartyIntegration
 *
 * PHP version 8.x
 *
 * @category  PHP
 * @package   Pratech\ThirdPartyIntegration
 * @author    Puneet Mittal <puneet.mittal@pratechbrands.com>
 * @copyright 2024 Copyright (c) Pratech Brands Private Limited
 * @link      https://pratechbrands.com/
 **/

namespace Pratech\ThirdPartyIntegration\Helper;

use DateTime;
use Exception;
use Magento\Catalog\Api\Data\ProductInterface;
use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Catalog\Model\Category;
use Magento\Catalog\Model\Product\Attribute\Source\Status as ProductStatus;
use Magento\Catalog\Model\ResourceModel\Category\CollectionFactory as CategoryCollectionFactory;
use Magento\Catalog\Model\ResourceModel\Product\Collection;
use Magento\Catalog\Model\ResourceModel\Product\CollectionFactory;
use Magento\Catalog\Pricing\Price\FinalPrice;
use Magento\CatalogInventory\Api\Data\StockItemInterface;
use Magento\CatalogInventory\Api\StockRegistryInterface;
use Magento\Checkout\Api\Data\PaymentDetailsInterface;
use Magento\Checkout\Api\Data\ShippingInformationInterface;
use Magento\ConfigurableProduct\Api\Data\OptionInterface;
use Magento\ConfigurableProduct\Model\Product\Type\Configurable;
use Magento\ConfigurableProduct\Pricing\Price\ConfigurableOptionsProviderInterface;
use Magento\Customer\Api\CustomerRepositoryInterface;
use Magento\Customer\Model\ResourceModel\Customer\CollectionFactory as CustomerCollectionFactory;
use Magento\Eav\Model\Entity\Attribute;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\Exception\AuthorizationException;
use Magento\Framework\Exception\CouldNotSaveException;
use Magento\Framework\Exception\InputException;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Exception\State\InputMismatchException;
use Magento\Framework\Stdlib\DateTime\TimezoneInterface;
use Magento\Quote\Api\Data\CartItemInterface;
use Magento\Quote\Api\Data\PaymentInterface;
use Magento\Quote\Api\GuestCartManagementInterface;
use Magento\Quote\Api\GuestCartTotalRepositoryInterface;
use Magento\Sales\Api\Data\OrderInterface;
use Magento\Sales\Api\OrderRepositoryInterface;
use Magento\Sales\Model\Order as SalesOrder;
use Magento\Sales\Model\OrderFactory as SalesOrderFactory;
use Magento\Sales\Model\ResourceModel\Order\CollectionFactory as OrderCollectionFactory;
use Magento\Store\Model\ScopeInterface;
use Pratech\Base\Helper\Data;
use Pratech\ThirdPartyIntegration\Logger\DpandaLogger;
use Pratech\Cart\Api\GuestAddressManagementInterface;
use Pratech\Cart\Api\GuestCartInterface;
use Pratech\Catalog\Helper\Eav;
use Pratech\Order\Api\Data\CampaignInterface;
use Pratech\Order\Helper\Order;
use Pratech\StoreCredit\Helper\Data as StoreCreditHelper;

/**
 * External Order Helper Class
 */
class ExternalOrder
{
    /**
     * IS DPANDA ENABLED OR NOT.
     */
    public const IS_DPANDA_ENABLED = "third_party_integrations/dpanda/enable";

    /**
     * IS Limechat enabled or not.
     */
    public const IS_LIMECHAT_ENABLED = 'third_party_integrations/limechat/enable';

    /**
     * IS DPANDA ENABLED OR NOT.
     */
    public const BRANDS_CATEGORY_ID = "third_party_integrations/dpanda/brands_category_id";

    /**
     * Tracking Host Url Config Page
     */
    public const TRACKING_HOST_URL_CONFIG_PATH = 'customers/orders/tracking_host_url';

    /**
     * CUSTOM ATTRIBUTES CONFIGURATION PATH
     */
    public const CUSTOM_ATTRIBUTE_CONFIG_PATH = 'product/attributes/custom_attributes';

    /**
     * CONTENT ATTRIBUTES CONFIGURATION PATH
     */
    public const PRODUCT_INFORMATION_CONFIG_PATH = 'product/attributes/product_information';

    /**
     * CONTENT ATTRIBUTES CONFIGURATION PATH
     */
    public const ADDITIONAL_INFORMATION_CONFIG_PATH = 'product/attributes/additional_information';

    /**
     * Hyuga Frontend Url
     */
    public const HYUGA_FRONTEND_URL_CONFIG_PATH = 'third_party_integrations/limechat/hyuga_frontend_url';

    /**
     * Max Delivery Date Constant
     */
    public const MAX_DELIVERY_DATE = 4;

    /**
     * External Order Constructor
     *
     * @param GuestCartInterface $guestCart
     * @param GuestAddressManagementInterface $guestAddressManagement
     * @param ProductRepositoryInterface $productRepository
     * @param OrderRepositoryInterface $orderRepository
     * @param ScopeConfigInterface $scopeConfig
     * @param CategoryCollectionFactory $categoryCollectionFactory
     * @param SalesOrderFactory $salesOrderFactory
     * @param Order $orderHelper
     * @param Data $baseHelper
     * @param Eav $eavHelper
     * @param StockRegistryInterface $stockItemRepository
     * @param Configurable $configurable
     * @param TimezoneInterface $timezone
     * @param DpandaLogger $dpandaLogger
     * @param ConfigurableOptionsProviderInterface $configurableOptionsProvider
     * @param GuestCartManagementInterface $guestCartManagement
     * @param CustomerRepositoryInterface $customerRepository
     * @param \Magento\Framework\Stdlib\DateTime\DateTime $date
     * @param CollectionFactory $productCollectionFactory
     * @param Attribute $attribute
     * @param StoreCreditHelper $storeCreditHelper
     * @param GuestCartTotalRepositoryInterface $guestCartTotalRepository
     * @param CustomerCollectionFactory $customerCollectionFactory
     * @param OrderCollectionFactory $orderCollectionFactory
     */
    public function __construct(
        private GuestCartInterface                          $guestCart,
        private GuestAddressManagementInterface             $guestAddressManagement,
        private ProductRepositoryInterface                  $productRepository,
        private OrderRepositoryInterface                    $orderRepository,
        private ScopeConfigInterface                        $scopeConfig,
        private CategoryCollectionFactory                   $categoryCollectionFactory,
        private SalesOrderFactory                           $salesOrderFactory,
        private Order                                       $orderHelper,
        private Data                                        $baseHelper,
        private Eav                                         $eavHelper,
        private StockRegistryInterface                      $stockItemRepository,
        private Configurable                                $configurable,
        private TimezoneInterface                           $timezone,
        private DpandaLogger                                $dpandaLogger,
        private ConfigurableOptionsProviderInterface        $configurableOptionsProvider,
        private GuestCartManagementInterface                $guestCartManagement,
        private CustomerRepositoryInterface                 $customerRepository,
        private \Magento\Framework\Stdlib\DateTime\DateTime $date,
        private CollectionFactory                           $productCollectionFactory,
        private Attribute                                   $attribute,
        private StoreCreditHelper                           $storeCreditHelper,
        private GuestCartTotalRepositoryInterface           $guestCartTotalRepository,
        private CustomerCollectionFactory                   $customerCollectionFactory,
        private OrderCollectionFactory                      $orderCollectionFactory
    ) {
    }

    /**
     * Create Empty Cart
     *
     * @param string $platform
     * @return array
     * @throws AuthorizationException
     * @throws CouldNotSaveException
     */
    public function createEmptyCart(string $platform): array
    {
        if ($this->isAllowed($platform)) {
            return $this->guestCart->createEmptyCart();
        }
        throw new AuthorizationException(__('Unauthorized'));
    }

    /**
     * Is Allowed to interact with system.
     *
     * @param string $platform
     * @return bool
     */
    public function isAllowed(string $platform): bool
    {
        return match ($platform) {
            'dpanda' => $this->scopeConfig->getValue(self::IS_DPANDA_ENABLED),
            'limechat' => $this->scopeConfig->getValue(self::IS_LIMECHAT_ENABLED),
            default => false,
        };
    }

    /**
     * Add Item To Guest Cart For Third Party.
     *
     * @param string $platform
     * @param CartItemInterface $cartItem
     * @return array
     * @throws AuthorizationException
     * @throws NoSuchEntityException
     * @throws CouldNotSaveException
     * @throws InputException
     */
    public function addItemToGuestCart(string $platform, CartItemInterface $cartItem): array
    {
        if ($this->isAllowed($platform)) {
            return $this->guestCart->addItemToGuestCart($cartItem);
        }
        throw new AuthorizationException(__('Unauthorized'));
    }

    /**
     * Save Address Information For Third party.
     *
     * @param string $platform
     * @param string $cartId
     * @param ShippingInformationInterface $addressInformation
     * @return PaymentDetailsInterface
     * @throws AuthorizationException
     */
    public function saveAddressInformation(
        string                       $platform,
        string                       $cartId,
        ShippingInformationInterface $addressInformation
    ): PaymentDetailsInterface {
        if ($this->isAllowed($platform)) {
            return $this->guestAddressManagement->saveAddressInformation($cartId, $addressInformation);
        }
        throw new AuthorizationException(__('Unauthorized'));
    }

    /**
     * Get Scope Config.
     *
     * @param string $config
     * @return mixed
     */
    public function getConfig(string $config): mixed
    {
        return $this->scopeConfig->getValue(
            $config,
            ScopeInterface::SCOPE_STORE
        );
    }

    /**
     * Get Product Detail By Product ID For Third Party.
     *
     * @param string $platform
     * @param int $productId
     * @return array
     * @throws AuthorizationException
     * @throws NoSuchEntityException
     */
    public function getProductById(string $platform, int $productId): array
    {
        if ($this->isAllowed($platform)) {
            return $this->formatProduct($productId);
        }
        throw new AuthorizationException(__('Unauthorized'));
    }

    /**
     * Format Product Response For Carousels
     *
     * @param int $productId
     * @return array
     * @throws NoSuchEntityException
     */
    public function formatProduct(int $productId): array
    {
        $product = $this->productRepository->getById($productId);
        $productData = $this->getProductAttributes($product);

        if ($product->getTypeId() == 'configurable') {
            $productData['configurable_product_options'] = $this->getConfigurableProductOptions($product);
            $productData['price'] = $productData['configurable_product_options'][0]['values']['minimum_price'];
            if (isset($productData['configurable_product_options'][0]['values']['minimum_special_price'])) {
                $productData['special_price'] = $productData['configurable_product_options'][0]
                ['values']['minimum_special_price'];
            }
            $productData['default_variant_id'] = $this->getDefaultVariantId($product->getId());
        }

        $parentProductId = $this->getParentProductId($productId);

        if ($parentProductId) {
            $productData['parent_id'] = $parentProductId;
            $productData['parent_slug'] = $this->productRepository->getById($parentProductId)->getUrlKey();
        }

        return $productData;
    }

    /**
     * Get Product Attributes
     *
     * @param ProductInterface $product
     * @return array
     * @throws NoSuchEntityException
     */
    private function getProductAttributes(ProductInterface $product): array
    {
        $productStock = $this->getProductStockInfo($product->getId());
        $productData = [
            'id' => $product->getId(),
            'name' => $product->getName(),
            'status' => $product->getStatus(),
            'sku' => $product->getSku(),
            'price' => $product->getPrice(),
            'type' => $product->getTypeId(),
            'slug' => $product->getCustomAttribute('url_key')->getValue(),
            'image' => $product->getImage(),
            'created_at' => $this->getDateTimeBasedOnTimezone($product->getCreatedAt()),
            'updated_at' => $this->getDateTimeBasedOnTimezone($product->getUpdatedAt()),
            'description' => $product->getCustomAttribute('description')
                ? $product->getCustomAttribute('description')->getValue()
                : "",
            'product_information' => $this->eavHelper->getLabelValueFormat(
                $product,
                self::PRODUCT_INFORMATION_CONFIG_PATH
            ),
            'additional_information' => $this->eavHelper->getLabelValueFormat(
                $product,
                self::ADDITIONAL_INFORMATION_CONFIG_PATH
            ),
            'stock_info' => [
                'qty' => $productStock->getQty(),
                'min_sale_qty' => $productStock->getMinSaleQty(),
                'max_sale_qty' => $productStock->getMaxSaleQty(),
                'is_in_stock' => $productStock->getIsInStock() && $product->getStatus() == 1
            ],
            'media_gallery' => $this->getMediaGallery($product),
            'item_weight' => $product->getCustomAttribute('item_weight')
                ? $this->eavHelper->getOptionLabel(
                    'item_weight',
                    $product->getCustomAttribute('item_weight')->getValue()
                ) : ""
        ];

        if ($product->getCustomAttribute('special_price')) {
            $productData['special_price'] = $product->getCustomAttribute('special_price')->getValue();
            $productData['special_from_date_formatted'] = $product->getCustomAttribute('special_from_date')
                ? $this->getDateTimeBasedOnTimezone(
                    $product->getCustomAttribute('special_from_date')->getValue()
                )
                : "";
            $productData['special_to_date_formatted'] = $product->getCustomAttribute('special_to_date')
                ? $this->getDateTimeBasedOnTimezone(
                    $product->getCustomAttribute('special_to_date')->getValue()
                )
                : "";
        }

        $productData['custom_attributes'] = $this->eavHelper->getLabelValueFormat(
            $product,
            self::CUSTOM_ATTRIBUTE_CONFIG_PATH
        );

        return $productData;
    }

    /**
     * Get Product Stock Info
     *
     * @param int $productId
     * @return StockItemInterface
     */
    public function getProductStockInfo(int $productId): StockItemInterface
    {
        return $this->stockItemRepository->getStockItem($productId);
    }

    /**
     * Get Time Based On Timezone for Email
     *
     * @param string $date
     * @param string $format
     * @return string
     */
    public function getDateTimeBasedOnTimezone(string $date, string $format = 'Y-m-d H:i:s'): string
    {
        try {
            $locale = $this->scopeConfig->getValue(
                'general/locale/timezone',
                ScopeInterface::SCOPE_STORE
            );
            return $this->timezone->date(new DateTime($date), $locale)->format($format);
        } catch (Exception $e) {
            $this->dpandaLogger->error($e->getMessage() . __METHOD__);
            return "";
        }
    }

    /**
     * Get Product Media Gallery
     *
     * @param ProductInterface $product
     * @return array
     */
    private function getMediaGallery(ProductInterface $product): array
    {
        $mediaGallery = [];
        foreach ($product->getMediaGalleryEntries() as $mediaGalleryEntry) {
            $mediaGallery[] = $mediaGalleryEntry->getData();
        }
        return $mediaGallery;
    }

    /**
     * Get Configurable Product Options
     *
     * @param ProductInterface $product
     * @return array
     * @throws NoSuchEntityException
     */
    private function getConfigurableProductOptions(ProductInterface $product): array
    {
        $configurableOptions = [];

        /** @var OptionInterface[] $options */
        $options = $product->getExtensionAttributes()->getConfigurableProductOptions();

        foreach ($options as $option) {
            $configurableOptions[] = [
                'attribute_id' => $option->getAttributeId(),
                'label' => $option->getLabel(),
                'position' => $option->getPosition(),
                'product_id' => $option->getProductId(),
                'attribute_code' => $this->attribute->load($option->getAttributeId())->getAttributeCode(),
                'values' => $this->getConfigurableProductVariants($option->getAttributeId(), $product),
            ];
        }

        return $configurableOptions;
    }

    /**
     * Get Configurable Product Options
     *
     * @param ProductInterface $product
     * @return array
     * @throws NoSuchEntityException
     */
    private function getConfigurableProductOptionsOld(ProductInterface $product): array
    {
        $configurableOptions = [];

        /** @var OptionInterface[] $options */
        $options = $product->getExtensionAttributes()->getConfigurableProductOptions();

        foreach ($options as $option) {
            $configurableOptions[] = [
                'attribute_id' => $option->getAttributeId(),
                'label' => $option->getLabel(),
                'position' => $option->getPosition(),
                'product_id' => $option->getProductId(),
                'values' => $this->getConfigurableProductVariants($option->getAttributeId(), $product),
            ];
        }

        return $configurableOptions;
    }

    /**
     * Get Configurable Product Variants Data.
     *
     * @param string|null $optionId
     * @param ProductInterface $product
     * @return array
     * @throws NoSuchEntityException
     */
    private function getConfigurableProductVariants(?string $optionId, ProductInterface $product): array
    {
        $variants = [];
        $minimumRegularPrice = $minimumSpecialPrice = 0;
        /** @var Configurable $typeInstance */
        $typeInstance = $product->getTypeInstance();
        $configurableOptions = $typeInstance->getConfigurableOptions($product);
        foreach ($configurableOptions as $key => $configurableOption) {
            if ($optionId == $key) {
                $configurableVariant = [];
                foreach ($configurableOption as $variant) {
                    $simpleProduct = $this->productRepository->get($variant['sku']);
                    if ($simpleProduct->getStatus() != ProductStatus::STATUS_ENABLED) {
                        continue;
                    }
                    $productStock = $this->getProductStockInfo($simpleProduct->getId());
                    $variant = array_merge($variant, $this->getProductAttributes($simpleProduct));
                    $variant['option_id'] = $optionId;
                    $variant['product_id'] = $simpleProduct->getId();
                    $variant['stock_status'] = $productStock->getIsInStock();
                    $variant['media_gallery'] = $this->getMediaGallery($simpleProduct);

                    if ($variant['stock_status']) {
                        if ($minimumRegularPrice == 0 || $variant['price'] < $minimumRegularPrice) {
                            $minimumRegularPrice = $variant['price'];
                        }
                        if (isset($variant['special_price'])
                            && ($minimumSpecialPrice == 0 || $variant['special_price'] < $minimumSpecialPrice)
                        ) {
                            $minimumSpecialPrice = $variant['special_price'];
                        }
                    }
                    $configurableVariant[] = $variant;
                }
                $variants['minimum_price'] = $minimumRegularPrice;
                if ($minimumSpecialPrice != 0) {
                    $variants['minimum_special_price'] = $minimumSpecialPrice;
                }
                $variants['variants'] = $configurableVariant;
            }
        }
        return $variants;
    }

    /**
     * Get Default Variant ID for Configurable Products
     *
     * @param int $productId
     * @return int
     * @throws NoSuchEntityException
     */
    public function getDefaultVariantId(int $productId): int
    {
        $minimumAmount = null;
        $variantId = null;

        $product = $this->productRepository->getById($productId);

        /** @var Configurable $typeInstance */
        $typeInstance = $product->getTypeInstance();
        $productVariants = $this->configurableOptionsProvider->getProducts($product);

        foreach ($productVariants as $variant) {
            $variantStock = $this->getProductStockInfo($variant->getId());
            if ($variant->getStatus() != ProductStatus::STATUS_ENABLED || !$variantStock->getIsInStock()) {
                continue;
            }
            $variantAmount = $variant->getPriceInfo()
                ->getPrice(FinalPrice::PRICE_CODE)
                ->getAmount();

            if (!$minimumAmount
                || ($variantAmount->getValue() < $minimumAmount->getValue())
            ) {
                $minimumAmount = $variantAmount;
                $variantId = $variant->getId();
            }
        }
        if (!$variantId) {
            $variantId = $productVariants[0]->getId();
        }
        return $variantId;
    }

    /**
     * Get Parent Product ID By Child ID
     *
     * @param int $childId
     * @return mixed|string
     */
    private function getParentProductId(int $childId): mixed
    {
        $product = $this->configurable->getParentIdsByChild($childId);
        if (isset($product[0])) {
            return $product[0];
        }
        return "";
    }

    /**
     * Get Products By Category ID For Third Party.
     *
     * @param int $categoryId
     * @param string $platform
     * @param int $pageSize
     * @param int $currentPage
     * @return array
     * @throws AuthorizationException
     * @throws LocalizedException
     * @throws NoSuchEntityException
     */
    public function getProductsByCategoryId(int $categoryId, string $platform, int $pageSize, int $currentPage): array
    {
        if (!$this->isAllowed($platform)) {
            throw new AuthorizationException(__('Unauthorized'));
        }
        /** @var Category $category */
        $category = $this->categoryCollectionFactory->create()
            ->addAttributeToSelect('*')
            ->addAttributeToFilter('entity_id', ['eq' => $categoryId])
            ->addAttributeToFilter('is_active', ['eq' => 1])
            ->getFirstItem();
        if (!$category->getId()) {
            throw new NoSuchEntityException(
                __("The category that was requested doesn't exist. Verify the category and try again.")
            );
        }
        return $this->formatCategoryResponse($category, $pageSize, $currentPage);
    }

    /**
     * Format Category Response
     *
     * @param Category $category
     * @param int $pageSize
     * @param int $currentPage
     * @return array
     * @throws NoSuchEntityException
     */
    public function formatCategoryResponse(Category $category, int $pageSize, int $currentPage): array
    {
        $productCollection = $this->productCollectionFactory->create();
        $productCollection->addAttributeToSelect('*');
        $productCollection->addCategoriesFilter(['eq' => $category->getId()]);
        if ($pageSize && $currentPage) {
            $productCollection->setPageSize($pageSize);
            $productCollection->setCurPage($currentPage);
            return [
                'id' => $category->getId(),
                'name' => $category->getName(),
                'slug' => $category->getUrlKey(),
                'product_count' => $category->getProductCount(),
                'page_size' => $pageSize,
                'current_page' => $currentPage,
                'products' => $this->getFormattedProductResponse($productCollection)
            ];
        } else {
            return [
                'id' => $category->getId(),
                'name' => $category->getName(),
                'slug' => $category->getUrlKey(),
                'product_count' => $category->getProductCount(),
                'products' => $this->getFormattedProductResponse($productCollection)
            ];
        }
    }

    /**
     * Get Formatted Product Response
     *
     * @param Collection $productsCollection
     * @return array
     * @throws NoSuchEntityException
     */
    public function getFormattedProductResponse(Collection $productsCollection): array
    {
        $products = [];
        foreach ($productsCollection as $product) {
            $products[] = $this->formatProduct($product->getId());
        }
        return $products;
    }

    /**
     * Get Brand Images.
     *
     * @throws NoSuchEntityException
     * @throws LocalizedException
     */
    public function getBrandImages(): array
    {
        $brandImages = [];

        $brandsCategoryId = $this->scopeConfig->getValue(
            self::BRANDS_CATEGORY_ID,
            ScopeInterface::SCOPE_STORE
        );

        if ($brandsCategoryId) {
            /** @var Category $category */
            $categories = $this->categoryCollectionFactory->create()
                ->addAttributeToSelect('*')
                ->addAttributeToFilter('parent_id', ['eq' => $brandsCategoryId])
                ->addAttributeToFilter('is_active', ['eq' => 1]);

            foreach ($categories as $category) {
                $brandImages[] = [
                    'name' => $category->getName(),
                    'category_thumbnail' => $this->removeMediaFromUrl($category->getCategoryThumbnail())
                ];
            }
        }

        return $brandImages;
    }

    /**
     * Remove Media From Url
     *
     * @param string|null $url
     * @return string|null
     */
    public function removeMediaFromUrl(?string $url): ?string
    {
        if ($url && str_contains($url, '/media')) {
            $url = explode('/media', $url, 2)[1];
        }
        return $url;
    }

    /**
     * Place Guest Order For Third Party.
     *
     * @param string $platform
     * @param string $cartId
     * @param int|null $customerId
     * @param PaymentInterface|null $paymentMethod
     * @param CampaignInterface|null $campaign
     * @return array
     * @throws AuthorizationException
     * @throws CouldNotSaveException
     */
    public function placeExternalOrder(
        string            $platform,
        string            $cartId,
        ?int              $customerId,
        PaymentInterface  $paymentMethod = null,
        CampaignInterface $campaign = null
    ): array {
        if (!$this->isAllowed($platform)) {
            throw new AuthorizationException(__('Unauthorized'));
        }
        $this->dpandaLogger->info(
            "Order Placement Request",
            [
                'cart_id' => $cartId,
                'customer_id' => $customerId,
                'payment_method' => $paymentMethod->getMethod()
            ]
        );
        $eligibleCashbackAmount = 0;
        try {
            $totals = $this->guestCartTotalRepository->get($cartId);
            $eligibleCashbackAmount = $this->storeCreditHelper->getCashbackAmount($totals);
        } catch (Exception $exception) {
            $this->dpandaLogger->error($exception->getMessage());
        }
        $orderId = $this->guestCartManagement->placeOrder($cartId, $paymentMethod);
        $order = $this->orderRepository->get($orderId);
        switch ($paymentMethod->getMethod()) {
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

        try {
            $mageCustomerId = ($customerId != null) ? $this->customerRepository->getById($customerId)->getId() : null;
            $order->setCustomerId($mageCustomerId);
        } catch (NoSuchEntityException|LocalizedException $e) {
            $this->dpandaLogger->error($e->getMessage() . __METHOD__);
        }

        try {
            $order->setEstimatedDeliveryDate($this->getClickPostEdd());
            $order->setEligibleCashback($eligibleCashbackAmount);
            if (!empty($campaign)) {
                $order = $this->setOrderCampaign($order, $campaign);
            }
            $order = $this->orderRepository->save($order);
        } catch (Exception $exception) {
            $this->dpandaLogger->error($exception->getMessage() . __METHOD__);
        }

        $this->dpandaLogger->info(
            "Order Created",
            [
                'order_id' => $orderId,
                'increment_id' => $order->getIncrementId(),
            ]
        );

        if ($orderId) {
            return [
                "order_id" => $orderId,
                "details" => $this->baseHelper->getOrderDetails($order)
            ];
        }
        return [
            "order_id" => $orderId
        ];
    }

    /**
     * Get Estimated Delivery Date
     *
     * @return string
     */
    private function getClickPostEdd(): string
    {
        $days = self::MAX_DELIVERY_DATE;
        $date = $this->date->date('Y-m-d');
        return $this->date->date('Y-m-d', strtotime($date . " +" . $days . "days"));
    }

    /**
     * Update Campaign Details in sales_order table.
     *
     * @param OrderInterface $order
     * @param CampaignInterface $campaign
     * @return OrderInterface
     */
    private function setOrderCampaign(OrderInterface $order, CampaignInterface $campaign): OrderInterface
    {
        $order->setIpAddress($campaign->getIpAddress());
        $order->setPlatform($campaign->getPlatform());
        $order->setUtmId($campaign->getUtmId());
        $order->setUtmSource($campaign->getUtmSource());
        $order->setUtmCampaign($campaign->getUtmCampaign());
        $order->setUtmMedium($campaign->getUtmMedium());
        $order->setUtmTerm($campaign->getUtmTerm());
        $order->setUtmContent($campaign->getUtmContent());
        $order->setTrackerCookie($campaign->getTrackerCookie());
        $order->setUtmTimestamp($campaign->getUtmTimestamp());
        return $order;
    }

    /**
     * Get Order Details For Third Party
     *
     * @param string $platform
     * @param int $id
     * @return array
     * @throws NoSuchEntityException
     * @throws AuthorizationException
     */
    public function getOrderDetails(string $platform, int $id): array
    {
        if (!$this->isAllowed($platform)) {
            throw new AuthorizationException(__('Unauthorized'));
        }

        $id = strval($id);

        $order = $this->salesOrderFactory->create()
            ->loadByIncrementId($id);

        if (!$order->getId()) {
            throw new NoSuchEntityException(__('Order doesn\'t exist'));
        }

        $trackingHostUrl = $this->scopeConfig->getValue(
            self::TRACKING_HOST_URL_CONFIG_PATH,
            ScopeInterface::SCOPE_STORE
        );

        $trackingUrlByOrderId = '';
        $trackingUrlByAwbNo = [];
        $orderStatus = ['processing', 'shipped', 'partially_shipped', 'delivered', 'complete'];

        if (in_array($order->getStatus(), $orderStatus)) {
            $trackingUrlByOrderId = $trackingHostUrl . "my-order?order_id=" . $id;
        }

        foreach ($order->getTracksCollection() as $track) {
            $trackingUrlByAwbNo[$track->getTrackNumber()] = $trackingHostUrl . "?waybill=" . $track->getTrackNumber();
        }

        return [
            'status' => $order->getStatus(),
            'tracking_url_by_order_id' => $trackingUrlByOrderId,
            'tracking_url_by_awb_no' => $trackingUrlByAwbNo,
            'details' => $this->baseHelper->getOrderDetailForDPanda($order)
        ];
    }

    /**
     * Cancel Third Party Orders.
     *
     * @param string $platform
     * @param int $id
     * @return bool
     * @throws AuthorizationException
     * @throws NoSuchEntityException
     * @throws Exception
     */
    public function cancelOrder(string $platform, int $id): bool
    {
        if (!$this->isAllowed($platform)) {
            throw new AuthorizationException(__('Unauthorized'));
        }

        $this->dpandaLogger->info(
            "Order Cancel",
            [
                'increment_id' => $id
            ]
        );

        $order = $this->salesOrderFactory->create()
            ->loadByIncrementId($id);
        
        if (!$order->getId()) {
            throw new NoSuchEntityException(__('Order doesn\'t exist'));
        }

        return $this->orderHelper->cancelOrder(
            $order->getEntityId(),
            $order->getCustomerId(),
            "Cancelled By " . $platform
        );
    }

    /**
     * Get Product Detail By Product ID For Third Party.
     *
     * @param string $sku
     * @param string $platform
     * @return array
     * @throws AuthorizationException
     * @throws NoSuchEntityException
     */
    public function getInventoryBySku(string $sku, string $platform): array
    {
        if ($this->isAllowed($platform)) {
            $productData = [];
            $product = $this->productRepository->get($sku);
            $productStock = $this->getProductStockInfo($product->getId());
            $productData = [
                'id' => $product->getId(),
                'name' => $product->getName(),
                'sku' => $product->getSku(),
                'price' => $product->getPrice(),
                'stock_info' => [
                    'qty' => $productStock->getQty(),
                    'min_sale_qty' => $productStock->getMinSaleQty(),
                    'max_sale_qty' => $productStock->getMaxSaleQty(),
                    'is_in_stock' => $productStock->getIsInStock() && $product->getStatus() == 1
                ]
            ];
            if ($product->getCustomAttribute('special_price')) {
                $productData['special_price'] = $product->getCustomAttribute('special_price')->getValue();
                $productData['special_from_date_formatted'] = $product->getCustomAttribute('special_from_date')
                    ? $this->getDateTimeBasedOnTimezone(
                        $product->getCustomAttribute('special_from_date')->getValue()
                    )
                    : "";
                $productData['special_to_date_formatted'] = $product->getCustomAttribute('special_to_date')
                    ? $this->getDateTimeBasedOnTimezone(
                        $product->getCustomAttribute('special_to_date')->getValue()
                    )
                    : "";
            }
            return $productData;
        }
        throw new AuthorizationException(__('Unauthorized'));
    }

    /**
     * Get Orders by customer mobile number
     *
     * @param string $mobileNumber
     * @param string $platform
     * @param string $orderId
     * @return array
     * @throws NoSuchEntityException
     * @throws AuthorizationException
     */
    public function getOrdersByCustomerMobileNumber(string $mobileNumber, string $platform, string $orderId = ''): array
    {
        if (!$this->isAllowed($platform)) {
            throw new AuthorizationException(__('Unauthorized'));
        }

        // To extract only the last 10 digits from a mobile number string
        preg_match('/(\d{10})\D*$/', preg_replace('/\D/', '', $mobileNumber), $matches);
        if (isset($matches[1])) {
            $mobileNumber = $matches[1];
        } else {
            throw new InputMismatchException(__('The mobile number is not valid.'));
        }

        $message = 'success';
        $orderData = [];
        $customerCollection = $this->customerCollectionFactory->create();
        $customerCollection->addAttributeToSelect(['entity_id'])
            ->addAttributeToFilter('mobile_number', ['eq' => $mobileNumber])
            ->setPageSize(1)
            ->load();
        $customer = $customerCollection->getFirstItem();

        if ($customer && $customer->getId()) {
            $orders = $this->orderCollectionFactory->create()
                ->addFieldToSelect('*')
                ->addFieldToFilter('customer_id', $customer->getId());

            if ($orderId) {
                $orders->addFieldToFilter('increment_id', $orderId)
                    ->setPageSize(1);

                if ($orders->getSize() < 1) {
                    $message = 'No order found for the given order number.';
                }
            } else {
                $orders->setOrder('created_at', 'DESC')
                    ->setPageSize(5);
            }

            foreach ($orders->getItems() as $order) {
                $orderStatus = "";
                try {
                    $orderStatus = $order->getStatusLabel();
                } catch (LocalizedException $exception) {
                    $this->apiLogger->error($exception->getMessage() . __METHOD__);
                }

                list($trackingUrlByOrderId, $trackingUrlByAwbNo, $hyugaFrontendOrderUrl) = $this->getTrackingUrls(
                    $order
                );

                $orderData[] = [
                    "entity_id" => $order->getEntityId(),
                    "increment_id" => $order->getIncrementId(),
                    "created_at" => $this->baseHelper->getTimeBasedOnTimezone($order->getCreatedAt()),
                    "updated_at" => $this->baseHelper->getTimeBasedOnTimezone($order->getUpdatedAt()),
                    "status" => $orderStatus,
                    "status_code" => $order->getStatus(),
                    "total_item_count" => $order->getTotalItemCount(),
                    "total_qty_ordered" => $order->getTotalQtyOrdered(),
                    "items" => $this->baseHelper->getCustomerOrderItems($order->getItems()),
                    "shipments" => $this->baseHelper->getOrderShipment($order),
                    "tracking_url_by_order_id" => $trackingUrlByOrderId,
                    "tracking_url_by_awb_no" => $trackingUrlByAwbNo,
                    "hyuga_frontend_order_url" => $hyugaFrontendOrderUrl,
                    "shipping_address" => $this->baseHelper->getCustomerShippingAddressForOrder(
                        $order->getShippingAddress()
                    )
                ];
            }
        } else {
            $message = 'No customer found for the given mobile number.';
        }

        return [$message, $orderData];
    }

    /**
     * Function to get Tracking URLs
     *
     * @param OrderInterface $order
     * @return array
     */
    private function getTrackingUrls(OrderInterface $order)
    {
        $hyugaFrontendUrl = $this->scopeConfig->getValue(
            self::HYUGA_FRONTEND_URL_CONFIG_PATH,
            ScopeInterface::SCOPE_STORE
        );
        $trackingHostUrl = $this->scopeConfig->getValue(
            self::TRACKING_HOST_URL_CONFIG_PATH,
            ScopeInterface::SCOPE_STORE
        );

        $trackingUrlByOrderId = '';
        $trackingUrlByAwbNo = [];
        $orderStatus = ['processing', 'shipped', 'partially_shipped', 'delivered', 'complete'];

        // if (in_array($order->getStatus(), $orderStatus)) {
            $trackingUrlByOrderId = $trackingHostUrl . "my-order?order_id=" . $order->getIncrementId();
        // }

        foreach ($order->getTracksCollection() as $track) {
            $trackingUrlByAwbNo[$track->getTrackNumber()] = $trackingHostUrl . "?waybill=" . $track->getTrackNumber();
        }

        $hyugaFrontendOrderUrl = $hyugaFrontendUrl . "account/orders/details/" . $order->getId();

        return [$trackingUrlByOrderId, $trackingUrlByAwbNo, $hyugaFrontendOrderUrl];
    }
}
