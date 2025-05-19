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

use Magento\Catalog\Api\Data\ProductInterface;
use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\CatalogInventory\Api\Data\StockItemInterface;
use Magento\CatalogInventory\Api\StockRegistryInterface;
use Magento\ConfigurableProduct\Model\Product\Type\Configurable;
use Magento\Framework\Exception\CouldNotSaveException;
use Magento\Framework\Exception\InputException;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Exception\State\InvalidTransitionException;
use Magento\Framework\Stdlib\DateTime\TimezoneInterface;
use Magento\Quote\Api\CartRepositoryInterface;
use Magento\Quote\Api\Data\CartItemInterface;
use Magento\Quote\Api\Data\PaymentInterface;
use Magento\Quote\Api\Data\TotalsInterface;
use Magento\Quote\Api\GuestCartItemRepositoryInterface;
use Magento\Quote\Api\GuestCartManagementInterface;
use Magento\Quote\Api\GuestCartTotalRepositoryInterface;
use Magento\Quote\Model\QuoteIdMask;
use Magento\Quote\Model\QuoteIdMaskFactory;
use Pratech\Base\Helper\Data as BaseHelper;
use Pratech\Base\Logger\Logger;
use Pratech\Cart\Api\GuestPaymentManagementInterface;
use Pratech\Warehouse\Service\DeliveryDateCalculator;

/**
 * Guest Cart Helper Class for fetching guest cart related data.
 */
class GuestCart
{
    /**
     * Cart Helper Constructor
     *
     * @param GuestCartManagementInterface $guestCartManagement
     * @param CartRepositoryInterface $quoteRepository
     * @param QuoteIdMaskFactory $quoteIdMaskFactory
     * @param GuestCartItemRepositoryInterface $guestCartItemRepository
     * @param GuestCartTotalRepositoryInterface $guestCartTotalRepository
     * @param ProductRepositoryInterface $productRepository
     * @param StockRegistryInterface $stockItemRepository
     * @param BaseHelper $baseHelper
     * @param TimezoneInterface $timezoneInterface
     * @param Coupon $couponHelper
     * @param GuestPaymentManagementInterface $guestPaymentManagement
     * @param PaymentInterface $payment
     * @param Logger $apiLogger
     * @param Configurable $configurableType
     * @param DeliveryDateCalculator $deliveryDateCalculator
     */
    public function __construct(
        private GuestCartManagementInterface      $guestCartManagement,
        private CartRepositoryInterface           $quoteRepository,
        private QuoteIdMaskFactory                $quoteIdMaskFactory,
        private GuestCartItemRepositoryInterface  $guestCartItemRepository,
        private GuestCartTotalRepositoryInterface $guestCartTotalRepository,
        private ProductRepositoryInterface        $productRepository,
        private StockRegistryInterface            $stockItemRepository,
        private BaseHelper                        $baseHelper,
        private TimezoneInterface                 $timezoneInterface,
        private Coupon                            $couponHelper,
        private GuestPaymentManagementInterface   $guestPaymentManagement,
        private PaymentInterface                  $payment,
        private Logger                            $apiLogger,
        private Configurable                      $configurableType,
        private DeliveryDateCalculator            $deliveryDateCalculator
    )
    {
    }

    /**
     * Create Guest Cart
     *
     * @return string
     * @throws CouldNotSaveException
     */
    public function createGuestCart(): string
    {
        $quoteId = $this->guestCartManagement->createEmptyCart();

        try {
            $paymentMethod = $this->payment->setMethod('upi');
            $this->guestPaymentManagement->savePaymentInformation($quoteId, $paymentMethod);
        } catch (NoSuchEntityException|InvalidTransitionException|LocalizedException $e) {
            $this->apiLogger->error("Error while setting payment method during create cart for quote id " .
                $quoteId . " | " . $e->getMessage() . __METHOD__ . " | " . __LINE__);
        }
        return $quoteId;
    }

    /**
     * Reset Guest Cart
     *
     * @param string $cartId
     * @return bool
     */
    public function resetGuestCart(string $cartId): bool
    {
        try {
            /** @var $quoteIdMask QuoteIdMask */
            $quoteIdMask = $this->quoteIdMaskFactory->create()->load($cartId, 'masked_id');
            $quote = $this->quoteRepository->get($quoteIdMask->getQuoteId());
            $quote->setIsActive(1);
            $this->quoteRepository->save($quote);
            return true;
        } catch (NoSuchEntityException $e) {
            $this->apiLogger->error("Reset Guest Cart Issue : " . $cartId . $e->getMessage() . __METHOD__);
        }
        return false;
    }

    /**
     * Get Guest Cart Details
     *
     * @param string $cartId
     * @param int|null $pincode
     * @return array
     * @throws NoSuchEntityException
     */
    public function getGuestCart(string $cartId, int $pincode = null): array
    {
        /** @var $quoteIdMask QuoteIdMask */
        $quoteIdMask = $this->quoteIdMaskFactory->create()->load($cartId, 'masked_id');
        $cartDetails = $this->quoteRepository->get($quoteIdMask->getQuoteId());
        $items = [];
        $isCodAvailableForCart = 1;
        if (!empty($cartDetails->getItems())) {
            foreach ($cartDetails->getItems() as $item) {
                $cartItemDetail = $this->getFilteredCartItems($item, $pincode);
                if ($cartItemDetail['is_cod_restricted'] == 1) {
                    $isCodAvailableForCart = 0;
                }
                $items[] = $cartItemDetail;
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
            "applied_rule" => $cartDetails->getAppliedRuleIds() ?
                $this->couponHelper->getRuleDetails(explode(',', $cartDetails->getAppliedRuleIds()), [1, 2])
                : []
        ];
    }

    /**
     * Get Filtered Cart Items.
     *
     * @param CartItemInterface $cartItem
     * @param int|null $pincode
     * @return array
     * @throws NoSuchEntityException
     */
    private function getFilteredCartItems(CartItemInterface $cartItem, int $pincode = null): array
    {
        $estimatedDeliveryTime = null;
        $product = $this->getProduct($cartItem->getSku());
        $productStock = $this->getProductStockInfo($product->getId());
        if ($cartItem->getAppliedRuleIds()) {
            $appliedRuleIds = $cartItem->getPrice() != 0 ?
                $this->couponHelper->getRuleDetails(explode(',', $cartItem->getAppliedRuleIds()), [1])
                : $this->couponHelper->getRuleDetails(explode(',', $cartItem->getAppliedRuleIds()), [1, 2]);
        } else {
            $appliedRuleIds = [];
        }

        if ($pincode !== null) {
            $isDropship = (int)$product->getCustomAttribute('is_dropship')?->getValue();
            $estimatedDeliveryTime = $this->deliveryDateCalculator
                ->getEstimatedDelivery($product->getSku(), $pincode, $isDropship);
        }

        $itemData = [
            "product_id" => $product->getId(),
            "item_id" => $cartItem->getItemId(),
            "sku" => $cartItem->getSku(),
            "qty" => (int)$cartItem->getQty(),
            "name" => $cartItem->getName(),
            "price" => (float)$product->getPrice(),
            "item_price" => (float)$cartItem->getPrice(),
            "special_price" => ($product->getSpecialPrice() && $this->validateSpecialPrice($product)) ?
                (float)$product->getSpecialPrice()
                : null,
            "product_type" => $cartItem->getProductType(),
            "quote_id" => $cartItem->getQuoteId(),
            "image" => $product->getImage(),
            "stock_status" => $productStock->getIsInStock(),
            "is_cod_restricted" => $product->getCustomAttribute('is_cod_restricted')
                ? $product->getCustomAttribute('is_cod_restricted')->getValue()
                : 1,
            "stock_info" => [
                "min_sale_qty" => $productStock->getMinSaleQty(),
                "max_sale_qty" => $productStock->getMaxSaleQty(),
            ],
            "slug" => $product->getCustomAttribute('url_key')
                ? $product->getCustomAttribute('url_key')->getValue()
                : "",
            "applied_rule" => $appliedRuleIds,
            "estimated_delivery_time" => $estimatedDeliveryTime
        ];
        $parentConfigProduct = $this->configurableType->getParentIdsByChild($product->getId());
        if ($parentConfigProduct) {
            $itemData["parent_id"] = $parentConfigProduct[0];
        }
        return $itemData;
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
            if ((is_null($specialFromDate) && is_null($specialToDate)) ||
                ($today >= $specialFromDate && is_null($specialToDate)) ||
                ($today <= $specialToDate && is_null($specialFromDate)) ||
                ($today >= $specialFromDate && $today <= $specialToDate)
            ) {
                return true;
            }
        }
        return false;
    }

    /**
     * Get Guest Cart Totals
     *
     * @param string $cartId
     * @return TotalsInterface
     * @throws NoSuchEntityException
     */
    public function getGuestCartTotals(string $cartId): TotalsInterface
    {
        /** @var $quoteIdMask QuoteIdMask */
        $quoteIdMask = $this->quoteIdMaskFactory->create()->load($cartId, 'masked_id');

        $quote = $this->quoteRepository->get($quoteIdMask->getQuoteId());
        $quote->collectTotals();

        return $this->guestCartTotalRepository->get($cartId);
    }

    /**
     * Add Product To Guest Cart
     *
     * @param CartItemInterface $cartItem
     * @return array
     * @throws CouldNotSaveException
     * @throws InputException
     * @throws NoSuchEntityException
     */
    public function addItemToGuestCart(CartItemInterface $cartItem): array
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
     * @throws NoSuchEntityException
     */
    private function alterCartItem(CartItemInterface $cartItem): array
    {
        $cartItemAdded = $this->guestCartItemRepository->save($cartItem);
        if ($cartItemAdded->getItemId()) {
            return $this->getAddedOrUpdatedCartItem($cartItemAdded);
        }
        return [];
    }

    /**
     * Get Added Or Updated Cart Item.
     *
     * @param CartItemInterface $cartItem
     * @return array
     */
    private function getAddedOrUpdatedCartItem(CartItemInterface $cartItem): array
    {
        return [
            "item_id" => $cartItem->getItemId(),
            "sku" => $cartItem->getSku(),
            "qty" => (int)$cartItem->getQty(),
            "name" => $cartItem->getName(),
            "item_price" => (float)$cartItem->getPrice(),
            "product_type" => $cartItem->getProductType(),
            "quote_id" => $cartItem->getQuoteId()
        ];
    }

    /**
     * Add Product To Guest Cart
     *
     * @param array $cartItems
     * @return array
     * @throws CouldNotSaveException
     * @throws InputException
     * @throws NoSuchEntityException
     */
    public function addMultipleItemToGuestCart(array $cartItems): array
    {
        $addedCartItem = [];

        foreach ($cartItems as $cartItem) {
            /** @var $quoteIdMask QuoteIdMask */
            $quoteIdMask = $this->quoteIdMaskFactory->create()->load($cartItem['quote_id'], 'masked_id');
            $quote = $this->quoteRepository->get($quoteIdMask->getQuoteId());
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
     * Update Product In Guest Cart
     *
     * @param CartItemInterface $cartItem
     * @return array
     * @throws CouldNotSaveException
     * @throws InputException
     * @throws NoSuchEntityException
     */
    public function updateItemInGuestCart(CartItemInterface $cartItem): array
    {
        return $this->alterCartItem($cartItem);
    }

    /**
     * Delete Item From Guest Cart
     *
     * @param string $cartId
     * @param int $itemId
     * @return bool
     * @throws NoSuchEntityException
     * @throws CouldNotSaveException
     */
    public function deleteItemFromGuestCart(string $cartId, int $itemId): bool
    {
        return $this->guestCartItemRepository->deleteById($cartId, $itemId);
    }
}
