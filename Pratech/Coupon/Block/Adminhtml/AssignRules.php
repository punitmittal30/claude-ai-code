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

namespace Pratech\Coupon\Block\Adminhtml;

use Magento\Backend\Block\Template;
use Magento\Backend\Block\Template\Context;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Json\EncoderInterface;
use Magento\Framework\Registry;
use Magento\Framework\View\Element\BlockInterface;
use Magento\SalesRule\Model\ResourceModel\Rule\CollectionFactory;
use Pratech\Coupon\Block\Adminhtml\Tab\Rulegrid;

class AssignRules extends Template
{
    /**
     * @var string
     */
    protected $_template = 'Pratech_Coupon::rules/assign_rules.phtml';
    /**
     * @var $blockGrid
     */
    protected $blockGrid;

    /**
     * @param Context $context
     * @param Registry $registry
     * @param EncoderInterface $jsonEncoder
     * @param CollectionFactory $ruleFactory
     * @param array $data
     */
    public function __construct(
        Context                     $context,
        protected Registry          $registry,
        protected EncoderInterface  $jsonEncoder,
        protected CollectionFactory $ruleFactory,
        array                       $data = []
    ) {
        parent::__construct($context, $data);
    }

    /**
     * Get Grid Html.
     *
     * @return string
     * @throws LocalizedException
     */
    public function getGridHtml()
    {
        return $this->getBlockGrid()->toHtml();
    }

    /**
     * Get Block Grid.
     *
     * @return BlockInterface
     * @throws LocalizedException
     */
    public function getBlockGrid()
    {
        if (null === $this->blockGrid) {
            $this->blockGrid = $this->getLayout()->createBlock(
                Rulegrid::class,
                'sales.rule.grid'
            );
        }
        return $this->blockGrid;
    }

    /**
     * Get Rules Json.
     *
     * @return string
     */
    public function getRulesJson(): string
    {
        $rule_id = $this->getRequest()->getParam('id');
        if ($rule_id) {
            $rule = $this->ruleFactory->create()
                ->addFieldToSelect(['rule_id', 'stackable_rule_ids'])
                ->addFieldToFilter('rule_id', ['eq' => $rule_id])
                ->getFirstItem();

            if ($rule && $rule->getStackableRuleIds()) {
                $ruleIds = explode(',', $rule->getStackableRuleIds());
                $result = array_fill_keys($ruleIds, '');
                return $this->jsonEncoder->encode($result);
            }
        }
        return '{}';
    }

    /**
     * Get Item
     *
     * @return mixed|null
     */
    public function getItem()
    {
        return $this->registry->registry('stackable_rules');
    }
}
