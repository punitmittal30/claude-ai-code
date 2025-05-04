<?php
/**
 * Pratech_Coupon
 *
 * PHP version 8.x
 *
 * @category  PHP
 * @package   Pratech\Coupon
 * @author    Vivek Kumar <vivek.kumar@pratechbrands.com>
 * @copyright 2023 Copyright (c) Pratech Brands Private Limited
 * @link      https://pratechbrands.com/
 **/

namespace Pratech\Coupon\Model\Rule\Condition;

use Magento\Rule\Model\Condition as Condition;
use Pratech\Coupon\Model\ResourceModel\Order;

/**
 * Sales rule condition data model
 */
class Orders extends \Magento\Rule\Model\Condition\AbstractCondition
{
    /**
     * @param Condition\Context $context
     * @param Order             $orderResource
     * @param array             $data
     */
    public function __construct(
        Condition\Context $context,
        private Order $orderResource,
        array $data = []
    ) {

        parent::__construct($context, $data);
    }

    /**
     * @inheritDoc
     */
    public function loadAttributeOptions()
    {
        $attributes = [
            'order_num_app' => __('Number of Orders from App'),
            'order_num_web' => __('Number of Orders from Web'),
        ];

        $this->setAttributeOption($attributes);

        return $this;
    }

    /**
     * @inheritDoc
     */
    public function getAttributeElement()
    {
        $element = parent::getAttributeElement();
        $element->setShowAsText(true);

        return $element;
    }

    /**
     * @inheritDoc
     */
    public function getInputType()
    {
        return 'numeric';
    }

    /**
     * @inheritDoc
     */
    public function getValueElementType()
    {
        return 'text';
    }

    /**
     * @inheritDoc
     */
    public function getValueSelectOptions()
    {
        $options = [];

        $key = 'value_select_options';
        if (!$this->hasData($key)) {
            $this->setData($key, $options);
        }

        return $this->getData($key);
    }

    /**
     * @inheritDoc
     */
    public function validate(\Magento\Framework\Model\AbstractModel $model)
    {
        $quote = $model;
        $num = 0;

        if ($quote instanceof \Amasty\Acart\Model\Customer\Address) {
            $quote = $model->getData('quote');
        }

        if (!$quote instanceof \Magento\Quote\Model\Quote) {
            $quote = $model->getQuote();
        }

        if ($quote->getCustomerId()) {
            $num = $this->orderResource->getValidationData($quote->getCustomerId(), $this->getAttribute());
        }
        return $this->validateAttribute($num);
    }
}
