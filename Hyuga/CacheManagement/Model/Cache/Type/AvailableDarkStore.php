<?php
/**
 * Hyuga_CacheManagement
 *
 * PHP version 8.x
 *
 * @category  PHP
 * @package   Hyuga\CacheManagement
 * @author    Puneet Mittal <puneet.mittal@pratechbrands.com>
 * @copyright 2025 Copyright (c) Pratech Brands Private Limited
 * @link      https://pratechbrands.com/
 **/

namespace Hyuga\CacheManagement\Model\Cache\Type;

use Magento\Framework\App\Cache\Type\FrontendPool;
use Magento\Framework\Cache\Frontend\Decorator\TagScope;

class AvailableDarkStore extends TagScope
{
    public const TYPE_IDENTIFIER = 'hyuga_available_dark_store';
    public const CACHE_TAG = 'available_dark_stores';

    /**
     * @param FrontendPool $frontendPool
     */
    public function __construct(FrontendPool $frontendPool)
    {
        parent::__construct($frontendPool->get(self::TYPE_IDENTIFIER), self::CACHE_TAG);
    }
}
