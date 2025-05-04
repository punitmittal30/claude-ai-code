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

use Magento\Sales\Api\Data\OrderInterface;

/**
 * Interface ReturnOrderInterface
 */
interface ReturnOrderInterface
{
    /**
     * Constants defined for keys of data array
     */
    public const ORDER = 'order';
    public const ITEMS = 'items';

    /**
     * Set Order.
     *
     * @param  OrderInterface $order
     * @return $this
     */
    public function setOrder($order);

    /**
     * Get Order.
     *
     * @return OrderInterface
     */
    public function getOrder();

    /**
     * Get Items.
     *
     * @return ReturnOrderItemInterface[]
     */
    public function getItems();

    /**
     * Set Items,
     *
     * @param  ReturnOrderItemInterface[] $items
     * @return $this
     */
    public function setItems($items);
}
