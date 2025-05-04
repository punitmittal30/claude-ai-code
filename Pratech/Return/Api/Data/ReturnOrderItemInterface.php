<?php
/**
 * Pratech_Return
 *
 * PHP version 8.x
 *
 * @category  PHP
 * @package   Pratech\Return
 * @author    Vivek Kumar <vivek.kumar@pratechbrands.com>
 * @copyright 2024 Copyright (c) Pratech Brands Private Limited
 * @link      https://pratechbrands.com/
 **/
namespace Pratech\Return\Api\Data;

use Magento\Catalog\Api\Data\ProductInterface;
use Magento\Sales\Api\Data\OrderItemInterface;

/**
 * Interface ReturnOrderItemInterface
 */
interface ReturnOrderItemInterface
{
    /**
     * Constants defined for keys of data array
     */
    public const ITEM = 'item';
    public const PRODUCT_ITEM = 'product_item';
    public const AVAILABLE_QTY = 'available_qty';
    public const PURCHASED_QTY = 'purchased_qty';
    public const IS_RETURNABLE = 'is_returnable';
    public const NO_RETURNABLE_REASON = 'no_returnable_reason';
    public const NO_RETURNABLE_DATA = 'no_returnable_data';

    /**
     * @param OrderItemInterface $item
     *
     * @return $this
     */
    public function setItem($item);

    /**
     * @return OrderItemInterface
     */
    public function getItem();

    /**
     * @param ProductInterface|bool $productItem
     *
     * @return $this
     */
    public function setProductItem($productItem);

    /**
     * @return \Magento\Catalog\Api\Data\ProductInterface|bool
     */
    public function getProductItem();

    /**
     * @param double $qty
     *
     * @return $this
     */
    public function setAvailableQty($qty);

    /**
     * @return double
     */
    public function getAvailableQty();

    /**
     * @param double $qty
     *
     * @return $this
     */
    public function setPurchasedQty($qty);

    /**
     * @return double
     */
    public function getPurchasedQty();

    /**
     * @param bool $isReturnable
     *
     * @return $this
     */
    public function setIsReturnable($isReturnable);
    /**
     * @return bool
     */
    public function isReturnable();

    /**
     * @param int $reason
     *
     * @return $this
     */
    public function setNoReturnableReason($reason);

    /**
     * @return int
     */
    public function getNoReturnableReason();

    /**
     * @param array $data
     *
     * @return $this
     */
    public function setNoReturnableData($data);

    /**
     * @return array
     */
    public function getNoReturnableData();
}
