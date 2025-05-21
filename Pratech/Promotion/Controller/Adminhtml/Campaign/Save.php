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

namespace Pratech\Promotion\Controller\Adminhtml\Campaign;

use Exception;
use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Backend\Model\View\Result\Redirect;
use Magento\Backend\Model\View\Result\RedirectFactory;
use Magento\Framework\App\ResponseInterface;
use Magento\Framework\Controller\ResultInterface;
use Magento\Framework\Exception\LocalizedException;
use Pratech\Promotion\Model\CampaignFactory;

class Save extends Action
{
    /**
     * @var Redirect
     */
    protected $redirect;

    /**
     * Save constructor
     *
     * @param Context $context
     * @param RedirectFactory $redirectFactory
     * @param CampaignFactory $campaignFactory
     */
    public function __construct(
        Action\Context          $context,
        RedirectFactory         $redirectFactory,
        private CampaignFactory $campaignFactory
    ) {
        $this->redirect = $redirectFactory->create();
        parent::__construct($context);
    }

    /**
     * Execute
     *
     * @return Redirect|ResponseInterface|ResultInterface
     * @throws LocalizedException
     */
    public function execute()
    {
        $data = $this->getRequest()->getPostValue();

        if ($data) {
            try {
                $id = $data['campaign_id'];
                $campaign = $this->campaignFactory->create()->load($id);

                if (empty($id)) {
                    unset($data['campaign_id']);
                }

                $campaign->setData($data);
                $campaign->save();

                $this->_eventManager->dispatch(
                    'campaign_controller_save_after',
                    ['campaign' => $campaign]
                );

                $this->messageManager->addSuccessMessage(__('Campaign data successfully saved'));

                $this->_getSession()->setFormData(false);

                if ($this->getRequest()->getParam('back')) {
                    return $this->redirect->setPath(
                        '*/*/edit',
                        ['campaign_id' => $campaign->getCampaignId(), '_current' => true]
                    );
                }
            } catch (Exception $exception) {
                $this->messageManager->addErrorMessage(__($exception->getMessage()));
            }
        }
        return $this->redirect->setPath('*/*/index');
    }

    /**
     * Is Allowed
     *
     * @return bool
     */
    protected function _isAllowed(): bool
    {
        return $this->_authorization->isAllowed('Pratech_Promotion::manage');
    }
}
