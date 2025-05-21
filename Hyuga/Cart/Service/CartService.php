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

namespace Hyuga\Cart\Service;

use Hyuga\Catalog\Api\ProductRepositoryInterface;
use Magento\CatalogInventory\Api\Data\StockItemInterface;
use Magento\CatalogInventory\Api\StockRegistryInterface;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Quote\Api\CartRepositoryInterface;
use Magento\Quote\Api\Data\CartInterface;
use Magento\Quote\Model\QuoteFactory;
use Magento\Quote\Model\QuoteIdMask;
use Magento\Quote\Model\QuoteIdMaskFactory;
use Magento\Store\Model\ScopeInterface;

class CartService
{
    /**
     * Config Paths
     */
    public const CROSS_SELL_ENABLE_CONFIG_PATH = 'cart/cross_sell/enable';
    public const CROSS_SELL_MAX_NUMBER_CONFIG_PATH = 'cart/cross_sell/max_number';
    public const CROSS_SELL_MODE_CONFIG_PATH = 'cart/cross_sell/mode';

    /**
     * @param CartRepositoryInterface $quoteRepository
     * @param QuoteIdMaskFactory $quoteIdMaskFactory
     * @param StockRegistryInterface $stockItemRepository
     * @param ScopeConfigInterface $scopeConfig
     * @param ProductRepositoryInterface $productRepository
     */
    public function __construct(
        private CartRepositoryInterface    $quoteRepository,
        private QuoteIdMaskFactory         $quoteIdMaskFactory,
        private StockRegistryInterface     $stockItemRepository,
        private ScopeConfigInterface       $scopeConfig,
        private ProductRepositoryInterface $productRepository
    ) {
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
     * Get Cart.
     *
     * @param int $cartId
     * @return CartInterface
     * @throws NoSuchEntityException
     */
    public function getCart(int $cartId): CartInterface
    {
        return $this->quoteRepository->get($cartId);
    }

    /**
     * Get Stock Item.
     *
     * @param int $productId
     * @return StockItemInterface
     */
    public function getStockItem(int $productId): StockItemInterface
    {
        return $this->stockItemRepository->getStockItem($productId);
    }

    /**
     * Get Product Data.
     *
     * @param int $productId
     * @param int|null $pincode
     * @return array
     * @throws NoSuchEntityException
     */
    public function getProductData(int $productId, int $pincode = null): array
    {
        return $this->productRepository->getProductById($productId, $pincode, 'carousel');
    }
}
