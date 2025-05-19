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

use Exception;
use Magento\Backend\App\Action;
use Magento\Framework\Exception\LocalizedException;
use Magento\Ui\Component\MassAction\Filter;
use Pratech\Return\Api\StatusRepositoryInterface;
use Pratech\Return\Model\Status\ResourceModel\Collection;
use Pratech\Return\Model\Status\ResourceModel\CollectionFactory;
use Psr\Log\LoggerInterface;

class MassDelete extends Action
{
    /**
     * @param Action\Context $context
     * @param StatusRepositoryInterface $repository
     * @param CollectionFactory $collectionFactory
     * @param Filter $filter
     * @param LoggerInterface $logger
     */
    public function __construct(
        Action\Context                    $context,
        private StatusRepositoryInterface $repository,
        private CollectionFactory         $collectionFactory,
        private Filter                    $filter,
        private LoggerInterface           $logger
    ) {
        parent::__construct($context);
    }

    /**
     * Mass action execution
     *
     * @throws LocalizedException
     */
    public function execute()
    {
        $this->filter->applySelectionOnTargetProvider();

        /**
         * @var Collection $collection
         */
        $collection = $this->filter->getCollection($this->collectionFactory->create());
        $deletedStatuses = 0;
        $failedStatuses = 0;

        if ($collection->count()) {
            foreach ($collection->getItems() as $status) {
                try {
                    $this->repository->delete($status);
                    $deletedStatuses++;
                } catch (LocalizedException $e) {
                    $this->messageManager->addErrorMessage(
                        __($e->getMessage())
                    );
                    $failedStatuses++;
                } catch (Exception $e) {
                    $this->logger->error(
                        __('Error occurred while deleting status with ID %1. Error: %2'),
                        [$status->getStatusId(), $e->getMessage()]
                    );
                }
            }
        }

        if ($deletedStatuses !== 0) {
            $this->messageManager->addSuccessMessage(
                __('%1 status(es) has been successfully deleted', $deletedStatuses)
            );
        }

        if ($failedStatuses !== 0) {
            $this->messageManager->addErrorMessage(
                __('%1 status(es) has been failed to delete', $failedStatuses)
            );
        }

        return $this->resultRedirectFactory->create()->setRefererUrl();
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
