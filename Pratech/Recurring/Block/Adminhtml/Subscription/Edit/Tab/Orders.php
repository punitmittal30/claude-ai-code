<?php

namespace Pratech\Recurring\Block\Adminhtml\Subscription\Edit\Tab;

/**
 * Adminhtml Orders grid block
 *
 * @api
 */
class Orders extends \Magento\Backend\Block\Widget\Grid\Extended
{
    /**
     * @var \Magento\Framework\Registry
     */
    protected $coreRegistry = null;

    /**
     * @var \Pratech\Recurring\Model\SubscriptionMappingFactory
     */
    protected $subscriptionMappingFactory;

    /**
     * @param \Magento\Backend\Block\Template\Context $context
     * @param \Magento\Backend\Helper\Data $backendHelper
     * @param \Pratech\Recurring\Model\SubscriptionMappingFactory $subscriptionMappingFactory
     * @param \Magento\Framework\Registry $coreRegistry
     * @param array $data
     */
    public function __construct(
        \Magento\Backend\Block\Template\Context $context,
        \Magento\Backend\Helper\Data $backendHelper,
        \Pratech\Recurring\Model\SubscriptionMappingFactory $subscriptionMappingFactory,
        \Magento\Framework\Registry $coreRegistry,
        array $data = []
    ) {
        $this->coreRegistry = $coreRegistry;
        $this->subscriptionMappingFactory = $subscriptionMappingFactory;
        parent::__construct($context, $backendHelper, $data);
    }

    /**
     * @inheritdoc
     */
    protected function _construct()
    {
        parent::_construct();
        $this->setId('subscription_orders_grid');
        $this->setDefaultSort('created_at', 'desc');
        $this->setUseAjax(true);
    }

    /**
     * Apply various selection filters to prepare the sales order grid collection.
     *
     * @return $this
     */
    protected function _prepareCollection()
    {
        $id = $this->coreRegistry->registry('subscription_data')->getId();
        
        $collection = $this->subscriptionMappingFactory->create()->getCollection()
        ->addFieldToFilter(
            'subscription_id',
            $id
        );
        $this->setCollection($collection);
        return parent::_prepareCollection();
    }

    /**
     * @inheritdoc
     */
    protected function _prepareColumns()
    {
        $this->addColumn(
            'subscription_mapping_id',
            [
                'header' => __('ID'),
                'width' => '100',
                'index' => 'subscription_mapping_id'
            ]
        );
        
        $this->addColumn(
            'order_id',
            [
                'header' => __('Order ID'),
                'width' => '100',
                'index' => 'order_id',
                'renderer' => \Pratech\Recurring\Block\Adminhtml\Customer\OrderIncrementId::class
            ]
        );

        $this->addColumn(
            'created_at',
            ['header' => __('Created At'), 'index' => 'created_at', 'type' => 'datetime']
        );
        
        return parent::_prepareColumns();
    }

    /**
     * @inheritdoc
     */
    public function getGridUrl()
    {
        return $this->getUrl('pratech_recurring/subscription/orders', ['_current' => true]);
    }
}
