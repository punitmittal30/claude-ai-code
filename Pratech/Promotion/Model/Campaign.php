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

class Campaign extends AbstractModel
{
    /**
     * Model constructor
     */
    protected function _construct(): void
    {
        $this->_init(ResourceModel\Campaign::class);
    }
}
