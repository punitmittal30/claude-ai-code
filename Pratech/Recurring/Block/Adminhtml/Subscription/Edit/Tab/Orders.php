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
namespace Pratech\Recurring\Block\Adminhtml\Subscription\Edit\Tab;

use Magento\Backend\Block\Template\Context;
use Magento\Backend\Helper\Data as BackendHelper;
use Magento\Framework\Registry;
use Pratech\Recurring\Block\Adminhtml\Customer\OrderIncrementId;
use Pratech\Recurring\Model\SubscriptionMappingFactory;

/**
 * Adminhtml Orders grid block
 *
 * @api
 */
class Orders extends \Magento\Backend\Block\Widget\Grid\Extended
{
    /**
     * @param Context $context
     * @param BackendHelper $backendHelper
     * @param SubscriptionMappingFactory $subscriptionMappingFactory
     * @param Registry $coreRegistry
     * @param array $data
     */
    public function __construct(
        Context $context,
        BackendHelper $backendHelper,
        protected SubscriptionMappingFactory $subscriptionMappingFactory,
        protected Registry $coreRegistry,
        array $data = []
    ) {
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
                'renderer' => OrderIncrementId::class
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
