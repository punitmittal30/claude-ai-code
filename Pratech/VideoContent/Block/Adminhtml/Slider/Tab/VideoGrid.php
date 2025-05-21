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

namespace Pratech\VideoContent\Block\Adminhtml\Slider\Tab;

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
use Pratech\VideoContent\Block\Adminhtml\Slider\Tab\Render\Status;
use Pratech\VideoContent\Model\ResourceModel\Slider;
use Pratech\VideoContent\Model\VideoFactory;

class VideoGrid extends Extended
{
    /**
     * Slide grid constructor
     *
     * @param Context $context
     * @param Data $backendHelper
     * @param VideoFactory $videoFactory
     * @param Registry $coreRegistry
     * @param Manager $moduleManager
     * @param StoreManagerInterface $storeManager
     * @param Slider $slider
     * @param array $data
     */
    public function __construct(
        Context                 $context,
        Data                    $backendHelper,
        protected VideoFactory  $videoFactory,
        protected Registry      $coreRegistry,
        protected Manager       $moduleManager,
        StoreManagerInterface   $storeManager,
        protected Slider        $slider,
        array                   $data = []
    ) {
        $this->_storeManager = $storeManager;
        parent::__construct($context, $backendHelper, $data);
    }

    /**
     * Get Grid URL
     *
     * @return string
     */
    public function getGridUrl(): string
    {
        return $this->getUrl('*/slider/grids', ['_current' => true]);
    }

    /**
     * Slide Constructor
     */
    protected function _construct()
    {
        parent::_construct();
        $this->setId('pratech_grid_videos');
        $this->setDefaultSort('video_id');
        $this->setDefaultDir('ASC');
        $this->setUseAjax(true);
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
        $collection = $this->videoFactory->create()
            ->getCollection()
            ->addFieldToSelect(['video_id', 'name', 'is_active', 'url'])
            ->addFieldToFilter('used_for_carousel', 1)
            ->addFieldToFilter('video_id', ['in' => $this->getVideosToShow()]);

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
     * Get Videos to show
     *
     * @return array
     */
    public function getVideosToShow()
    {
        $sliderId = $this->getRequest()->getParam('slider_id');
        $assignedVideoIdsArray = $this->slider->getVideos($sliderId);
        $unassignedVideoIds = $this->slider->getVideosNotAssigned();
        return array_merge($assignedVideoIdsArray, $unassignedVideoIds);
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
        if ($columnId == 'in_videos') {
            $videoIds = $this->_getSelectedVideos();
            if (empty($videoIds)) {
                $videoIds = 0;
            }

            $columnValue = $column->getFilter()->getValue();
            if ($columnValue) {
                $this->getCollection()
                    ->addFieldToFilter(
                        'video_id',
                        ['in' => $videoIds]
                    );
            } elseif (!empty($videoIds)) {
                $this->getCollection()
                    ->addFieldToFilter(
                        'video_id',
                        ['in' => $videoIds]
                    );
            }
        } else {
            parent::_addColumnFilterToCollection($column);
        }

        return $this;
    }

    /**
     * Get Selected Videos
     *
     * @return array
     */
    protected function _getSelectedVideos()
    {
        return $this->getSelectedVideos();
    }

    /**
     * GetSelectedVideos
     *
     * @return array
     */
    public function getSelectedVideos()
    {
        $sliderId = $this->getRequest()->getParam('slider_id');

        if ($sliderId) {
            return $this->slider->getVideos($sliderId);
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
        $this->addColumn('in_videos', [
            'type' => 'checkbox',
            'html_name' => 'video_id',
            'required' => true,
            'values' => $this->_getSelectedVideos(),
            'align' => 'center',
            'index' => 'video_id',
        ]);

        $this->addColumn('video_id', [
            'header' => __('ID'),
            'width' => '50px',
            'index' => 'video_id',
            'type' => 'number',
        ]);

        $this->addColumn('name', [
            'header' => __('Name'),
            'index' => 'name',
            'header_css_class' => 'col-type',
            'column_css_class' => 'col-type',
        ]);

        $this->addColumn('url', [
            'header' => __('Video Url'),
            'index' => 'url',
            'header_css_class' => 'col-sku',
            'column_css_class' => 'col-sku',
        ]);

        $this->addColumn('is_active', [
            'header' => __('Status'),
            'index' => 'is_active',
            'header_css_class' => 'col-status',
            'column_css_class' => 'col-status',
            'renderer' => Status::class
        ]);

        $store = $this->_getStore();
        return parent::_prepareColumns();
    }
}
