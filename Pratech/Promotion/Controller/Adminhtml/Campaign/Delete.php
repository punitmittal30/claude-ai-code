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
use Pratech\Promotion\Model\Campaign;

class Delete extends Action
{
    /**
     * @var Campaign
     */
    protected $campaign;

    /**
     * @var Redirect
     */
    protected $redirect;

    /**
     * @param Context $context
     * @param Campaign $campaign
     * @param RedirectFactory $redirectFactory
     */
    public function __construct(
        Action\Context  $context,
        Campaign        $campaign,
        RedirectFactory $redirectFactory
    ) {
        $this->campaign = $campaign;
        $this->redirect = $redirectFactory->create();
        parent::__construct($context);
    }

    /**
     * Execute
     *
     * @return Redirect|ResponseInterface|ResultInterface
     */
    public function execute()
    {
        $id = $this->getRequest()->getParam('campaign_id');
        if ($id) {
            $this->campaign->load($id);
            try {
                $this->campaign->delete();
                $this->_eventManager->dispatch('campaign_controller_delete_after', ['campaign' => $this->campaign]);
                $this->messageManager->addSuccessMessage(__('Campaign has been successfully removed'));
            } catch (Exception $exception) {
                $this->messageManager->addErrorMessage(__($exception->getMessage()));
                return $this->redirect->setPath('*/*/edit', ['campaign_id' => $id]);
            }
        }
        return $this->redirect->setPath('*/*/index');
    }

    /**
     * Is Allowed
     *
     * @return bool
     */
    protected function _isAllowed()
    {
        return $this->_authorization->isAllowed('Pratech_Promotion::manage');
    }
}
