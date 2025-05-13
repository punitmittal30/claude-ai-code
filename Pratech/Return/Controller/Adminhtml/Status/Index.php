<?php
/**
 * Pratech_Return
 *
 * PHP version 8.x
 *
 * @category  PHP
 * @package   Pratech\Return
 * @author    Vivek Kumar <vivek.kumar@pratechbrands.com>
 * @copyright 2024 Copyright (c) Pratech Brands Private Limited
 * @link      https://pratechbrands.com/
 **/

namespace Pratech\Return\Controller\Adminhtml\Status;

use Magento\Backend\App\Action;
use Magento\Backend\Model\View\Result\Page;
use Magento\Framework\Controller\ResultFactory;
use Pratech\Return\Api\Data\StatusInterface;
use Pratech\Return\Model\OptionSource\State;
use Pratech\Return\Model\Status\ResourceModel\Collection;
use Pratech\Return\Model\Status\ResourceModel\CollectionFactory;

class Index extends Action
{
    /**
     * @param Action\Context $context
     * @param CollectionFactory $collectionFactory
     * @param State $state
     */
    public function __construct(
        Action\Context            $context,
        private CollectionFactory $collectionFactory,
        private State             $state,
    ) {
        parent::__construct($context);
    }

    /**
     * @inheritdoc
     */
    public function execute()
    {
        $this->checkStatusSettings();

        /**
         * @var Page $resultPage
         */
        $resultPage = $this->resultFactory->create(ResultFactory::TYPE_PAGE);
        $resultPage->setActiveMenu('Pratech_Return::status');
        $resultPage->getConfig()->getTitle()->prepend(__('Return Statuses'));

        return $resultPage;
    }

    public function checkStatusSettings()
    {
        $hasInitialStatus = $this->getStatusCollection()
            ->addFieldToFilter(StatusInterface::IS_INITIAL, 1)
            ->getSize();

        if (!$hasInitialStatus) {
            $this->messageManager->addWarningMessage(__('You don\'t have `Initial Status`. Please Create/Enable It.'));
        }

        foreach ($this->state->toArray() as $stateIndex => $stateName) {
            if (!$this->getStatusCollection()->addFieldToFilter(StatusInterface::STATE, $stateIndex)->getSize()) {
                $this->messageManager->addWarningMessage(__('State `%1` has no active statuses.', $stateName));
            }
        }

        $hasCancelStatus = $this->getStatusCollection()
            ->addFieldToFilter(StatusInterface::STATE, State::CANCELED)
            ->getSize();
        if (!$hasCancelStatus) {
            $this->messageManager->addWarningMessage(
                __(
                    'Customer couldn\'t Cancel Return because there is no'
                    . ' active status in state `Cancel` with automatically set status on event `Customer Canceled Return`.'
                    . ' Please Create/Enable it.'
                )
            );
        }
    }

    /**
     * @return Collection
     */
    public function getStatusCollection()
    {
        $statusCollection = $this->collectionFactory->create();

        return $statusCollection->addNotDeletedFilter()
            ->addFieldToFilter(StatusInterface::IS_ENABLED, 1);
    }

    /**
     * Is Allowed
     *
     * @return bool
     */
    protected function _isAllowed(): bool
    {
        return $this->_authorization->isAllowed('Pratech_Return::status');
    }
}
