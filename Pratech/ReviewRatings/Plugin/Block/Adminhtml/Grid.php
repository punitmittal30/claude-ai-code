<?php
/**
 * Pratech_ReviewRatings
 *
 * PHP version 8.x
 *
 * @category  PHP
 * @package   Pratech\ReviewRatings
 * @author    Vivek Kumar <vivek.kumar@pratechbrands.com>
 * @copyright 2022 Copyright (c) Pratech Brands Private Limited
 * @link      https://pratechbrands.com/
 **/

namespace Pratech\ReviewRatings\Plugin\Block\Adminhtml;

use Magento\Backend\Block\Template\Context;
use Magento\Backend\Block\Widget\Grid\Extended;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\DataObject;
use Magento\Framework\Registry;
use Magento\Review\Block\Adminhtml\Grid\Renderer\Type;
use Magento\Review\Helper\Action\Pager;
use Magento\Review\Helper\Data;
use Magento\Review\Model\ResourceModel\Review\Product\Collection;
use Magento\Review\Model\ResourceModel\Review\Product\CollectionFactory;
use Magento\Review\Model\Review;
use Magento\Review\Model\ReviewFactory;
use Magento\Store\Model\ScopeInterface;

/**
 * Adminhtml reviews grid
 *
 * @method int getProductId()
 * @method Grid setProductId(int $productId)
 * @method int getCustomerId()
 * @method Grid setCustomerId(int $customerId)
 * @method Grid setMassactionIdFieldOnlyIndexValue(bool $onlyIndex)
 */
class Grid extends Extended
{
    /**
     * Review action pager variable
     *
     * @var Pager
     */
    protected $_reviewActionPager = null;

    /**
     * Review data variable
     *
     * @var Data
     */
    protected $_reviewData = null;

    /**
     * Core registry variable
     *
     * @var Registry
     */
    protected $_coreRegistry = null;

    /**
     * Review collection model factory
     *
     * @var CollectionFactory
     */
    protected $_productsFactory;

    /**
     * Review model factory
     *
     * @var ReviewFactory
     */
    protected $_reviewFactory;

    /**
     * @param Context $context
     * @param \Magento\Backend\Helper\Data $backendHelper
     * @param ReviewFactory $reviewFactory
     * @param CollectionFactory $productsFactory
     * @param Data $reviewData
     * @param Pager $reviewActionPager
     * @param Registry $coreRegistry
     * @param ScopeConfigInterface $scopeConfig
     * @param array $data
     */
    public function __construct(
        Context                        $context,
        \Magento\Backend\Helper\Data   $backendHelper,
        ReviewFactory                  $reviewFactory,
        CollectionFactory              $productsFactory,
        Data                           $reviewData,
        Pager                          $reviewActionPager,
        Registry                       $coreRegistry,
        protected ScopeConfigInterface $scopeConfig,
        protected array                $data = []
    ) {
        $this->_productsFactory = $productsFactory;
        $this->_coreRegistry = $coreRegistry;
        $this->_reviewData = $reviewData;
        $this->_reviewActionPager = $reviewActionPager;
        $this->_reviewFactory = $reviewFactory;
        parent::__construct($context, $backendHelper, $data);
    }

    /**
     * Get row url
     *
     * @param Review|DataObject $row
     * @return string
     */
    public function getRowUrl($row)
    {
        return $this->getUrl(
            'review/product/edit',
            [
                'id' => $row->getReviewId(),
                'productId' => $this->getProductId(),
                'customerId' => $this->getCustomerId(),
                'ret' => $this->_coreRegistry->registry('usePendingFilter') ? 'pending' : null
            ]
        );
    }

    /**
     * Get grid url
     *
     * @return string
     */
    public function getGridUrl()
    {
        if ($this->getProductId() || $this->getCustomerId()) {
            return $this->getUrl(
                'review/product' . ($this->_coreRegistry->registry('usePendingFilter') ? 'pending' : ''),
                ['productId' => $this->getProductId(), 'customerId' => $this->getCustomerId()]
            );
        } else {
            return $this->getCurrentUrl();
        }
    }

    /**
     * Initialize grid
     *
     * @return void
     */
    protected function _construct()
    {
        parent::_construct();
        $this->setId('reviewGrid');
        $this->setDefaultSort('created_at');
    }

    /**
     * Save search results
     *
     * @return \Magento\Backend\Block\Widget\Grid
     */
    protected function _afterLoadCollection()
    {
        /** @var $actionPager Pager */
        $actionPager = $this->_reviewActionPager;
        $actionPager->setStorageId('reviews');
        $actionPager->setItems($this->getCollection()->getResultingIds());

        return parent::_afterLoadCollection();
    }

    /**
     * @inheritDoc
     */
    protected function _prepareCollection()
    {
        /** @var $model Review */
        $model = $this->_reviewFactory->create();
        /** @var $collection Collection */
        $collection = $this->_productsFactory->create();

        if ($this->getProductId() || $this->getRequest()->getParam('productId', false)) {
            $productId = $this->getProductId();
            if (!$productId) {
                $productId = $this->getRequest()->getParam('productId');
            }
            $this->setProductId($productId);
            $collection->addEntityFilter($this->getProductId());
        }

        if ($this->getCustomerId() || $this->getRequest()->getParam('customerId', false)) {
            $customerId = $this->getCustomerId();
            if (!$customerId) {
                $customerId = $this->getRequest()->getParam('customerId');
            }
            $this->setCustomerId($customerId);
            $collection->addCustomerFilter($this->getCustomerId());
        }

        if ($this->_coreRegistry->registry('usePendingFilter') === true) {
            $collection->addStatusFilter($model->getPendingStatus());
        }

        $collection->addStoreData();

        $this->setCollection($collection);
        return parent::_prepareCollection();
    }

    /**
     * Prepare grid columns
     *
     * @return \Magento\Backend\Block\Widget\Grid
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    protected function _prepareColumns()
    {
        $this->addColumn(
            'review_id',
            [
                'header' => __('ID'),
                'filter_index' => 'rt.review_id',
                'index' => 'review_id',
                'header_css_class' => 'col-id',
                'column_css_class' => 'col-id'
            ]
        );

        $this->addColumn(
            'created_at',
            [
                'header' => __('Created'),
                'type' => 'datetime',
                'filter_index' => 'rt.created_at',
                'index' => 'review_created_at',
                'header_css_class' => 'col-date col-date-min-width',
                'column_css_class' => 'col-date'
            ]
        );

        if (!$this->_coreRegistry->registry('usePendingFilter')) {
            $this->addColumn(
                'status',
                [
                    'header' => __('Status'),
                    'type' => 'options',
                    'options' => $this->_reviewData->getReviewStatuses(),
                    'filter_index' => 'rt.status_id',
                    'index' => 'status_id'
                ]
            );
        }

        $this->addColumn(
            'title',
            [
                'header' => __('Title'),
                'filter_index' => 'rdt.title',
                'index' => 'title',
                'type' => 'text',
                'truncate' => 50,
                'escape' => true
            ]
        );

        $this->addColumn(
            'nickname',
            [
                'header' => __('Nickname'),
                'filter_index' => 'rdt.nickname',
                'index' => 'nickname',
                'type' => 'text',
                'truncate' => 50,
                'escape' => true,
                'header_css_class' => 'col-name',
                'column_css_class' => 'col-name'
            ]
        );

        $this->addColumn(
            'power_review',
            [
                'header' => __('Power Review'),
                'filter_index' => 'rdt.power_review',
                'index' => 'power_review',
                'type' => 'text',
                'truncate' => 50,
                'escape' => true,
                'header_css_class' => 'col-name',
                'column_css_class' => 'col-name'
            ]
        );

        $this->addColumn(
            'position',
            [
                'header' => __('Position'),
                'filter_index' => 'rdt.position',
                'index' => 'position',
                'type' => 'boolean',
                'truncate' => 50,
                'escape' => true,
                'header_css_class' => 'col-name',
                'column_css_class' => 'col-name'
            ]
        );

        $this->addColumn(
            'keywords',
            [
                'header' => __('Keywords'),
                'filter_index' => 'rdt.keywords',
                'index' => 'keywords',
                'type' => 'text',
                'truncate' => 50,
                'escape' => true,
                'header_css_class' => 'col-name',
                'column_css_class' => 'col-name'
            ]
        );

        $this->addColumn(
            'detail',
            [
                'header' => __('Review'),
                'index' => 'detail',
                'filter_index' => 'rdt.detail',
                'type' => 'text',
                'truncate' => 50,
                'nl2br' => true,
                'escape' => true
            ]
        );

        $this->addColumn(
            'rating',
            [
                'header' => __('Rating'),
                'filter_index' => 'rvt.value',
                'index' => 'rating',
                'type' => 'boolean',
                'truncate' => 50,
                'escape' => true,
                'header_css_class' => 'col-name',
                'column_css_class' => 'col-name'
            ]
        );

        /**
         * Check is single store mode
         */
        if (!$this->_storeManager->isSingleStoreMode()) {
            $this->addColumn(
                'visible_in',
                [
                    'header' => __('Visibility'),
                    'index' => 'stores',
                    'type' => 'store',
                    'store_view' => true,
                    'sortable' => false
                ]
            );
        }

        $this->addColumn(
            'type',
            [
                'header' => __('Type'),
                'type' => 'select',
                'index' => 'type',
                'filter' => \Magento\Review\Block\Adminhtml\Grid\Filter\Type::class,
                'renderer' => Type::class
            ]
        );

        $this->addColumn(
            'name',
            ['header' => __('Product'), 'type' => 'text', 'index' => 'name', 'escape' => true]
        );

        $this->addColumn(
            'sku',
            [
                'header' => __('SKU'),
                'type' => 'text',
                'index' => 'sku',
                'escape' => true
            ]
        );

        $this->addColumn(
            'action',
            [
                'header' => __('Action'),
                'type' => 'action',
                'getter' => 'getReviewId',
                'actions' => [
                    [
                        'caption' => __('Edit'),
                        'url' => [
                            'base' => 'review/product/edit',
                            'params' => [
                                'productId' => $this->getProductId(),
                                'customerId' => $this->getCustomerId(),
                                'ret' => $this->_coreRegistry->registry('usePendingFilter') ? 'pending' : null,
                            ],
                        ],
                        'field' => 'id',
                    ],
                ],
                'filter' => false,
                'sortable' => false
            ]
        );

        $block = $this->getLayout()->getBlock('grid.bottom.links');
        if ($block) {
            $this->setChild('grid.bottom.links', $block);
        }

        return parent::_prepareColumns();
    }

    /**
     * Prepare grid mass actions
     *
     * @return void
     */
    protected function _prepareMassaction()
    {
        $this->setMassactionIdField('review_id');
        $this->setMassactionIdFilter('rt.review_id');
        $this->setMassactionIdFieldOnlyIndexValue(true);
        $this->getMassactionBlock()->setFormFieldName('reviews');

        $enableExport = $this->scopeConfig->getValue(
            'review/export/enable',
            ScopeInterface::SCOPE_STORE
        );
        if ($enableExport) {
            $this->addExportType(
                $this->getUrl('review/review/export', ['_current' => true, '_use_rewrite' => true]),
                __('Export to CSV')
            );
        }

        $this->getMassactionBlock()->addItem(
            'delete',
            [
                'label' => __('Delete'),
                'url' => $this->getUrl(
                    '*/*/massDelete',
                    ['ret' => $this->_coreRegistry->registry('usePendingFilter') ? 'pending' : 'index']
                ),
                'confirm' => __('Are you sure?')
            ]
        );

        $statuses = $this->_reviewData->getReviewStatusesOptionArray();
        array_unshift($statuses, ['label' => '', 'value' => '']);
        $this->getMassactionBlock()->addItem(
            'update_status',
            [
                'label' => __('Update Status'),
                'url' => $this->getUrl(
                    '*/*/massUpdateStatus',
                    ['ret' => $this->_coreRegistry->registry('usePendingFilter') ? 'pending' : 'index']
                ),
                'additional' => [
                    'status' => [
                        'name' => 'status',
                        'type' => 'select',
                        'class' => 'required-entry',
                        'label' => __('Status'),
                        'values' => $statuses,
                    ],
                ]
            ]
        );
    }

    /**
     * @inheritdoc
     */
    protected function _prepareMassactionColumn()
    {
        parent::_prepareMassactionColumn();
        /** needs for correct work of mass action select functionality */
        $this->setMassactionIdField('rt.review_id');

        return $this;
    }
}
