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

namespace Pratech\Promotion\Model\ResourceModel\PromoCodeUsage;

use Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection;
use Pratech\Promotion\Model\ResourceModel\PromoCodeUsage;

class Collection extends AbstractCollection
{
    /**
     * Constructor to map model and resource model.
     */
    protected function _construct(): void
    {
        $this->_init(
            \Pratech\Promotion\Model\PromoCodeUsage::class,
            PromoCodeUsage::class
        );
    }
}
