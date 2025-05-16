<?php
/**
 * Pratech_Banners
 *
 * PHP version 8.x
 *
 * @category  PHP
 * @package   Pratech\Banners
 * @author    Puneet Mittal <puneet.mittal@pratechbrands.com>
 * @copyright 2023 Copyright (c) Pratech Brands Private Limited
 * @link      https://pratechbrands.com/
 **/

namespace Pratech\Banners\Block\Adminhtml\Slider\Tab;

use Magento\Backend\Block\Template\Context;
use Magento\Backend\Block\Widget\Grid\Column;
use Magento\Backend\Block\Widget\Grid\Extended;
use Magento\Backend\Helper\Data;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Module\Manager;
use Magento\Framework\Registry;
use Magento\Store\Api\Data\StoreInterface;
use Magento\Store\Model\StoreManagerInterface;
use Pratech\Banners\Block\Adminhtml\Slider\Tab\Render\Status;
use Pratech\Banners\Model\ResourceModel\Slider;
use Pratech\Banners\Model\BannerFactory;

class BannerGrid extends Extended
{
    /**
     * @var Registry|null
     */
    protected $coreRegistry = null;

    /**
     * @var BannerFactory
     */
    protected $bannerFactory;

    /**
     * @var Manager
     */
    protected $moduleManager;

    /**
     * @var Slider
     */
    protected $slider;

    /**
     * Slide grid constructor
     *
     * @param Context $context
     * @param Data $backendHelper
     * @param BannerFactory $bannerFactory
     * @param Registry $coreRegistry
     * @param Manager $moduleManager
     * @param StoreManagerInterface $storeManager
     * @param Slider $slider
     * @param array $data
     */
    public function __construct(
        Context               $context,
        Data                  $backendHelper,
        BannerFactory         $bannerFactory,
        Registry              $coreRegistry,
        Manager               $moduleManager,
        StoreManagerInterface $storeManager,
        Slider                $slider,
        array                 $data = []
    ) {
        $this->bannerFactory = $bannerFactory;
        $this->coreRegistry = $coreRegistry;
        $this->moduleManager = $moduleManager;
        $this->_storeManager = $storeManager;
        $this->slider = $slider;
        parent::__construct($context, $backendHelper, $data);
    }

    /**
     * Get Grid URL
     *
     * @return string
     */
    public function getGridUrl()
    {
        return $this->getUrl('*/slider/grids', ['_current' => true]);
    }

    /**
     * Slide Constructor
     */
    protected function _construct()
    {
        parent::_construct();
        $this->setId('pratech_grid_banners');
        $this->setDefaultSort('banner_id');
        $this->setDefaultDir('ASC');
        $this->setUseAjax(true);
//        if ($this->getRequest()->getParam('banner_id')) {
//            $this->setDefaultFilter(['in_banners' => 1]);
//        } else {
//            $this->setDefaultFilter(['in_banners' => 0]);
//        }
        $this->setSaveParametersInSession(true);
    }

    /**
     * Prepare Collection
     *
     * @return Extended
     * @throws NoSuchEntityException
     */
    protected function _prepareCollection()
    {
        $store = $this->_getStore();
        $collection = $this->bannerFactory->create()
            ->getCollection()
            ->addFieldToSelect(['banner_id', 'name', 'status', 'desktop_image'])
            ->addFieldToFilter('banner_id', ['in' => $this->getBannersToShow()]);

        $this->setCollection($collection);
        return parent::_prepareCollection();
    }

    /**
     * Get Store
     *
     * @return StoreInterface
     * @throws NoSuchEntityException
     */
    protected function _getStore()
    {
        $storeId = (int)$this->getRequest()->getParam('store', 0);
        return $this->_storeManager->getStore($storeId);
    }

    /**
     * Get Banners to show
     *
     * @return array
     */
    public function getBannersToShow()
    {
        $sliderId = $this->getRequest()->getParam('slider_id');
        $assignedBannerIdsArray = $this->slider->getBanners($sliderId);
        $unassignedBannerIds = $this->slider->getBannersNotAssigned();
        return array_merge($assignedBannerIdsArray, $unassignedBannerIds);
    }

    /**
     * Add Column Filter To Collection
     *
     * @param Column $column
     * @return $this|Extended
     * @throws LocalizedException
     */
    protected function _addColumnFilterToCollection($column)
    {
        $columnId = $column->getId();
        if ($columnId == 'in_banners') {
            $bannerIds = $this->_getSelectedBanners();
            if (empty($bannerIds)) {
                $bannerIds = 0;
            }

            $columnValue = $column->getFilter()->getValue();
            if ($columnValue) {
                $this->getCollection()
                    ->addFieldToFilter(
                        'banner_id',
                        ['in' => $bannerIds]
                    );
            } elseif (!empty($bannerIds)) {
                $this->getCollection()
                    ->addFieldToFilter(
                        'banner_id',
                        ['in' => $bannerIds]
                    );
            }
        } else {
            parent::_addColumnFilterToCollection($column);
        }

        return $this;
    }

    /**
     * Get Selected Banners
     *
     * @return array
     */
    protected function _getSelectedBanners()
    {
        return $this->getSelectedBanners();
    }

    /**
     * GetSelectedBanners
     *
     * @return array
     */
    public function getSelectedBanners()
    {
        $sliderId = $this->getRequest()->getParam('slider_id');

        if ($sliderId) {
            return $this->slider->getBanners($sliderId);
        }

        return [];
    }

    /**
     * PrepareColumns
     *
     * @return Extended
     * @throws NoSuchEntityException
     */
    protected function _prepareColumns()
    {
        $this->addColumn('in_banners', [
            'type' => 'checkbox',
            'html_name' => 'banner_id',
            'required' => true,
            'values' => $this->_getSelectedBanners(),
            'align' => 'center',
            'index' => 'banner_id',
        ]);

        $this->addColumn('banner_id', [
            'header' => __('ID'),
            'width' => '50px',
            'index' => 'banner_id',
            'type' => 'number',
        ]);

        $this->addColumn('name', [
            'header' => __('Name'),
            'index' => 'name',
            'header_css_class' => 'col-type',
            'column_css_class' => 'col-type',
        ]);

        $this->addColumn('desktop_image', [
            'header' => __('Thumbnail Image'),
            'index' => 'desktop_image',
            'header_css_class' => 'col-sku',
            'column_css_class' => 'col-sku',
        ]);

        $this->addColumn('status', [
            'header' => __('Status'),
            'index' => 'status',
            'header_css_class' => 'col-status',
            'column_css_class' => 'col-status',
            'renderer' => Status::class
        ]);

        $store = $this->_getStore();
        return parent::_prepareColumns();
    }
}
