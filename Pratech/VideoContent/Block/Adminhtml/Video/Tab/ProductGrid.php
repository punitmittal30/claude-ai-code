<?php
/**
 * Pratech_VideoContent
 *
 * PHP version 8.x
 *
 * @category  PHP
 * @package   Pratech\VideoContent
 * @author    Akash Panwar <akash.panwar@pratechbrands.com>
 * @copyright 2025 Copyright (c) Pratech Brands Private Limited
 * @link      https://pratechbrands.com/
 **/

namespace Pratech\VideoContent\Block\Adminhtml\Video\Tab;

use Exception;
use Magento\Backend\Block\Template\Context;
use Magento\Backend\Block\Widget\Grid\Column;
use Magento\Backend\Block\Widget\Grid\Extended;
use Magento\Backend\Helper\Data;
use Magento\Catalog\Model\Product\Visibility;
use Magento\Catalog\Model\ProductFactory;
use Magento\Catalog\Model\ResourceModel\Product\CollectionFactory;
use Magento\Framework\App\ObjectManager;
use Magento\Framework\Exception\FileSystemException;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Module\Manager;
use Magento\Framework\Registry;
use Magento\Store\Api\Data\StoreInterface;
use Magento\Store\Model\Store;
use Magento\Store\Model\StoreManagerInterface;
use Pratech\VideoContent\Model\ResourceModel\Video;

/**
 * Product Grid Block Class
 */
class ProductGrid extends Extended
{
    /**
     * @var Registry|null
     */
    protected $coreRegistry = null;

    /**
     * @param Context $context
     * @param Data $backendHelper
     * @param ProductFactory $productFactory
     * @param CollectionFactory $productCollFactory
     * @param Manager $moduleManager
     * @param StoreManagerInterface $storeManager
     * @param Video $video
     * @param Registry $coreRegistry
     * @param Visibility|null $visibility
     * @param array $data
     */
    public function __construct(
        Context                         $context,
        Data                            $backendHelper,
        protected ProductFactory        $productFactory,
        protected CollectionFactory     $productCollFactory,
        protected Manager               $moduleManager,
        protected StoreManagerInterface $storeManager,
        protected Video                 $video,
        Registry                        $coreRegistry,
        Visibility                      $visibility = null,
        array                           $data = []
    ) {
        $this->coreRegistry = $coreRegistry;
        $this->visibility = $visibility ?: ObjectManager::getInstance()->get(Visibility::class);
        parent::__construct($context, $backendHelper, $data);
    }

    /**
     * Get Grid Url
     *
     * @return string
     */
    public function getGridUrl()
    {
        return $this->getUrl('*/videos/grids', ['_current' => true]);
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
        $this->setId('video_grid_products');
        $this->setDefaultSort('entity_id');
        $this->setDefaultDir('ASC');
        $this->setUseAjax(true);
//        if ($this->getRequest()->getParam('entity_id')) {
//            $this->setDefaultFilter(['in_products' => 1]);
//        } else {
//            $this->setDefaultFilter(['in_products' => 0]);
//        }
        $this->setSaveParametersInSession(true);
    }

    /**
     * Prepare Collection.
     *
     * @return ProductGrid
     * @throws NoSuchEntityException
     */
    protected function _prepareCollection()
    {
        if (!empty($this->_getSelectedProducts())) {
            $this->setDefaultFilter(['in_products' => 1]);
        }
        $store = $this->_getStore();
        $collection = $this->productFactory->create()->getCollection()->addAttributeToSelect(
            'sku'
        )->addAttributeToSelect(
            'name'
        )->addAttributeToSelect(
            'attribute_set_id'
        )->addAttributeToSelect(
            'type_id'
        )->joinField(
            'position',
            'video_product_mapping',
            'position',
            'product_id=entity_id',
            'video_id=' . (int)$this->getRequest()->getParam('id', 0),
            'left'
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
        } else {
            $collection->joinAttribute('status', 'catalog_product/status', 'entity_id', null, 'inner');
            $collection->joinAttribute('visibility', 'catalog_product/visibility', 'entity_id', null, 'inner');
        }
        $this->setCollection($collection);
        return parent::_prepareCollection();
    }

    /**
     * Get Store.
     *
     * @return StoreInterface
     * @throws NoSuchEntityException
     */
    protected function _getStore()
    {
        $storeId = (int)$this->getRequest()->getParam('store', 0);
        return $this->storeManager->getStore($storeId);
    }

    /**
     * Add Column Filters
     *
     * @param Column $column
     * @return $this|ProductGrid
     * @throws LocalizedException
     */
    protected function _addColumnFilterToCollection($column): ProductGrid|static
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
     * Get Selected Products Keys
     *
     * @return array
     */
    protected function _getSelectedProducts(): array
    {
        return array_keys($this->getSelectedProducts());
    }

    /**
     * Get Selected Products
     *
     * @return array
     */
    public function getSelectedProducts(): array
    {
        $videoId = $this->getRequest()->getParam('id');
        if ($videoId) {
            $selectedProducts = [];
            $productAndPosition = $this->video->getProductsAndPosition($videoId);
            foreach ($productAndPosition as $product => $position) {
                $selectedProducts[$product] = $position;
            }
            return $selectedProducts;
        }
        return [];
    }

    /**
     * Prepare Columns of Product Grid,
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
}
