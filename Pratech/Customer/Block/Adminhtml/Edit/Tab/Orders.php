<?php
/**
 * Pratech_Customer
 *
 * PHP version 8.x
 *
 * @category  PHP
 * @package   Pratech\Customer
 * @author    Puneet Mittal <puneet.mittal@pratechbrands.com>
 * @copyright 2024 Copyright (c) Pratech Brands Private Limited
 * @link      https://pratechbrands.com/
 **/

namespace Pratech\Customer\Block\Adminhtml\Edit\Tab;

use Magento\Backend\Block\Template\Context;
use Magento\Backend\Helper\Data;
use Magento\Customer\Block\Adminhtml\Edit\Tab\Orders as BaseOrders;
use Magento\Framework\Registry;
use Magento\Framework\View\Element\UiComponent\DataProvider\CollectionFactory;
use Magento\Sales\Helper\Reorder;
use Magento\Sales\Model\Order\Config as OrderConfig;

class Orders extends BaseOrders
{
    /**
     * @var OrderConfig
     */
    protected $orderConfig;

    /**
     * Orders constructor.
     *
     * @param Context $context
     * @param Data $backendHelper
     * @param CollectionFactory $collectionFactory
     * @param Reorder $salesReorder
     * @param Registry $coreRegistry
     * @param OrderConfig $orderConfig
     * @param array $data
     */
    public function __construct(
        Context           $context,
        Data              $backendHelper,
        CollectionFactory $collectionFactory,
        Reorder           $salesReorder,
        Registry          $coreRegistry,
        OrderConfig       $orderConfig,
        array             $data = []
    ) {
        $this->orderConfig = $orderConfig;
        parent::__construct(
            $context,
            $backendHelper,
            $collectionFactory,
            $salesReorder,
            $coreRegistry,
            $data
        );
    }

    /**
     * Set Collection Method.
     *
     * @param $collection
     * @return void
     */
    public function setCollection($collection)
    {
        $collection->addFieldToSelect('status');
        parent::setCollection($collection);
    }

    /**
     * Prepare Columns Method
     *
     * @return $this|BaseOrders|Orders
     */
    protected function _prepareColumns(): BaseOrders|Orders|static
    {
        parent::_prepareColumns();

        $this->addColumnAfter('status', [
            'header' => __('Status'),
            'index' => 'status',
            'type' => 'options',
            'width' => '70px',
            'options' => $this->orderConfig->getStatuses(),
        ], 'store_id');

        $this->sortColumnsByOrder();
        return $this;
    }
}
