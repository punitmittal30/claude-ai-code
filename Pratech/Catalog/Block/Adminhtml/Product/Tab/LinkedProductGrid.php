<?php
/**
 * Pratech_Catalog
 *
 * PHP version 8.x
 *
 * @category  PHP
 * @package   Pratech\Catalog
 * @author    Vivek Kumar <vivek.kumar@pratechbrands.com>
 * @copyright 2025 Copyright (c) Pratech Brands Private Limited
 * @link      https://pratechbrands.com/
 **/
namespace Pratech\Catalog\Block\Adminhtml\Product\Tab;

use Magento\Backend\Block\Widget\Grid\Extended;
use Magento\Catalog\Model\Product\Attribute\Source\Status;
use Magento\Catalog\Model\Product\Visibility;
use Magento\Framework\App\ObjectManager;
use Magento\Framework\Registry;
use Pratech\Catalog\Model\ResourceModel\LinkedProduct\CollectionFactory as LinkedCollectionFactory;
use Magento\Catalog\Model\ProductFactory;
use Magento\Backend\Helper\Data as BackendHelper;
use Magento\Backend\Block\Template\Context;

class LinkedProductGrid extends Extended
{
    /**
     * @var Visibility
     */
    private $visibility;

    /**
     * @var Status
     */
    private $status;

    /**
     * Constructor
     *
     * @param Context                 $context
     * @param BackendHelper           $backendHelper
     * @param ProductFactory          $productFactory
     * @param Registry                $coreRegistry
     * @param LinkedCollectionFactory $linkedCollectionFactory
     * @param array                   $data
     * @param Visibility|null         $visibility
     * @param Status|null             $status
     */
    public function __construct(
        Context                         $context,
        BackendHelper                   $backendHelper,
        private ProductFactory          $productFactory,
        private Registry                $coreRegistry,
        private LinkedCollectionFactory $linkedCollectionFactory,
        array                           $data = [],
        Visibility                      $visibility = null,
        Status                          $status = null
    ) {
        $this->visibility = $visibility ?: ObjectManager::getInstance()->get(Visibility::class);
        $this->status = $status ?: ObjectManager::getInstance()->get(Status::class);
        parent::__construct($context, $backendHelper, $data);
    }

    /**
     * @inheritdoc
     */
    protected function _construct()
    {
        parent::_construct();
        $this->setId('linked_configurable_grid');
        $this->setDefaultSort('entity_id');
        $this->setUseAjax(true);
    }

    /**
     * Get current product id
     *
     * @return int
     */
    public function getCurrentProductId(): int
    {
        return $this->coreRegistry->registry('current_product')
            ? $this->coreRegistry->registry('current_product')->getId()
            : $this->getRequest()->getParam('id');
    }

    /**
     * @inheritdoc
     */
    protected function _prepareCollection()
    {
        if ($this->getCurrentProductId()) {
            $this->setDefaultFilter(['in_linked_products' => 1]);
        }
        $collection = $this->productFactory->create()->getCollection()
            ->addAttributeToSelect(['name', 'sku', 'status', 'visibility', 'price'])
            ->addFieldToFilter('type_id', 'configurable')
            ->addFieldToFilter('entity_id', ['neq' => $this->getCurrentProductId()]);

        $this->setCollection($collection);

        return parent::_prepareCollection();
    }

    /**
     * @inheritdoc
     */
    protected function _addColumnFilterToCollection($column)
    {
        if ($column->getId() === 'in_linked_products') {
            $linkedIds = $this->_getSelectedLinkedProductIds();
            if (empty($linkedIds)) {
                $linkedIds = [0];
            }
            if ($column->getFilter()->getValue()) {
                $this->getCollection()->addFieldToFilter('entity_id', ['in' => $linkedIds]);
            } else {
                $this->getCollection()->addFieldToFilter('entity_id', ['nin' => $linkedIds]);
            }
        } else {
            parent::_addColumnFilterToCollection($column);
        }
        return $this;
    }

    /**
     * @inheritdoc
     */
    protected function _prepareColumns()
    {
        $this->addColumn(
            'in_linked_products',
            [
                'type' => 'checkbox',
                'name' => 'in_linked_products',
                'values' => $this->_getSelectedLinkedProductIds(),
                'index' => 'entity_id',
                'header_css_class' => 'col-select col-massaction',
                'column_css_class' => 'col-select col-massaction'
            ]
        );

        $this->addColumn(
            'entity_id',
            [
                'header' => __('ID'),
                'sortable' => true,
                'index' => 'entity_id',
                'header_css_class' => 'col-id',
                'column_css_class' => 'col-id'
            ]
        );

        $this->addColumn('name', ['header' => __('Name'), 'index' => 'name']);
        $this->addColumn('sku', ['header' => __('SKU'), 'index' => 'sku']);

        $this->addColumn(
            'visibility',
            [
                'header' => __('Visibility'),
                'index' => 'visibility',
                'type' => 'options',
                'options' => $this->visibility->getOptionArray(),
                'header_css_class' => 'col-visibility',
                'column_css_class' => 'col-visibility'
            ]
        );

        $this->addColumn(
            'status',
            [
                'header' => __('Status'),
                'index' => 'status',
                'type' => 'options',
                'options' => $this->status->getOptionArray()
            ]
        );

        return parent::_prepareColumns();
    }

    /**
     * Get selected linked product ids
     *
     * @return array
     */
    protected function _getSelectedLinkedProductIds(): array
    {
        return $this->getSelectedLinkedProductIds();
    }

    /**
     * Get selected linked product ids
     *
     * @return array
     */
    public function getSelectedLinkedProductIds(): array
    {
        $productId = (int)$this->getCurrentProductId();
        if (!$productId) {
            return [];
        }

        $collection = $this->linkedCollectionFactory->create();
        $collection->getSelect()->where('product_id = ? OR linked_product_id = ?', $productId, $productId);

        $linkedIds = [];
        foreach ($collection as $link) {
            if ((int)$link->getData('product_id') === $productId) {
                $linkedIds[] = (int)$link->getData('linked_product_id');
            } elseif ((int)$link->getData('linked_product_id') === $productId) {
                $linkedIds[] = (int)$link->getData('product_id');
            }
        }

        return array_unique($linkedIds);
    }

    /**
     * @inheritdoc
     */
    public function getGridUrl(): string
    {
        return $this->getUrl('pratech/product/linkedGrid', ['_current' => true]);
    }
}
