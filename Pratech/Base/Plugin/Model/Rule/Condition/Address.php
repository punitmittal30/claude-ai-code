<?php

namespace Pratech\Base\Plugin\Model\Rule\Condition;

use Magento\Framework\Model\AbstractModel;
use Magento\Rule\Model\Condition\AbstractCondition;

class Address extends \Magento\SalesRule\Model\Rule\Condition\Address
{
    /**
     * Validate
     *
     * @param AbstractModel $model
     * @return bool
     */
    public function validate(AbstractModel $model): bool
    {
        $address = $model;
        if (!$address instanceof \Magento\Quote\Model\Quote\Address) {
            if ($model->getQuote()) {
                if ($model->getQuote()->isVirtual()) {
                    $address = $model->getQuote()->getBillingAddress();
                } else {
                    $address = $model->getQuote()->getShippingAddress();
                }
            }
        }

        if ('payment_method' == $this->getAttribute() && !$address->hasPaymentMethod()) {
            $address->setPaymentMethod($model->getQuote()->getPayment()->getMethod());
        }

        return AbstractCondition::validate($address);
    }
}
