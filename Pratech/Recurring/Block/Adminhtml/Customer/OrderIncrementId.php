<?php

namespace Pratech\Recurring\Block\Adminhtml\Customer;

/**
 * Adminhtml block action item renderer
 */
class OrderIncrementId extends \Magento\Backend\Block\Widget\Grid\Column\Renderer\AbstractRenderer
{
    /**
     * @var \Magento\Sales\Model\OrderFactory
     */
    protected $orderFactory;
    /**
     * @param \Magento\Backend\Block\Context $context
     * @param \Magento\Sales\Model\OrderFactory $orderFactory
     * @param array $data
     */
    public function __construct(
        \Magento\Backend\Block\Context $context,
        \Magento\Sales\Model\OrderFactory $orderFactory,
        array $data = []
    ) {
        $this->orderFactory = $orderFactory;
        parent::__construct($context, $data);
    }

    /**
     * Render data
     *
     * @param \Magento\Framework\DataObject $row
     * @return string
     */
    public function render(\Magento\Framework\DataObject $row)
    {
        $orderName = '';
        if ($row->getData('order_id')) {
            $orderName = $this->orderFactory->create()->load($row->getData('order_id'))->getIncrementId();
            $orderName = "<a href='".$this->getUrl(
                'sales/order/view',
                ['order_id' => $row->getData('order_id')]
            )."' >#".$orderName."</a>";
        }
        return $orderName;
    }
}
