<?php

namespace Pratech\Promotion\Api\Data;

/**
 * Promo code mass delete results interface.
 *
 * @api
 * @since 100.0.2
 */
interface PromoCodeMassDeleteResultInterface
{
    /**
     * Get list of failed items.
     *
     * @return string[]
     */
    public function getFailedItems();

    /**
     * Set list of failed items.
     *
     * @param string[] $items
     * @return $this
     */
    public function setFailedItems(array $items);

    /**
     * Get list of missing items.
     *
     * @return string[]
     */
    public function getMissingItems();

    /**
     * Set list of missing items.
     *
     * @param string[] $items
     * @return $this
     */
    public function setMissingItems(array $items);
}
