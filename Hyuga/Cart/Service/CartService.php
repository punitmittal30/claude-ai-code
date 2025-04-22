<?php

namespace Hyuga\Cart\Service;

use Hyuga\Catalog\Api\ProductRepositoryInterface;
use Magento\CatalogInventory\Api\Data\StockItemInterface;
use Magento\CatalogInventory\Api\StockRegistryInterface;
use Magento\ConfigurableProduct\Model\Product\Type\Configurable;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Stdlib\DateTime\TimezoneInterface;
use Magento\Quote\Api\CartItemRepositoryInterface;
use Magento\Quote\Api\CartManagementInterface;
use Magento\Quote\Api\CartRepositoryInterface;
use Magento\Quote\Api\CartTotalRepositoryInterface;
use Magento\Quote\Api\Data\CartInterface;
use Magento\Quote\Api\Data\PaymentInterface;
use Magento\Quote\Model\QuoteFactory;
use Magento\Quote\Model\QuoteIdMask;
use Magento\Quote\Model\QuoteIdMaskFactory;
use Magento\Store\Model\ScopeInterface;
use Pratech\Base\Helper\Data as BaseHelper;
use Pratech\Base\Logger\Logger;
use Pratech\Cart\Api\CustomerPaymentManagementInterface;
use Pratech\Cart\Helper\Coupon;
use Pratech\Catalog\Helper\Product as ProductHelper;
use Pratech\Warehouse\Service\DeliveryDateCalculator;

class CartService
{
    /**
     * Config Paths
     */
    public const CROSS_SELL_ENABLE_CONFIG_PATH = 'cart/cross_sell/enable';
    public const CROSS_SELL_MAX_NUMBER_CONFIG_PATH = 'cart/cross_sell/max_number';
    public const CROSS_SELL_MODE_CONFIG_PATH = 'cart/cross_sell/mode';

    /**
     * @param CartManagementInterface $customerCartManagement
     * @param CartRepositoryInterface $quoteRepository
     * @param QuoteIdMaskFactory $quoteIdMaskFactory
     * @param CartItemRepositoryInterface $customerCartItemRepository
     * @param CartTotalRepositoryInterface $customerCartTotalRepository
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
        private ScopeConfigInterface               $scopeConfig,
        private ProductRepositoryInterface         $productRepository
    )
    {
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
    public function getCart(int $cartId)
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
        return $this->productRepository->getProductById($productId, $pincode,'minicart');
    }
}
