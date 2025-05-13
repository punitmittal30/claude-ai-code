<?php
/**
 * Pratech_Coupon
 *
 * PHP version 8.x
 *
 * @category  PHP
 * @package   Pratech\Coupon
 * @author    Puneet Mittal <puneet.mittal@pratechbrands.com>
 * @copyright 2025 Copyright (c) Pratech Brands Private Limited
 * @link      https://pratechbrands.com/
 **/

namespace Pratech\Coupon\Plugin\Controller\Promo\Quote;

class Save
{
    /**
     * Before Execute.
     *
     * @param \Magento\SalesRule\Controller\Adminhtml\Promo\Quote\Save $subject
     * @return void
     */
    public function beforeExecute(
        \Magento\SalesRule\Controller\Adminhtml\Promo\Quote\Save $subject
    ): void {
        $request = $subject->getRequest();
        $postData = $request->getPostValue();
        $stackableRules = $postData['stackable_rule_ids'] ?? null;

        if ($stackableRules) {
            $rulesArray = json_decode($stackableRules, true);
            if (is_array($rulesArray)) {
                $ruleIds = implode(',', array_keys($rulesArray));
                $postData['stackable_rule_ids'] = $ruleIds;
                $request->setPostValue($postData);
            }
        }
    }
}
