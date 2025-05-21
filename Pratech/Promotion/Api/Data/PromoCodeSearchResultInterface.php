<?php

namespace Pratech\Promotion\Api\Data;

/**
 * @api
 * @since 100.0.2
 */
interface PromoCodeSearchResultInterface extends \Magento\Framework\Api\SearchResultsInterface
{
    /**
     * Get promo codes.
     *
     * @return \Pratech\Promotion\Api\Data\PromoCodeInterface[]
     */
    public function getItems();

    /**
     * Set promo codes .
     *
     * @param \Pratech\Promotion\Api\Data\PromoCodeInterface[] $items
     * @return $this
     */
    public function setItems(array $items = null);
}
