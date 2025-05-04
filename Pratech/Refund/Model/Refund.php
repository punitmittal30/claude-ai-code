<?php
/**
 * Pratech_Refund
 *
 * PHP version 8.x
 *
 * @category  PHP
 * @package   Pratech\Refund
 * @author    Puneet Mittal <puneet.mittal@pratechbrands.com>
 * @copyright 2024 Copyright (c) Pratech Brands Private Limited
 * @link      https://pratechbrands.com/
 **/

namespace Pratech\Refund\Model;

use Magento\Framework\Model\AbstractModel;

class Refund extends AbstractModel
{
    /**
     * Model constructor
     */
    protected function _construct(): void
    {
        $this->_init(ResourceModel\Refund::class);
    }
}
