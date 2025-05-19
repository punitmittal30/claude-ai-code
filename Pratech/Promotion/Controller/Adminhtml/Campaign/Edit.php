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

use Magento\Backend\App\Action;
use Magento\Framework\App\ResponseInterface;
use Magento\Framework\Controller\Result\Redirect;
use Magento\Framework\Controller\ResultInterface;
use Magento\Framework\Registry;
use Magento\Framework\View\Result\Page;
use Magento\Framework\View\Result\PageFactory;
use Pratech\Promotion\Model\Campaign;

/**
 * Edit controller to manage edit action in campaign management.
 */
class Edit extends Action
{
    /**
     * @var PageFactory
     */
    protected $pageFactory;

    /**
     * @var Campaign
     */
    protected $campaign;

    /**
     * @var Registry
     */
    protected $registry;

    /**
     * Edit constructor
     *
     * @param Action\Context $context
     * @param PageFactory $pageFactory
     * @param Registry $registry
     * @param Campaign $campaign
     */
    public function __construct(
        Action\Context $context,
        PageFactory    $pageFactory,
        Registry       $registry,
        Campaign       $campaign
    ) {
        $this->registry = $registry;
        $this->campaign = $campaign;
        $this->pageFactory = $pageFactory;
        parent::__construct($context);
    }

    /**
     * Execute
     *
     * @return ResponseInterface|Redirect|ResultInterface|Page
     */
    public function execute()
    {
        $campaignId = $this->getRequest()->getParam('campaign_id');
        $campaign = $this->campaign;
        if ($campaignId) {
            $campaign->load($campaignId);
            if (!$campaign->getCampaignId()) {
                $this->messageManager->addErrorMessage(__('This campaign does not exists'));
                $result = $this->resultRedirectFactory->create();
                return $result->setPath('promotion/campaign/index');
            }
        }
        $data = $this->_getSession()->getFormData(true);
        if (!empty($data)) {
            $campaign->setData($data);
        }

        $this->registry->register('campaign', $campaign);
        $resultPage = $this->pageFactory->create();

        if ($campaignId) {
            $resultPage->getConfig()->getTitle()->prepend('Edit Campaign');
        } else {
            $resultPage->getConfig()->getTitle()->prepend('Add Campaign');
        }
        return $resultPage;
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
