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

 namespace Pratech\Coupon\Observer;

use Magento\Framework\Event\ObserverInterface;

class RuleConditionCombineObserver implements ObserverInterface
{

    /**
     * Rule Condition Combine Observer Constructor
     *
     * @param \Magento\Framework\ObjectManagerInterface $objectManager
     * @param \Magento\Framework\Module\Manager         $moduleManager
     */
    public function __construct(
        private \Magento\Framework\ObjectManagerInterface $objectManager,
        private \Magento\Framework\Module\Manager $moduleManager
    ) {
    }

    /**
     * @inheritDoc
     */
    public function execute(\Magento\Framework\Event\Observer $observer)
    {
        $transport = $observer->getAdditional();
        $cond = $transport->getConditions();
        if (!is_array($cond)) {
            $cond = [];
        }

        $types = [
            'Orders' => 'Order Conditions',
        ];

        foreach ($types as $typeCode => $typeLabel) {
            $condition = $this->objectManager->get('Pratech\Coupon\Model\Rule\Condition\\' . $typeCode);
            $conditionAttributes = $condition->loadAttributeOptions()->getAttributeOption();

            $attributes = [];
            foreach ($conditionAttributes as $code => $label) {
                $attributes[] = [
                    'value' => 'Pratech\Coupon\Model\Rule\Condition\\' . $typeCode . '|' . $code,
                    'label' => $label,
                ];
            }
            $cond[] = [
                'value' => $attributes,
                'label' => __($typeLabel),
            ];
        }
        $transport->setConditions($cond);

        return $this;
    }
}
