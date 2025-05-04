<?php
/**
 * Pratech_Promotion
 *
 * PHP version 8.x
 *
 * @category  PHP
 * @package   Pratech\Promotion
 * @author    Puneet Mittal <puneet.mittal@pratechbrands.com>
 * @copyright 2024 Copyright (c) Pratech Brands Private Limited
 * @link      https://pratechbrands.com/
 **/

namespace Pratech\Promotion\Block\Adminhtml\PromoCode\Edit\Tab\Promo;

use Magento\Backend\Block\Template\Context;
use Magento\Backend\Block\Widget\Grid\Extended;
use Magento\Backend\Helper\Data;
use Magento\Framework\Registry;
use Pratech\Promotion\Model\ResourceModel\PromoCode\CollectionFactory;

/**
 * promo codes grid
 *
 * @api
 * @since 100.0.2
 */
class Grid extends Extended
{
    /**
     * @var Registry
     */
    protected $_coreRegistry = null;

    /**
     * @var CollectionFactory
     */
    protected $_promoCodes;

    /**
     * @param Context $context
     * @param Data $backendHelper
     * @param CollectionFactory $promoCodes
     * @param Registry $coreRegistry
     * @param array $data
     */
    public function __construct(
        Context           $context,
        Data              $backendHelper,
        CollectionFactory $promoCodes,
        Registry          $coreRegistry,
        array             $data = []
    ) {
        $this->_coreRegistry = $coreRegistry;
        $this->_promoCodes = $promoCodes;
        parent::__construct($context, $backendHelper, $data);
    }

    /**
     * @inheritdoc
     */
    public function getGridUrl()
    {
        return $this->getUrl('promotion/promocode/codesGrid', ['_current' => true]);
    }

    /**
     * @inheritdoc
     */
    protected function _construct()
    {
        parent::_construct();
        $this->setId('promoCodesGrid');
        $this->setUseAjax(true);
    }

    /**
     * @inheritdoc
     */
    protected function _prepareCollection()
    {
        $campaign = $this->_coreRegistry->registry("campaign");

        $collection = $this->_promoCodes->create()
            ->addFieldToFilter('campaign_id', ['eq' => $campaign->getCampaignId()]);

        if ($this->_isExport && $this->getMassactionBlock()->isAvailable()) {
            $itemIds = $this->getMassactionBlock()->getSelected();
            if (!empty($itemIds)) {
                $collection->addFieldToFilter('code_id', ['in' => $itemIds]);
            }
        }

        $this->setCollection($collection);

        return parent::_prepareCollection();
    }

    /**
     * @inheritdoc
     */
    protected function _prepareColumns()
    {
        $this->addColumn('promo_code', ['header' => __('Promo Code'), 'index' => 'promo_code']);

        $this->addColumn(
            'times_used',
            ['header' => __('Times Used'), 'index' => 'times_used', 'width' => '50', 'type' => 'number']
        );

        $this->addColumn(
            'created_at',
            [
                'header' => __('Created'),
                'index' => 'created_at',
                'type' => 'datetime',
                'align' => 'center',
                'width' => '160'
            ]
        );

        $this->addExportType('promotion/promocode/exportCouponsCsv', __('CSV'));
        $this->addExportType('promotion/promocode/exportCouponsXml', __('Excel XML'));
        return parent::_prepareColumns();
    }

    /**
     * @inheritdoc
     */
    protected function _prepareMassaction()
    {
        $this->setMassactionIdField('code_id');
        $this->getMassactionBlock()->setFormFieldName('ids');
        $this->getMassactionBlock()->setUseAjax(true);
        $this->getMassactionBlock()->setHideFormElement(true);

        $this->getMassactionBlock()->addItem(
            'delete',
            [
                'label' => __('Delete'),
                'url' => $this->getUrl('promotion/promocode/codesMassDelete', ['_current' => true]),
                'confirm' => __('Are you sure you want to delete the selected promo code(s)?'),
                'complete' => 'refreshPromoCodesGrid'
            ]
        );

        return $this;
    }
}
