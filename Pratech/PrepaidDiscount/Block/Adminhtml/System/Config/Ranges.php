<?php
/**
 * Pratech_PrepaidDiscount
 *
 * PHP version 8.x
 *
 * @category  PHP
 * @package   Pratech\PrepaidDiscount
 * @author    Puneet Mittal <puneet.mittal@pratechbrands.com>
 * @copyright 2023 Copyright (c) Pratech Brands Private Limited
 * @link      https://pratechbrands.com/
 **/

namespace Pratech\PrepaidDiscount\Block\Adminhtml\System\Config;

use Magento\Config\Block\System\Config\Form\Field\FieldArray\AbstractFieldArray;
use Magento\Framework\DataObject;
use Magento\Framework\Exception\LocalizedException;
use Pratech\PrepaidDiscount\Block\Adminhtml\Form\Field\DiscountTypeColumn;

/**
 * Types Class to provide frontend model for Prepaid Discount.
 */
class Ranges extends AbstractFieldArray
{
    /**
     * @var DiscountTypeColumn
     */
    private $discountTypeRenderer;

    /**
     * Prepare rendering the new field by adding all the needed columns
     */
    protected function _prepareToRender(): void
    {
        $this->addColumn('from_price', ['label' => __('From'), 'class' => 'required-entry']);
        $this->addColumn('to_price', ['label' => __('To'), 'class' => 'required-entry']);
        $this->addColumn('discount', ['label' => __('Discount'), 'class' => 'required-entry']);
        $this->addColumn('discount_type', [
            'label' => __('Discount Type'),
            'renderer' => $this->getDiscountType()
        ]);
        $this->_addAfter = false;
        $this->_addButtonLabel = __('Add');
    }

    /**
     * Get Discount Type Renderer.
     *
     * @return DiscountTypeColumn
     * @throws LocalizedException
     */
    private function getDiscountType(): DiscountTypeColumn
    {
        if (!$this->discountTypeRenderer) {
            $this->discountTypeRenderer = $this->getLayout()->createBlock(
                DiscountTypeColumn::class,
                '',
                ['data' => ['is_render_to_js_template' => true]]
            );
        }
        return $this->discountTypeRenderer;
    }

    /**
     * Prepare existing row data object
     *
     * @param DataObject $row
     * @throws LocalizedException
     */
    protected function _prepareArrayRow(DataObject $row): void
    {
        $options = [];

        $discountType = $row->getDiscountType();
        if ($discountType !== null) {
            $options['option_' . $this->getDiscountType()->calcOptionHash($discountType)] = 'selected="selected"';
        }

        $row->setData('option_extra_attrs', $options);
    }
}
