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

/**
 * Interface RequestItemInterface
 */
interface ReturnItemInterface
{
    public const ORDER_ITEM_ID = 'order_item_id';
    public const REASON_ID = 'reason_id';
    public const SKU = 'sku';
    public const QTY = 'qty';
    public const MEDIADATA = 'mediaData';
    public const COMMENT = 'comment';

    /**
     * Get Order Item Id
     *
     * @return int
     */
    public function getOrderItemId();

    /**
     * Set Item Id
     *
     * @param  int $orderItemId
     * @return $this
     */
    public function setOrderItemId(int $orderItemId);

    /**
     * Get Reason Id
     *
     * @return int
     */
    public function getReasonId();

    /**
     * Set Reason Id
     *
     * @param  int $reasonId
     * @return $this
     */
    public function setReasonId(int $reasonId);

    /**
     * Get Item Sku
     *
     * @return string
     */
    public function getSku();

    /**
     * Set Item Sku
     *
     * @param  string $sku
     * @return $this
     */
    public function setSku(string $sku);

    /**
     * Get Item Qty
     *
     * @return int
     */
    public function getQty();

    /**
     * Set Item Qty
     *
     * @param  int $qty
     * @return $this
     */
    public function setQty(int $qty);

    /**
     * Get Media Data
     *
     * @return string[]
     */
    public function getMediaData();

    /**
     * Set Media Data
     *
     * @param  string[] $mediaData
     * @return $this
     */
    public function setMediaData(array $mediaData);

    /**
     * Get Comment
     *
     * @return string
     */
    public function getComment();

    /**
     * Set Comment
     *
     * @param  string $comment
     * @return $this
     */
    public function setComment(string $comment);
}
