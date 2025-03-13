<?php

namespace Pratech\Warehouse\Api\Data;

interface InventoryListInterface
{
    /**
     * Get SKU Code.
     *
     * @return string
     */
    public function getSkucode();

    /**
     * Set Sku code.
     *
     * @param string $skucode
     * @return $this
     */
    public function setSkucode($skucode);

    /**
     * Get Location.
     *
     * @return string
     */
    public function getLocation();

    /**
     * Set Location.
     *
     * @param string $location
     * @return $this
     */
    public function setLocation($location);

    /**
     * Get Qty.
     *
     * @return string
     */
    public function getQty();

    /**
     * Set Qty.
     *
     * @param string $qty
     * @return $this
     */
    public function setQty($qty);

    /**
     * Get Lot.
     *
     * @return string
     */
    public function getLot();

    /**
     * Set Lot.
     *
     * @param string $lot
     * @return $this
     */
    public function setLot($lot);

    /**
     * Get Lottable 01
     *
     * @return string|null
     */
    public function getLottable01();

    /**
     * Set Lottable 01.
     *
     * @param string|null $lottable01
     * @return $this
     */
    public function setLottable01($lottable01);

    /**
     * Get Lottable 03.
     *
     * @return string|null
     */
    public function getLottable03();

    /**
     * Set Lottable 03.
     *
     * @param string|null $lottable03
     * @return $this
     */
    public function setLottable03($lottable03);

    /**
     * Get Lottable 06.
     *
     * @return string|null
     */
    public function getLottable06();

    /**
     * Set Lottable 06
     *
     * @param string|null $lottable06
     * @return $this
     */
    public function setLottable06($lottable06);

    /**
     * Get Client Id.
     *
     * @return string
     */
    public function getClientId();

    /**
     * Set Client Id.
     *
     * @param string $clientId
     * @return $this
     */
    public function setClientId($clientId);

    /**
     * Get Org Id.
     *
     * @return string
     */
    public function getOrgId();

    /**
     * Set Org Id.
     *
     * @param string $orgId
     * @return $this
     */
    public function setOrgId($orgId);
}
