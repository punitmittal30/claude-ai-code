<?php
/**
 * Pratech_Search
 *
 * PHP version 8.x
 *
 * @category  PHP
 * @package   Pratech\Search
 * @author    Vivek Kumar <vivek.kumar@pratechbrands.com>
 * @copyright 2023 Copyright (c) Pratech Brands Private Limited
 * @link      https://pratechbrands.com/
 **/

namespace Pratech\Search\Model;

use Magento\Framework\Model\AbstractModel;

class SearchTerms extends AbstractModel
{
    /**
     * @var string
     */
    protected $_eventPrefix = 'search';

    /**
     * Model constructor
     */
    protected function _construct()
    {
        $this->_init(ResourceModel\SearchTerms::class);
    }
}
