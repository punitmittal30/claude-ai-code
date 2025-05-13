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

namespace Pratech\Cart\Model;

use Pratech\Base\Model\Data\Response;
use Pratech\Cart\Api\StoreCreditInterface;
use Pratech\Cart\Helper\StoreCredit as StoreCreditHelper;

/**
 * Store Credit Management Class to expose customer balance endpoints.
 */
class StoreCreditManagement implements StoreCreditInterface
{
    /**
     * SUCCESS CODE
     */
    private const SUCCESS_CODE = 200;

    /**
     * CART API RESOURCE
     */
    private const CART_API_RESOURCE = 'cart';

    /**
     * Store Credit Management Constructor
     *
     * @param StoreCreditHelper $storeCreditHelper
     * @param Response $response
     */
    public function __construct(
        private StoreCreditHelper $storeCreditHelper,
        private Response          $response
    ) {
    }

    /**
     * @inheritDoc
     */
    public function apply(int $cartId)
    {
        return $this->response->getResponse(
            self::SUCCESS_CODE,
            'success',
            self::CART_API_RESOURCE,
            [
                "applied" => $this->storeCreditHelper->apply($cartId)
            ]
        );
    }

    /**
     * @inheritDoc
     */
    public function remove(int $cartId)
    {
        return $this->response->getResponse(
            self::SUCCESS_CODE,
            'success',
            self::CART_API_RESOURCE,
            [
                "removed" => $this->storeCreditHelper->remove($cartId)
            ]
        );
    }
}
