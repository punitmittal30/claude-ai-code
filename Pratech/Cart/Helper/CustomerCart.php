<?php
/**
 * Pratech_Cart
 *
 * PHP version 8.x
 *
 * @category  PHP
 * @package   Pratech\Cart
 * @author    Puneet Mittal <puneet.mittal@pratechbrands.com>
 * @copyright 2022 Copyright (c) Pratech Brands Private Limited
 * @link      https://pratechbrands.com/
 **/

namespace Pratech\Cart\Helper;

use Exception;
use Magento\Catalog\Api\Data\ProductInterface;
use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\CatalogInventory\Api\Data\StockItemInterface;
use Magento\CatalogInventory\Api\StockRegistryInterface;
use Magento\ConfigurableProduct\Model\Product\Type\Configurable;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\Exception\CouldNotSaveException;
use Magento\Framework\Exception\InputException;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Exception\State\InvalidTransitionException;
use Magento\Framework\Stdlib\DateTime\TimezoneInterface;
use Magento\Quote\Api\CartItemRepositoryInterface;
use Magento\Quote\Api\CartManagementInterface;
use Magento\Quote\Api\CartRepositoryInterface;
use Magento\Quote\Api\CartTotalRepositoryInterface;
use Magento\Quote\Api\Data\CartInterface;
use Magento\Quote\Api\Data\CartItemInterface;
use Magento\Quote\Api\Data\PaymentInterface;
use Magento\Quote\Api\Data\TotalsInterface;
use Magento\Quote\Model\QuoteFactory;
use Magento\Quote\Model\QuoteIdMask;
use Magento\Quote\Model\QuoteIdMaskFactory;
use Magento\Store\Model\ScopeInterface;
use Pratech\Base\Helper\Data as BaseHelper;
use Pratech\Base\Logger\Logger;
use Pratech\Cart\Api\CustomerPaymentManagementInterface;
use Pratech\Catalog\Helper\Product as ProductHelper;
use Pratech\Warehouse\Service\DeliveryDateCalculator;

/**
 * Customer Cart Helper Class for fetching customer cart related data.
 */
class CustomerCart
{
    /**
     * Cross Sell Status on Cart page
     */
    public const CROSS_SELL_ENABLE_CONFIG_PATH = 'cart/cross_sell/enable';

    /**
     * Cross Sell Max Number on Cart Page
     */
    public const CROSS_SELL_MAX_NUMBER_CONFIG_PATH = 'cart/cross_sell/max_number';

    /**
     * Cross Sell Mode on Cart page
     */
    public const CROSS_SELL_MODE_CONFIG_PATH = 'cart/cross_sell/mode';

    /**
     * Cart Helper Constructor
     *
     * @param CartManagementInterface $customerCartManagement
     * @param CartRepositoryInterface $quoteRepository
     * @param QuoteIdMaskFactory $quoteIdMaskFactory
     * @param CartItemRepositoryInterface $customerCartItemRepository
     * @param CartTotalRepositoryInterface $customerCartTotalRepository
     * @param ProductRepositoryInterface $productRepository
     * @param StockRegistryInterface $stockItemRepository
     * @param BaseHelper $baseHelper
     * @param TimezoneInterface $timezoneInterface
     * @param Coupon $couponHelper
     * @param CustomerPaymentManagementInterface $customerPaymentManagement
     * @param PaymentInterface $payment
     * @param Logger $apiLogger
     * @param QuoteFactory $quoteFactory
     * @param Configurable $configurableType
     * @param DeliveryDateCalculator $deliveryDateCalculator
     * @param ProductHelper $productHelper
     * @param ScopeConfigInterface $scopeConfig
     */
    public function __construct(
        private CartManagementInterface            $customerCartManagement,
        private CartRepositoryInterface            $quoteRepository,
        private QuoteIdMaskFactory                 $quoteIdMaskFactory,
        private CartItemRepositoryInterface        $customerCartItemRepository,
        private CartTotalRepositoryInterface       $customerCartTotalRepository,
        private ProductRepositoryInterface         $productRepository,
        private StockRegistryInterface             $stockItemRepository,
        private BaseHelper                         $baseHelper,
        private TimezoneInterface                  $timezoneInterface,
        private Coupon                             $couponHelper,
        private CustomerPaymentManagementInterface $customerPaymentManagement,
        private PaymentInterface                   $payment,
        private Logger                             $apiLogger,
        private QuoteFactory                       $quoteFactory,
        private Configurable                       $configurableType,
        private DeliveryDateCalculator             $deliveryDateCalculator,
        private ProductHelper                      $productHelper,
        private ScopeConfigInterface               $scopeConfig
    )
    {
    }

    /**
     * Reset Customer Cart
     *
     * @param int $cartId
     * @return bool
     */
    public function resetCustomerCart(int $cartId): bool
    {
        try {
            $quote = $this->quoteRepository->get($cartId);
            $quote->setIsActive(1);
            $this->quoteRepository->save($quote);
            return true;
        } catch (NoSuchEntityException $e) {
            $this->apiLogger->error("Reset Guest Cart Issue : " . $e->getMessage() . __METHOD__);
        }
        return false;
    }

    /**
     * Add Product To Customer Cart
     *
     * @param CartItemInterface $cartItem
     * @return array
     * @throws CouldNotSaveException
     * @throws InputException
     * @throws NoSuchEntityException|LocalizedException
     */
    public function addItemToCustomerCart(CartItemInterface $cartItem): array
    {
        return $this->alterCartItem($cartItem);
    }

    /**
     * Alter Cart Item(Add or Update)
     *
     * @param CartItemInterface $cartItem
     * @return array
     * @throws CouldNotSaveException
     * @throws InputException
     * @throws NoSuchEntityException|LocalizedException
     */
    private function alterCartItem(CartItemInterface $cartItem): array
    {
        $cartItemAdded = $this->customerCartItemRepository->save($cartItem);
        if ($cartItemAdded->getItemId()) {
            return [
                "item_id" => $cartItemAdded->getItemId(),
                "sku" => $cartItemAdded->getSku(),
                "qty" => $cartItemAdded->getQty(),
                "name" => $cartItemAdded->getName(),
                "item_price" => (float)$cartItemAdded->getPrice(),
                "product_type" => $cartItemAdded->getProductType(),
                "quote_id" => $cartItemAdded->getQuoteId()
            ];
        }
        return [];
    }

    /**
     * Add Multiple Products To Customer Cart
     *
     * @param array $cartItems
     * @return array
     * @throws CouldNotSaveException
     * @throws InputException
     * @throws LocalizedException
     * @throws NoSuchEntityException
     */
    public function addMultipleItemToCustomerCart(array $cartItems): array
    {
        $addedCartItem = [];

        foreach ($cartItems as $cartItem) {
            $quote = $this->quoteRepository->get($cartItem['quote_id']);
            $product = $this->productRepository->get($cartItem['sku']);
            $quoteItem = $quote->addProduct($product, $cartItem['qty']);
            $quote->save();
            $quote->collectTotals()->save();
            $addedCartItem[] = [
                "item_id" => $quoteItem->getItemId(),
                "sku" => $quoteItem->getSku(),
                "qty" => (int)$quoteItem->getQty(),
                "quote_id" => $quoteItem->getQuoteId()
            ];
        }

        return $addedCartItem;
    }

    /**
     * Get Customer Cart Totals
     *
     * @param string $cartId
     * @return TotalsInterface
     * @throws NoSuchEntityException
     */
    public function getCustomerCartTotals(string $cartId): TotalsInterface
    {
        $quote = $this->quoteRepository->getActive($cartId);
        $quote->collectTotals();
        return $this->customerCartTotalRepository->get($cartId);
    }

    /**
     * Merge Cart.
     *
     * @param int $customerId
     * @param string $cartId
     * @return CartInterface
     * @throws NoSuchEntityException|CouldNotSaveException
     */
    public function mergeCart(int $customerId, string $cartId): CartInterface
    {
        $guestCart = $this->getGuestCart($cartId);
        if (!$guestCart->getIsActive()) {
            throw new NoSuchEntityException(
                __('The cart isn\'t active.')
            );
        }
        $customerCart = $this->quoteFactory->create()->loadByCustomer($customerId);
        if (empty($customerCart) || !$customerCart->getIsActive()) {
            $customerCartId = $this->createCustomerEmptyCart($customerId);
            if ($customerCartId) {
                $customerCart = $this->customerCartManagement->getCartForCustomer($customerId);
            }
        }
        $customerCart->merge($guestCart);
        $guestCart->setIsActive(false);
        $this->quoteRepository->save($customerCart);
        $this->quoteRepository->save($guestCart);
        return $customerCart;
    }

    /**
     * Get Guest Cart Details
     *
     * @param string $cartId
     * @return CartInterface
     * @throws NoSuchEntityException
     */
    public function getGuestCart(string $cartId): CartInterface
    {
        /** @var $quoteIdMask QuoteIdMask */
        $quoteIdMask = $this->quoteIdMaskFactory->create()->load($cartId, 'masked_id');
        return $this->quoteRepository->get($quoteIdMask->getQuoteId());
    }

    /**
     * Create Customer Empty Cart
     *
     * @param int $customerId
     * @return int
     * @throws CouldNotSaveException
     */
    public function createCustomerEmptyCart(int $customerId): int
    {
        $quoteId = $this->customerCartManagement->createEmptyCartForCustomer($customerId);
        try {
            $paymentMethod = $this->payment->setMethod('upi');
            $this->customerPaymentManagement->savePaymentInformation($quoteId, $paymentMethod);
        } catch (NoSuchEntityException|InvalidTransitionException|LocalizedException $e) {
            $this->apiLogger->error("Error while setting payment method during create cart for quote id " .
                $quoteId . " | " . $e->getMessage() . __METHOD__ . " | " . __LINE__);
        }
        return $quoteId;
    }

    /**
     * Get Customer Cart Details
     *
     * @param int $customerId
     * @param int|null $pincode
     * @return array
     * @throws NoSuchEntityException
     */
    public function getCustomerCart(int $customerId, int $pincode = null): array
    {
        $estimatedDeliveryTime = null;
        $cartDetails = $this->customerCartManagement->getCartForCustomer($customerId);
        $isCodAvailableForCart = 1;
        $items = [];
        if (!empty($cartDetails->getItems())) {
            foreach ($cartDetails->getItems() as $item) {
                $product = $this->getProduct($item->getSku());
                $productStock = $this->getProductStockInfo($product->getId());
                if ($pincode !== null) {
                    $estimatedDeliveryTime = $this->deliveryDateCalculator
                        ->getEstimatedDelivery($product->getSku(), $pincode);
                }

                if ($item->getAppliedRuleIds()) {
                    $appliedRuleIds = $item->getPrice() != 0
                        ? $this->couponHelper->getRuleDetails(explode(',', $item->getAppliedRuleIds()), [1])
                        : $this->couponHelper->getRuleDetails(explode(',', $item->getAppliedRuleIds()), [1, 2]);
                } else {
                    $appliedRuleIds = [];
                }

                $isCodRestricted = $product->getCustomAttribute('is_cod_restricted')
                    ? $product->getCustomAttribute('is_cod_restricted')->getValue()
                    : 1;

                if ($isCodRestricted) {
                    $isCodAvailableForCart = 0;
                }

                $itemData = [
                    "product_id" => $product->getId(),
                    "item_id" => $item->getItemId(),
                    "sku" => $item->getSku(),
                    "qty" => (int)$item->getQty(),
                    "name" => $item->getName(),
                    "price" => (float)$product->getPrice(),
                    "item_price" => (float)$item->getPrice(),
                    "special_price" => ($product->getSpecialPrice() && $this->validateSpecialPrice($product)) ?
                        (float)$product->getSpecialPrice()
                        : null,
                    "product_type" => $item->getProductType(),
                    "quote_id" => $item->getQuoteId(),
                    "image" => $product->getImage(),
                    "stock_status" => $productStock->getIsInStock(),
                    "is_cod_restricted" => $isCodRestricted,
                    "stock_info" => [
                        "min_sale_qty" => $productStock->getMinSaleQty(),
                        "max_sale_qty" => $productStock->getMaxSaleQty(),
                    ],
                    "slug" => $product->getCustomAttribute('url_key')
                        ? $product->getCustomAttribute('url_key')->getValue()
                        : "",
                    'applied_rule' => $appliedRuleIds,
                    "estimated_delivery_time" => $estimatedDeliveryTime
                ];

                $parentConfigProduct = $this->configurableType->getParentIdsByChild($product->getId());
                if ($parentConfigProduct) {
                    $itemData['parent_id'] = $parentConfigProduct[0];
                }
                $items[] = $itemData;
            }
        }

        return [
            "id" => (int)$cartDetails->getId(),
            "created_at" => $cartDetails->getCreatedAt(),
            "updated_at" => $cartDetails->getUpdatedAt(),
            "is_active" => (bool)$cartDetails->getIsActive(),
            "is_virtual" => (bool)$cartDetails->getIsVirtual(),
            "items" => $items,
            "items_count" => (int)$cartDetails->getItemsCount(),
            "items_qty" => (int)$cartDetails->getItemsQty(),
            "orig_order_id" => $cartDetails->getOrigOrderId(),
            "customer_is_guest" => (bool)$cartDetails->getCustomerIsGuest(),
            "is_cod_available_for_cart" => $isCodAvailableForCart,
            'applied_rule' => $cartDetails->getAppliedRuleIds() ?
                $this->couponHelper->getRuleDetails(explode(',', $cartDetails->getAppliedRuleIds()), [1, 2])
                : []
        ];
    }

    /**
     * Get Product Image By SKU
     *
     * @param string $sku
     * @return ProductInterface
     * @throws NoSuchEntityException
     */
    private function getProduct(string $sku): ProductInterface
    {
        return $this->productRepository->get($sku);
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
     * Get Validate Special Price
     *
     * @param ProductInterface $product
     * @return bool
     */
    public function validateSpecialPrice(ProductInterface $product): bool
    {
        $specialPrice = $product->getSpecialPrice();
        $specialFromDate = $product->getSpecialFromDate() ?
            $this->baseHelper->getDateTimeBasedOnTimezone($product->getSpecialFromDate())
            : null;
        $specialToDate = $product->getSpecialToDate() ?
            $this->baseHelper->getDateTimeBasedOnTimezone($product->getSpecialToDate())
            : null;
        $today = $this->timezoneInterface->date()->format('Y-m-d H:i:s');
        if ($specialPrice) {
            if ((is_null($specialFromDate) && is_null($specialToDate))
                || ($today >= $specialFromDate && is_null($specialToDate))
                || ($today <= $specialToDate && is_null($specialFromDate))
                || ($today >= $specialFromDate && $today <= $specialToDate)
            ) {
                return true;
            }
        }
        return false;
    }

    /**
     * Update Item In Customer Cart.
     *
     * @param CartItemInterface $cartItem
     * @return array
     * @throws CouldNotSaveException
     * @throws InputException
     * @throws NoSuchEntityException|LocalizedException
     */
    public function updateItemInCustomerCart(CartItemInterface $cartItem): array
    {
        return $this->alterCartItem($cartItem);
    }

    /**
     * Delete Item From Customer Cart.
     *
     * @param int $cartId
     * @param int $itemId
     * @return bool
     * @throws CouldNotSaveException
     * @throws NoSuchEntityException
     */
    public function deleteItemFromCustomerCart(int $cartId, int $itemId): bool
    {
        return $this->customerCartItemRepository->deleteById($cartId, $itemId);
    }

    /**
     * Get cross-sell products of cart items
     *
     * @param string $type Possible values: customer|guest
     * @param string $cartId
     * @param int|null $pincode
     * @return array
     * @throws Exception|NoSuchEntityException
     */
    public function getCartCrossSellProducts(string $type, string $cartId, int $pincode = null): array
    {
        $crossSellProducts = [];
        try {
            if (!$this->getConfigValue(self::CROSS_SELL_ENABLE_CONFIG_PATH)) {
                return [];
            }

            $cartDetails = ($type == 'guest') ?
                $this->getGuestCart($cartId)
                : $this->quoteRepository->get($cartId);
            $cartItems = $cartDetails->getItems();
            if (empty($cartItems)) {
                return [];
            }

            // Get cart item product IDs and the last item
            $cartItemProductIds = array_map(fn($item) => $item->getProductId(), $cartItems);
            $lastItem = max($cartItems, fn($a, $b) => $a->getId() <=> $b->getId());

            // Get cross-sell configuration
            $noOfCrossSellToDisplay = (int)$this->getConfigValue(self::CROSS_SELL_MAX_NUMBER_CONFIG_PATH);
            $crossSellMode = $this->getConfigValue(self::CROSS_SELL_MODE_CONFIG_PATH);

            return match ($crossSellMode) {
                'allcartitems' => $this->processCrossSellForAllItems(
                    $cartItems,
                    $cartItemProductIds,
                    $noOfCrossSellToDisplay,
                    $pincode
                ),
                'lastcartitem' => $lastItem ? $this->processCrossSellForLastItem(
                    $lastItem,
                    $cartItemProductIds,
                    $noOfCrossSellToDisplay,
                    $pincode
                ) : [],
                default => []
            };
        } catch (Exception|NoSuchEntityException $e) {
            $this->apiLogger->error(
                "Cart Cross-Sell Products Issue: " . $e->getMessage() . " in " . __METHOD__
            );
        }

        return $crossSellProducts;
    }

    /**
     * Get Scope Config Value.
     *
     * @param string $config
     * @return mixed
     */
    public function getConfigValue(string $config): mixed
    {
        return $this->scopeConfig->getValue(
            $config,
            ScopeInterface::SCOPE_STORE
        );
    }

    /**
     * Process cross-sell products for all cart items
     *
     * @param array $cartItems
     * @param array $cartItemProductIds
     * @param int $noOfCrossSellToDisplay
     * @param int|null $pincode
     * @return array
     * @throws NoSuchEntityException
     */
    private function processCrossSellForAllItems(array $cartItems, array $cartItemProductIds, int $noOfCrossSellToDisplay, ?int $pincode): array
    {
        $crossSellProducts = [];
        $productIdsProcessed = [];
        $productIdsToProcess = [];
        $noOfCrossSellPerItem = (int)($noOfCrossSellToDisplay / count($cartItems));

        foreach ($cartItems as $item) {
            $crossSellPerItemCount = 0;
            $crossSellProductIds = $item->getProduct()->getCrossSellProductIds();
            $productIdsToProcess = array_merge($productIdsToProcess, $crossSellProductIds);
            foreach (array_diff(
                         $crossSellProductIds,
                         $cartItemProductIds,
                         $productIdsProcessed
                     ) as $productId) {
                $productStock = $this->productHelper->getProductStockInfo($productId);
                if ($productStock->getIsInStock()) {
                    $crossSellProducts[] = $this->productHelper->formatProductForCarousel($productId, $pincode);
                }
                $productIdsProcessed[] = $productId;
                $crossSellPerItemCount++;
                if ($crossSellPerItemCount == $noOfCrossSellPerItem) {
                    break;
                }
                if (count($crossSellProducts) >= $noOfCrossSellToDisplay) {
                    break 2;
                }
            }
        }
        if (count($crossSellProducts) < $noOfCrossSellToDisplay) {
            $productIdsToInclude = array_diff($productIdsToProcess, $productIdsProcessed);
            shuffle($productIdsToInclude);
            foreach ($productIdsToInclude as $productId) {
                $productStock = $this->productHelper->getProductStockInfo($productId);
                if ($productStock->getIsInStock()) {
                    $crossSellProducts[] = $this->productHelper->formatProductForCarousel($productId, $pincode);
                }
                if (count($crossSellProducts) >= $noOfCrossSellToDisplay) {
                    break;
                }
            }
        }
        return $crossSellProducts;
    }

    /**
     * Process cross-sell products for the last cart item
     *
     * @param mixed $lastItem
     * @param array $cartItemProductIds
     * @param int $noOfCrossSellToDisplay
     * @param int|null $pincode
     * @return array
     * @throws NoSuchEntityException
     */
    private function processCrossSellForLastItem(mixed $lastItem, array $cartItemProductIds, int $noOfCrossSellToDisplay, ?int $pincode): array
    {
        $crossSellProducts = [];
        $productIds = $lastItem->getProduct()->getCrossSellProductIds();

        foreach ($productIds as $productId) {
            if (in_array($productId, $cartItemProductIds)) {
                continue;
            }
            $productStock = $this->getProductStockInfo($productId);
            if (!$productStock || !$productStock->getIsInStock()) {
                $crossSellProducts[] = $this->productHelper->formatProductForCarousel($productId, $pincode);
            }
            if (count($crossSellProducts) >= $noOfCrossSellToDisplay) {
                break;
            }
        }
        return $crossSellProducts;
    }
}
