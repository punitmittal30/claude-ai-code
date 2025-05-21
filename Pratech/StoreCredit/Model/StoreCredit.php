<?php
/**
 * Pratech_StoreCredit
 *
 * PHP version 8.x
 *
 * @category  PHP
 * @package   Pratech\StoreCredit
 * @author    Vivek Kumar <vivek.kumar@pratechbrands.com>
 * @copyright 2022 Copyright (c) Pratech Brands Private Limited
 * @link      https://pratechbrands.com/
 **/

namespace Pratech\StoreCredit\Model;

use Pratech\Base\Model\Data\Response;
use Pratech\StoreCredit\Api\StoreCreditInterface;
use Pratech\StoreCredit\Helper\Data as StoreCreditHelper;

/**
 * Store Credit Class to expose customer balance endpoints.
 */
class StoreCredit implements StoreCreditInterface
{
    /**
     * SUCCESS CODE
     */
    private const SUCCESS_CODE = 200;

    /**
     * CART API RESOURCE
     */
    private const STORE_CREDIT_API_RESOURCE = 'store_credit';

    /**
     * Store Credit Constructor
     *
     * @param StoreCreditHelper $storeCreditHelper
     * @param Response          $response
     */
    public function __construct(
        private StoreCreditHelper $storeCreditHelper,
        private Response          $response
    ) {
    }

    /**
     * @inheritDoc
     */
    public function getStoreCreditTransaction(int $customerId): array
    {
        return $this->response->getResponse(
            self::SUCCESS_CODE,
            'success',
            self::STORE_CREDIT_API_RESOURCE,
            $this->storeCreditHelper->getStoreCreditTransaction($customerId)
        );
    }
}
