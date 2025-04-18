<?php

namespace Hyuga\Cart\Api;

use Magento\Framework\Exception\NoSuchEntityException;

interface CartManagementInterface
{

    /**
     * Get cross-sell products of cart items.
     *
     * @param string $type Possible values: customer|guest
     * @param string $cartId The cart ID.
     * @param int|null $pincode
     * @return array
     * @throws NoSuchEntityException
     */
    public function getCartCrossSellProducts(string $type, string $cartId, int $pincode = null): array;
}
