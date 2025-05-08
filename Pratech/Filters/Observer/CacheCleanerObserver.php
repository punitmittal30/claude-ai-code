<?php
/**
 * Pratech_Filters
 *
 * PHP version 8.x
 *
 * @category  PHP
 * @package   Pratech\Filters
 * @author    Vivek Kumar <vivek.kumar@pratechbrands.com>
 * @copyright 2024 Copyright (c) Pratech Brands Private Limited
 * @link      https://pratechbrands.com/
 **/

namespace Pratech\Filters\Observer;

use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Pratech\Filters\Helper\Filter as FilterHelper;

/**
 * Observer for cleaning cache when filters are modified
 */
class CacheCleanerObserver implements ObserverInterface
{
    /**
     * @param FilterHelper $filterHelper
     */
    public function __construct(
        private FilterHelper $filterHelper
    ) {
    }

    /**
     * Execute observer
     *
     * @param Observer $observer
     * @return void
     */
    public function execute(Observer $observer)
    {
        $eventName = $observer->getEvent()->getName();

        if (str_contains($eventName, 'filters_position_controller')) {
            $this->filterHelper->cleanFiltersPositionCache();
        } elseif (str_contains($eventName, 'quick_filter_controller')) {
            $this->filterHelper->cleanQuickFiltersCache();
        }
    }
}
