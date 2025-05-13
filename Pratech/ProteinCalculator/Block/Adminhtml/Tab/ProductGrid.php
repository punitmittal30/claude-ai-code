<?php
/**
 * Pratech_ProteinCalculator
 *
 * PHP version 8.x
 *
 * @category  PHP
 * @package   Pratech\ProteinCalculator
 * @author    Himmat Singh <himmat.singh@pratechbrands.com>
 * @copyright 2024 Copyright (c) Pratech Brands Private Limited
 * @link      https://pratechbrands.com/
 **/
namespace Pratech\ProteinCalculator\Block\Adminhtml\Tab;

use Magento\Backend\Block\Template\Context;
use Magento\Backend\Block\Widget\Grid\Column;
use Pratech\ProteinCalculator\Model\ResourceModel\Diet\CollectionFactory;
use Magento\Backend\Helper\Data;
use Magento\Backend\Block\Widget\Grid\Extended;
use Magento\Framework\Exception\FileSystemException;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Catalog\Model\Product\Visibility;
use Magento\Framework\Module\Manager;
use Magento\Framework\App\ObjectManager;
use Magento\Store\Model\Store;
use Magento\Store\Api\Data\StoreInterface;
use Magento\Store\Model\StoreManagerInterface;
use Magento\Catalog\Model\ProductFactory;
use Magento\Framework\Registry;

class ProductGrid extends Extended
{
    /**
     * @var Registry
     */
    protected $coreRegistry = null;

    /**
     * @param Context               $context
     * @param Data                  $backendHelper
     * @param Registry              $coreRegistry
     * @param CollectionFactory     $productCollFactory
     * @param ProductFactory        $productFactory
     * @param Manager               $moduleManager
     * @param StoreManagerInterface $storeManager
     * @param Visibility|null       $visibility
     * @param array                 $data
     */
    public function __construct(
        Context $context,
        Data $backendHelper,
        Registry $coreRegistry,
        protected CollectionFactory $productCollFactory,
        protected ProductFactory $productFactory,
        protected Manager $moduleManager,
        protected StoreManagerInterface $storeManager,
        Visibility $visibility = null,
        array $data = []
    ) {
        $this->coreRegistry = $coreRegistry;
        $this->visibility = $visibility ?: ObjectManager::getInstance()->get(Visibility::class);
        parent::__construct($context, $backendHelper, $data);
    }

    /**
     * Get construct
     *
     * @return void
     * @throws FileSystemException
     */
    protected function _construct()
    {
        parent::_construct();
        $this->setId('calculator_grid_products');
        $this->setDefaultSort('entity_id');
        $this->setDefaultDir('ASC');
        $this->setUseAjax(true);
        if ($this->getRequest()->getParam('entity_id')) {
            $this->setDefaultFilter(['in_products' => 1]);
        } else {
            $this->setDefaultFilter(['in_products' => 0]);
        }
        $this->setSaveParametersInSession(true);
    }

    /**
     * Get Store.
     *
     * @return StoreInterface
     * @throws NoSuchEntityException
     */
    protected function _getStore()
    {
        $storeId = (int) $this->getRequest()->getParam('store', 0);
        return $this->_storeManager->getStore($storeId);
    }

    /**
     * Prepare Collection.
     *
     * @return ProductGrid
     * @throws NoSuchEntityException
     */
    protected function _prepareCollection()
    {
        $store = $this->_getStore();
        $collection = $this->productFactory->create()->getCollection()->addAttributeToSelect(
            'sku'
        )->addAttributeToSelect(
            'name'
        )->addAttributeToSelect(
            'attribute_set_id'
        )->addAttributeToSelect(
            'type_id'
        )->setStore(
            $store
        );

        if ($this->moduleManager->isEnabled('Magento_CatalogInventory')) {
            $collection->joinField(
                'qty',
                'cataloginventory_stock_item',
                'qty',
                'product_id=entity_id',
                '{{table}}.stock_id=1',
                'left'
            );
        }
        if ($store->getId()) {
            $collection->setStoreId($store->getId());
            $collection->addStoreFilter($store);
            $collection->joinAttribute(
                'name',
                'catalog_product/name',
                'entity_id',
                null,
                'inner',
                Store::DEFAULT_STORE_ID
            );
            $collection->joinAttribute(
                'status',
                'catalog_product/status',
                'entity_id',
                null,
                'inner',
                $store->getId()
            );
            $collection->joinAttribute(
                'visibility',
                'catalog_product/visibility',
                'entity_id',
                null,
                'inner',
                $store->getId()
            );
            $collection->joinAttribute('price', 'catalog_product/price', 'entity_id', null, 'left', $store->getId());
        } else {
            $collection->addAttributeToSelect('price');
            $collection->joinAttribute('status', 'catalog_product/status', 'entity_id', null, 'inner');
            $collection->joinAttribute('visibility', 'catalog_product/visibility', 'entity_id', null, 'inner');
        }
        $this->setCollection($collection);
        return parent::_prepareCollection();
    }

    /**
     * Add Column Filters
     *
     * @param Column $column
     * @return $this|ProductGrid
     * @throws LocalizedException
     */
    protected function _addColumnFilterToCollection($column)
    {
        if ($column->getId() == 'in_products') {
            $productIds = $this->_getSelectedProducts();
            if (empty($productIds)) {
                $productIds = 0;
            }
            if ($column->getFilter()->getValue()) {
                $this->getCollection()->addFieldToFilter('entity_id', ['in' => $productIds]);
            } else {
                if ($productIds) {
                    $this->getCollection()->addFieldToFilter('entity_id', ['nin' => $productIds]);
                }
            }
        } else {
            parent::_addColumnFilterToCollection($column);
        }
        return $this;
    }

    /**
     * Prepare Columns of Product Grid
     *
     * @return ProductGrid
     * @throws NoSuchEntityException
     * @throws Exception
     */
    protected function _prepareColumns()
    {
        $this->addColumn(
            'in_products',
            [
                'type' => 'checkbox',
                'html_name' => 'products_id',
                'required' => true,
                'values' => $this->_getSelectedProducts(),
                'align' => 'center',
                'index' => 'entity_id',
            ]
        );

        $this->addColumn(
            'entity_id',
            [
                'header' => __('ID'),
                'width' => '50px',
                'index' => 'entity_id',
                'type' => 'number',
            ]
        );
        $this->addColumn(
            'name',
            [
                'header' => __('Name'),
                'index' => 'name',
                'header_css_class' => 'col-type',
                'column_css_class' => 'col-type',
            ]
        );
        $this->addColumn(
            'sku',
            [
                'header' => __('SKU'),
                'index' => 'sku',
                'header_css_class' => 'col-sku',
                'column_css_class' => 'col-sku',
            ]
        );
        $store = $this->_getStore();
        $this->addColumn(
            'price',
            [
                'header' => __('Price'),
                'type' => 'price',
                'currency_code' => $store->getBaseCurrency()->getCode(),
                'index' => 'price',
                'header_css_class' => 'col-price',
                'column_css_class' => 'col-price',
            ]
        );
        $this->addColumn(
            'position',
            [
                'header' => __('Position'),
                'name' => 'position',
                'width' => 60,
                'type' => 'number',
                'validate_class' => 'validate-number',
                'index' => 'position',
                'editable' => true,
                'edit_only' => true,
            ]
        );
        return parent::_prepareColumns();
    }

    /**
     * Get Grid URL
     *
     * @return string
     */
    public function getGridUrl()
    {
        return $this->getUrl('*/diet/grids', ['_current' => true]);
    }

    /**
     * Get Selected Products
     *
     * @return array
     */
    protected function _getSelectedProducts()
    {
        $selectedProducts = $this->getSelectedProducts();
        $products = is_array($selectedProducts) ? array_keys($selectedProducts) : [];
        return $products;
    }

    /**
     * Get Selected Products
     *
     * @return array
     */
    public function getSelectedProducts()
    {
        $id = $this->getRequest()->getParam('entity_id');
    
        $collection = $this->productCollFactory->create()->addFieldToFilter('entity_id', $id);
    
        $decodedProductIds = [];
    
        foreach ($collection as $item) {
            $productIdJson = $item->getData('product_id');
            $decodedProductIds = json_decode($productIdJson, true);
        }
    
        return $decodedProductIds;
    }
}
