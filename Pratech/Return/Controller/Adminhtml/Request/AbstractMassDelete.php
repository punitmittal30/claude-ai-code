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

namespace Pratech\Return\Controller\Adminhtml\Request;

use Exception;
use Magento\Backend\App\Action;
use Magento\Framework\Exception\CouldNotDeleteException;
use Magento\Ui\Component\MassAction\Filter;
use Pratech\Return\Model\Request\Repository;
use Pratech\Return\Model\Request\ResourceModel\Collection;
use Pratech\Return\Model\Request\ResourceModel\CollectionFactory;
use Psr\Log\LoggerInterface;

abstract class AbstractMassDelete extends Action
{
    /**
     * Abstract Mass Delete Constructor
     *
     * @param Action\Context $context
     * @param Filter $filter
     * @param CollectionFactory $requestCollectionFactory
     * @param Repository $repository
     * @param LoggerInterface $logger
     */
    public function __construct(
        Action\Context            $context,
        private Filter            $filter,
        private CollectionFactory $requestCollectionFactory,
        private Repository        $repository,
        private LoggerInterface   $logger
    ) {
        parent::__construct($context);
    }

    public function execute()
    {
        $this->filter->applySelectionOnTargetProvider();
        /**
         * @var Collection $collection
         */
        $collection = $this->filter->getCollection($this->requestCollectionFactory->create());
        $deleted = 0;
        $failed = 0;

        foreach ($collection->getItems() as $request) {
            try {
                $this->repository->delete($request);
                $deleted++;
            } catch (CouldNotDeleteException $e) {
                $this->messageManager->addErrorMessage(__($e->getMessage()));
                $failed++;
            } catch (Exception $e) {
                $this->logger->error(
                    __('Error occurred while deleting Request with ID %1. Error: %2'),
                    [$request->getId(), $e->getMessage()]
                );
            }
        }

        if ($deleted !== 0) {
            $this->messageManager->addSuccessMessage(
                __('%1 request(s) has been successfully deleted', $deleted)
            );
        }

        if ($failed !== 0) {
            $this->messageManager->addErrorMessage(
                __('%1 request(s) has been failed to delete', $failed)
            );
        }

        return $this->resultRedirectFactory->create()->setRefererUrl();
    }
}
