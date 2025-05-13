<?php
/**
 * Pratech_Promotion
 *
 * PHP version 8.x
 *
 * @category  PHP
 * @package   Pratech\Promotion
 * @author    Puneet Mittal <puneet.mittal@pratechbrands.com>
 * @copyright 2024 Copyright (c) Pratech Brands Private Limited
 * @link      https://pratechbrands.com/
 **/

namespace Pratech\Promotion\Model;

use Magento\Framework\Model\AbstractModel;

class PromoCodeUsage extends AbstractModel
{
    /**
     * Model constructor
     */
    protected function _construct(): void
    {
        $this->_init(ResourceModel\PromoCodeUsage::class);
    }

    /**
     * Load By Customer ID.
     *
     * @param int $customerId
     * @return $this
     */
    public function loadByCustomerId(int $customerId)
    {
        $this->load($customerId, 'customer_id');
        return $this;
    }

    /**
     * Load By Code ID.
     *
     * @param int $codeId
     * @return $this
     */
    public function loadByCode(int $codeId)
    {
        $this->load($codeId, 'code_id');
        return $this;
    }
}
