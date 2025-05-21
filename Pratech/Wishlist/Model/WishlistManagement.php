<?php
/**
 * Pratech_Wishlist
 *
 * PHP version 8.x
 *
 * @category  PHP
 * @package   Pratech\Wishlist
 * @author    Puneet Mittal <puneet.mittal@pratechbrands.com>
 * @copyright 2022 Copyright (c) Pratech Brands Private Limited
 * @link      https://pratechbrands.com/
 **/

namespace Pratech\Wishlist\Model;

use Exception;
use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Framework\Exception\InputException;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Wishlist\Model\Item as WishlistItem;
use Magento\Wishlist\Model\ItemFactory as WishlistItemFactory;
use Magento\Wishlist\Model\ResourceModel\Item as WishlistItemResource;
use Magento\Wishlist\Model\ResourceModel\Item\Collection;
use Magento\Wishlist\Model\ResourceModel\Item\CollectionFactory;
use Magento\Wishlist\Model\Wishlist;
use Magento\Wishlist\Model\WishlistFactory;
use Pratech\Base\Model\Data\Response;
use Pratech\Catalog\Helper\Product;
use Pratech\Wishlist\Api\WishlistManagementInterface;

/**
 * WishlistManagement to manage wishlist content.
 */
class WishlistManagement implements WishlistManagementInterface
{
    /**
     * SUCCESS CODE
     */
    private const SUCCESS_CODE = 200;

    /**
     * ERROR CODE
     */
    private const ERROR_CODE = 404;

    /**
     * WISHLIST API RESOURCE
     */
    private const WISHLIST_API_RESOURCE = 'wishlist';

    /**
     * WishlistManagement Constructor
     *
     * @param CollectionFactory $wishlistCollectionFactory
     * @param WishlistFactory $wishlistFactory
     * @param ProductRepositoryInterface $productRepository
     * @param WishlistItemFactory $wishlistItemFactory
     * @param WishlistItemResource $wishlistItemResource
     * @param Product $productHelper
     * @param Response $response
     */
    public function __construct(
        private CollectionFactory          $wishlistCollectionFactory,
        private WishlistFactory            $wishlistFactory,
        private ProductRepositoryInterface $productRepository,
        private WishlistItemFactory        $wishlistItemFactory,
        private WishlistItemResource       $wishlistItemResource,
        private Product                    $productHelper,
        private Response                   $response,
    )
    {
    }

    /**
     * @inheritDoc
     */
    public function getWishlistForCustomer(int $customerId, int $pincode = null): array
    {
        if (empty($customerId) || $customerId == "") {
            throw new InputException(__('Customer ID required'));
        } else {
            /** @var Collection $collection */
            $collection = $this->wishlistCollectionFactory->create()->addCustomerIdFilter($customerId);
            $wishlistData = $data = [];
            foreach ($collection as $item) {
                try {
                    $productInfo = $this->productRepository->getById($item->getProductId());
                } catch (NoSuchEntityException $exception) {
                    throw new NoSuchEntityException(__($exception->getMessage() . __METHOD__));
                }
                $formattedProduct = $this->productHelper->formatProductForCarousel($productInfo->getId(), $pincode);
                if (!empty($formattedProduct)) {
                    $data = [
                        "wishlist_item_id" => $item->getWishlistItemId(),
                        "wishlist_id" => $item->getWishlistId(),
                        "product_id" => $item->getProductId(),
                        "added_at" => $item->getAddedAt(),
                        "description" => $item->getDescription(),
                        "qty" => round($item->getQty()),
                        "product" => $formattedProduct,
                    ];
                }
                $wishlistData[] = $data;
            }
            return $this->response->getResponse(
                self::SUCCESS_CODE,
                'success',
                self::WISHLIST_API_RESOURCE,
                $wishlistData
            );
        }
    }

    /**
     * @inheritDoc
     */
    public function addItemToWishlist($customerId, $productId): array
    {
        if ($productId == null) {
            throw new LocalizedException(__('Invalid product, Please select a valid product'));
        }
        try {
            /** @var Wishlist $wishlist */
            $wishlist = $this->wishlistFactory->create()->loadByCustomerId($customerId, true);
            $wishlist->addNewItem($productId);
            $item = $wishlist->save();
            return $this->response->getResponse(
                self::SUCCESS_CODE,
                'success',
                self::WISHLIST_API_RESOURCE,
                $item->getData()
            );
        } catch (Exception $exception) {
            throw new Exception(__($exception->getMessage() . __METHOD__));
        }
    }

    /**
     * @inheritDoc
     */
    public function removeItemFromWishlist(int $customerId, int $wishlistItemId): array
    {
        try {
            $collection = $this->wishlistCollectionFactory->create()->addCustomerIdFilter($customerId);
            if ($collection->getItemById($wishlistItemId) == null) {
                throw new LocalizedException(
                    __(
                        'The wishlist item with ID "%id" does not belong to the wishlist',
                        ['id' => $wishlistItemId]
                    )
                );
            }
            $collection->clear();
            /** @var WishlistItem $wishlistItem */
            $wishlistItem = $this->wishlistItemFactory->create();
            $this->wishlistItemResource->load($wishlistItem, $wishlistItemId);
            if (!$wishlistItem->getId()) {
                throw new NoSuchEntityException(
                    __('Could not find a wishlist item with ID "%id"', ['id' => $wishlistItemId])
                );
            }

            $this->wishlistItemResource->delete($wishlistItem);
            return $this->response->getResponse(
                self::SUCCESS_CODE,
                'success',
                self::WISHLIST_API_RESOURCE,
                [
                    'deleted' => true
                ]
            );
        } catch (LocalizedException $exception) {
            throw new LocalizedException(__($exception->getMessage()));
        } catch (Exception $e) {
            throw new Exception(__($e->getMessage()));
        }
    }
}
