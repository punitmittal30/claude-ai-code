<?php
/**
 * Pratech_Recurring
 *
 * PHP version 8.x
 *
 * @category  PHP
 * @package   Pratech\Recurring
 * @author    Akash Panwar <akash.panwarr@pratechbrands.com>
 * @copyright 2025 Copyright (c) Pratech Brands Private Limited
 * @link      https://pratechbrands.com/
 **/
namespace Pratech\Recurring\Block\Adminhtml\Customer;

use Magento\Backend\Block\Widget\Grid\Column\Renderer\AbstractRenderer;
use Magento\Backend\Block\Context;
use Magento\Framework\DataObject;
use Magento\Sales\Model\OrderFactory;

/**
 * Adminhtml block action item renderer
 */
class OrderIncrementId extends AbstractRenderer
{
    /**
     * @param Context $context
     * @param OrderFactory $orderFactory
     * @param array $data
     */
    public function __construct(
        Context $context,
        protected OrderFactory $orderFactory,
        array $data = []
    ) {
        parent::__construct($context, $data);
    }

    /**
     * Render data
     *
     * @param DataObject $row
     * @return string
     */
    public function render(DataObject $row)
    {
        $orderName = '';
        if ($row->getData('order_id')) {
            $orderName = $this->orderFactory->create()->load($row->getData('order_id'))->getIncrementId();
            $orderName = "<a target='_blank' href='".$this->getUrl(
                'sales/order/view',
                ['order_id' => $row->getData('order_id')]
            )."' >#".$orderName."</a>";
        }
        return $orderName;
    }
}
