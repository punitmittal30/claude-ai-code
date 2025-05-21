<?php
/**
 * Pratech_Order
 *
 * PHP version 8.x
 *
 * @category  PHP
 * @package   Pratech\Order
 * @author    Puneet Mittal <puneet.mittal@pratechbrands.com>
 * @copyright 2023 Copyright (c) Pratech Brands Private Limited
 * @link      https://pratechbrands.com/
 **/

namespace Pratech\Order\Api\Data;

/**
 * Interface ConfirmOrderRequestItemInterface
 *
 * @api
 */
interface ConfirmOrderRequestItemInterface
{
    public const ORDER_ID = 'order_id';

    public const RZP_ORDER_ID = 'rzp_order_id';

    public const RZP_PAYMENT_ID = 'rzp_payment_id';

    public const STATUS = 'status';

    public const SOURCE = 'source';

    /**
     * Get Magento Order ID
     *
     * @return int
     */
    public function getOrderId();

    /**
     * Set Magento Order ID
     *
     * @param int $orderId
     * @return $this
     */
    public function setOrderId(int $orderId);

    /**
     * Get Razor Pay Order ID
     *
     * @return string|null
     */
    public function getRzpOrderId();

    /**
     * Set Razor Pay Order ID
     *
     * @param string|null $rzpOrderId
     * @return $this
     */
    public function setRzpOrderId(?string $rzpOrderId);

    /**
     * Get Razor Pay Payment ID
     *
     * @return string|null
     */
    public function getRzpPaymentId();

    /**
     * Set Razor Pay Payment ID
     *
     * @param string|null $rzpPaymentId
     * @return $this
     */
    public function setRzpPaymentId(?string $rzpPaymentId);

    /**
     * Get Payment Status
     *
     * @return string
     */
    public function getStatus();

    /**
     * Set Razor Pay Payment ID
     *
     * @param string $status
     * @return $this
     */
    public function setStatus(string $status);

    /**
     * Get Payment Source
     *
     * @return string|null
     */
    public function getSource();

    /**
     * Set Payment Source
     *
     * @param string|null $source
     * @return $this
     */
    public function setSource(?string $source);
}
