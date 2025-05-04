<?php
/**
 * Pratech_Coupon
 *
 * @category  XML
 * @package   Pratech\Coupon
 * @author    Vivek Kumar <vivek.kumar@pratechbrands.com>
 * @copyright 2022 Copyright (c) Pratech Brands Private Limited
 * @link      https://pratechbrands.com/
 */

namespace Pratech\Coupon\Block\Adminhtml\Tab;

use Exception;
use Magento\Backend\Block\Template\Context;
use Magento\Backend\Block\Widget\Grid\Column;
use Magento\Backend\Block\Widget\Grid\Extended;
use Magento\Backend\Helper\Data;
use Magento\Framework\Exception\FileSystemException;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Registry;
use Magento\SalesRule\Model\RuleFactory;

class Rulegrid extends Extended
{
    /**
     * @param Context $context
     * @param Data $backendHelper
     * @param RuleFactory $ruleFactory
     * @param Registry $registry
     * @param array $data
     */
    public function __construct(
        Context               $context,
        Data                  $backendHelper,
        protected RuleFactory $ruleFactory,
        protected Registry    $registry,
        array                 $data = []
    ) {
        parent::__construct($context, $backendHelper, $data);
    }

    /**
     * Get Grid Url.
     *
     * @return string
     */
    public function getGridUrl(): string
    {
        return $this->getUrl('coupon/index/rulegrid', ['_current' => true]);
    }

    /**
     * Construct.
     *
     * @return void
     * @throws FileSystemException
     */
    protected function _construct(): void
    {
        parent::_construct();
        $this->setId('salesRuleGrid');
        $this->setDefaultSort('rule_id');
        $this->setDefaultDir('ASC');
        $this->setUseAjax(true);
//        if ($this->getRequest()->getParam('id')) {
//            $this->setDefaultFilter(['in_rules' => 1]);
//        }
        $this->setSaveParametersInSession(true);
    }

    /**
     * Add Column Filter To Collection
     *
     * @param Column $column
     * @return $this
     */
    protected function _addColumnFilterToCollection($column)
    {
        try {
            if ($column->getId() == 'in_rules') {
                $ruleIds = $this->_getSelectedRules();

                if ($column->getFilter()->getValue()) {
                    // 'Yes' filter selected
                    $this->getCollection()->addFieldToFilter('rule_id', ['in' => $ruleIds]);
                } else {
                    // 'No' filter selected
                    if (!empty($ruleIds)) {
                        $this->getCollection()->addFieldToFilter('rule_id', ['nin' => $ruleIds]);
                    }
                }
            } else {
                parent::_addColumnFilterToCollection($column);
            }
        } catch (LocalizedException $e) {
            parent::_addColumnFilterToCollection($column);
        }
        return $this;
    }

    /**
     * Get Selected Rules.
     *
     * @return array|string[]
     */
    protected function _getSelectedRules(): array
    {
        $ruleId = $this->getRequest()->getParam('id');
        if ($ruleId) {
            $rule = $this->ruleFactory->create()->load($ruleId);
            return explode(',', $rule->getStackableRuleIds() ?? '');
        }
        return [];
    }

    /**
     * Prepare Collection.
     *
     * @return Rulegrid
     */
    protected function _prepareCollection(): Rulegrid
    {
        $collection = $this->ruleFactory->create()->getCollection();

        // Exclude current rule from the grid
        $currentRuleId = $this->getRequest()->getParam('id');
        if ($currentRuleId) {
            $collection->addFieldToFilter('rule_id', ['neq' => $currentRuleId]);
        }

        $this->setCollection($collection);
        return parent::_prepareCollection();
    }

    /**
     * Prepare Columns.
     *
     * @return Rulegrid
     * @throws Exception
     */
    protected function _prepareColumns(): Rulegrid
    {
        $this->addColumn(
            'in_rules',
            [
                'type' => 'checkbox',
                'name' => 'in_rules',
                'values' => $this->_getSelectedRules(),
                'align' => 'center',
                'index' => 'rule_id',
                'header_css_class' => 'col-select',
                'column_css_class' => 'col-select'
            ]
        );

        $this->addColumn(
            'rule_id',
            [
                'header' => __('ID'),
                'sortable' => true,
                'index' => 'rule_id',
                'header_css_class' => 'col-id',
                'column_css_class' => 'col-id'
            ]
        );

        $this->addColumn(
            'name',
            [
                'header' => __('Rule Name'),
                'index' => 'name',
                'header_css_class' => 'col-name',
                'column_css_class' => 'col-name'
            ]
        );

        return parent::_prepareColumns();
    }
}
