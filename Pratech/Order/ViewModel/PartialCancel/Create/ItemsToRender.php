<?php
/**
 * Pratech_Order
 *
 * PHP version 8.x
 *
 * @category  PHP
 * @package   Pratech\Order
 * @author    Akash Panwar <akash.panwar@pratechbrands.com>
 * @copyright 2025 Copyright (c) Pratech Brands Private Limited
 * @link      https://pratechbrands.com/
 **/

declare(strict_types=1);

namespace Pratech\Order\ViewModel\PartialCancel\Create;

use Magento\Framework\View\Element\Block\ArgumentInterface;
use Pratech\Order\Block\Adminhtml\Order\PartialCancel\Create\Items;
use Magento\Sales\Model\Convert\OrderFactory;
use Magento\Sales\Model\Convert\Order as ConvertOrder;
use Magento\Sales\Model\Order\Item;

/**
 * View model for items for rendering
 */
class ItemsToRender implements ArgumentInterface
{
    /**
     * @param Items $items
     * @param OrderFactory $convertOrderFactory
     */
    public function __construct(
        private Items $items,
        private OrderFactory $convertOrderFactory
    ) {
    }

    /**
     * Return order items for rendering and make sure all its parents are included
     *
     * @return Item[]
     */
    public function getItems(): array
    {
        $parents = [];
        $items = [];
        foreach ($this->items->getOrder()->getAllItems() as $item) {
            $orderItem = $item;
            if ($orderItem->getChildrenItems()) {
                $parents[] = $orderItem->getItemId();
            }
        }
        foreach ($this->items->getOrder()->getAllItems() as $item) {
            $orderItemParent = $item->getParentItem();
            if ($orderItemParent && !in_array($orderItemParent->getItemId(), $parents)) {
                $itemParent = $orderItemParent;
                $items[] = $itemParent;
            }
            $items[] = $item;
        }
        return $items;
    }
}
