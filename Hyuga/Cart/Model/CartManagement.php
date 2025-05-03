<?php
/**
 * Hyuga_Cart
 *
 * PHP version 8.x
 *
 * @category  PHP
 * @package   Hyuga\Cart
 * @author    Puneet Mittal <puneet.mittal@pratechbrands.com>
 * @copyright 2025 Copyright (c) Pratech Brands Private Limited
 * @link      https://pratechbrands.com/
 **/

namespace Hyuga\Cart\Model;

use Exception;
use Hyuga\Cart\Api\CartManagementInterface;
use Hyuga\Cart\Service\CartService;
use Hyuga\LogManagement\Logger\CartApiLogger;
use Magento\Framework\Exception\NoSuchEntityException;
use Pratech\Base\Model\Data\Response;

class CartManagement implements CartManagementInterface
{
    /**
     * @param CartService $cartService
     * @param CartApiLogger $cartApiLogger
     * @param Response $response
     */
    public function __construct(
        private CartService   $cartService,
        private CartApiLogger $cartApiLogger,
        private Response      $response
    ) {
    }

    /**
     * @inheritDoc
     */
    public function getCartCrossSellProducts(string $type, string $cartId, int $pincode = null): array
    {
        return $this->response->getResponse(
            200,
            'success',
            'cart',
            $this->getCartCrossSellProductsByPincode($type, $cartId, $pincode)
        );
    }

    /**
     * Get cross-sell products for cart by pincode.
     *
     * @param string $type
     * @param string $cartId
     * @param int|null $pincode
     * @return array
     */
    private function getCartCrossSellProductsByPincode(string $type, string $cartId, int $pincode = null): array
    {
        $crossSellProducts = [];

        try {
            if (!$this->cartService->getConfigValue(CartService::CROSS_SELL_ENABLE_CONFIG_PATH)) {
                return [];
            }

            $cartDetails = ($type == 'guest')
                ? $this->cartService->getGuestCart($cartId)
                : $this->cartService->getCart($cartId);

            $cartItems = $cartDetails->getItems();
            if (empty($cartItems)) {
                return [];
            }

            $maxCrossSellCount = (int)$this->cartService
                ->getConfigValue(CartService::CROSS_SELL_MAX_NUMBER_CONFIG_PATH);
            $crossSellMode = $this->cartService->getConfigValue(CartService::CROSS_SELL_MODE_CONFIG_PATH);

            $cartItemProductIds = [];
            $cartItemMap = [];

            foreach ($cartItems as $item) {
                $productId = $item->getProductId();
                $cartItemProductIds[] = $productId;
                $cartItemMap[$productId] = $item;
            }

            // Determine which cart items to process based on mode
            $itemsToProcess = [];
            if ($crossSellMode === 'lastcartitem') {
                // Get the last item based on item ID
                $lastItemId = 0;
                $lastItem = null;
                foreach ($cartItems as $item) {
                    if ($item->getId() > $lastItemId) {
                        $lastItemId = $item->getId();
                        $lastItem = $item;
                    }
                }
                if ($lastItem) {
                    $itemsToProcess[] = $lastItem;
                }
            } else {
                // Process all cart items
                $itemsToProcess = $cartItems;
            }

            if (empty($itemsToProcess)) {
                return [];
            }

            // Collect all cross-sell product IDs in a single array
            $allCrossSellProductIds = [];
            foreach ($itemsToProcess as $item) {
                $crossSellIds = $item->getProduct()->getCrossSellProductIds();
                $allCrossSellProductIds = array_merge($allCrossSellProductIds, $crossSellIds);
            }

            // Remove duplicates and cart item products
            $allCrossSellProductIds = array_unique($allCrossSellProductIds);
            $allCrossSellProductIds = array_diff($allCrossSellProductIds, $cartItemProductIds);

            if (empty($allCrossSellProductIds)) {
                return [];
            }

            // Batch load stock information for all cross-sell products
            $stockItems = $this->batchGetProductStockInfo($allCrossSellProductIds);

            // Filter to in-stock products only
            $inStockProductIds = [];
            foreach ($stockItems as $productId => $stockItem) {
                if ($stockItem->getIsInStock()) {
                    $inStockProductIds[] = $productId;
                }
            }

            if (empty($inStockProductIds)) {
                return [];
            }

            // Limit the number of products to process based on mode
            if ($crossSellMode === 'allcartitems') {
                // Distribute cross-sell items evenly among cart items
                $productsPerItem = ceil($maxCrossSellCount / count($itemsToProcess));
                $processedProductIds = [];

                foreach ($itemsToProcess as $item) {
                    $crossSellIds = array_intersect(
                        $item->getProduct()->getCrossSellProductIds(),
                        $inStockProductIds
                    );
                    $crossSellIds = array_diff($crossSellIds, $processedProductIds);

                    // Limit to products per item
                    $crossSellIds = array_slice($crossSellIds, 0, $productsPerItem);

                    foreach ($crossSellIds as $productId) {
                        // If we've reached the maximum, stop processing
                        if (count($crossSellProducts) >= $maxCrossSellCount) {
                            break 2;
                        }

                        $crossSellProducts[] = $this->cartService->getProductData($productId, $pincode);
                        $processedProductIds[] = $productId;
                    }
                }

                // If we still need more products, add more from the remaining stock
                if (count($crossSellProducts) < $maxCrossSellCount) {
                    $remainingProductIds = array_diff($inStockProductIds, $processedProductIds);

                    // Shuffle to randomize the remaining cross-sell products
                    shuffle($remainingProductIds);

                    // Add remaining products up to the maximum
                    foreach ($remainingProductIds as $productId) {
                        if (count($crossSellProducts) >= $maxCrossSellCount) {
                            break;
                        }

                        $crossSellProducts[] = $this->cartService->getProductData($productId, $pincode);
                    }
                }
            } else {
                // For 'lastcartitem' mode, just take the first N cross-sell products
                $lastItem = $itemsToProcess[0]; // We know there's only one item in this case
                $crossSellIds = array_intersect(
                    $lastItem->getProduct()->getCrossSellProductIds(),
                    $inStockProductIds
                );

                // Limit to maximum cross-sell count
                $crossSellIds = array_slice($crossSellIds, 0, $maxCrossSellCount);

                foreach ($crossSellIds as $productId) {
                    $crossSellProducts[] = $this->cartService->getProductData($productId, $pincode);
                }
            }

            return $crossSellProducts;
        } catch (Exception|NoSuchEntityException $e) {
            $this->cartApiLogger->error(
                "Cart Cross-Sell Products Issue: " . $e->getMessage() . " in " . __METHOD__
            );
        }

        return $crossSellProducts;
    }

    /**
     * Batch get product stock information for multiple product IDs
     *
     * @param array $productIds
     * @return array
     */
    private function batchGetProductStockInfo(array $productIds): array
    {
        $stockItems = [];

        foreach ($productIds as $productId) {
            $stockItems[$productId] = $this->cartService->getStockItem($productId);
        }

        return $stockItems;
    }
}
