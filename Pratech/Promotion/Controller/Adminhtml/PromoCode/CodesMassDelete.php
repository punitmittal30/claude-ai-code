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

namespace Pratech\Promotion\Controller\Adminhtml\PromoCode;

class CodesMassDelete extends \Pratech\Promotion\Controller\Adminhtml\PromoCode
{
    /**
     * Codes mass delete action
     *
     * @return void
     */
    public function execute()
    {
        $this->_initRule();
        $campaign = $this->_coreRegistry->registry("campaign");

        if (!$campaign->getCampaignId()) {
            $this->_forward('noroute');
        }

        $codeIds = $this->getRequest()->getParam('ids');

        if (is_array($codeIds)) {
            $codesCollection = $this->_objectManager->create(
                \Pratech\Promotion\Model\ResourceModel\PromoCode\Collection::class
            )->addFieldToFilter(
                'code_id',
                ['in' => $codeIds]
            );

            foreach ($codesCollection as $code) {
                $code->delete();
            }
        }
    }
}
