<?php

namespace Pratech\Promotion\Model\Spi;

/**
 * Interface ResourceInterface
 *
 * @api
 * @since 100.0.2
 */
interface PromoCodeResourceInterface
{
    /**
     * Save object data
     *
     * @param \Magento\Framework\Model\AbstractModel $object
     * @return $this
     */
    public function save(\Magento\Framework\Model\AbstractModel $object);

    /**
     * Load an object
     *
     * @param \Magento\Framework\Model\AbstractModel $object
     * @param $value
     * @param $field
     * @return mixed
     */
    public function load(\Magento\Framework\Model\AbstractModel $object, $value, $field = null);

    /**
     * Delete the object
     *
     * @param \Magento\Framework\Model\AbstractModel $object
     * @return mixed
     */
    public function delete(\Magento\Framework\Model\AbstractModel $object);
}
