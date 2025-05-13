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

namespace Pratech\Promotion\Model\ResourceModel;

use Magento\Framework\Model\ResourceModel\Db\AbstractDb;

class Campaign extends AbstractDb
{
    /**
     * @var string
     */
    protected $_idFieldName = 'campaign_id';

    /**
     * Construct
     *
     * @return void
     */
    protected function _construct(): void
    {
        $this->_init('pratech_promotion_campaign', 'campaign_id');
    }
}
