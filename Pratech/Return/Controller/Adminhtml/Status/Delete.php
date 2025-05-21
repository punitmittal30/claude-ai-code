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
use Pratech\Return\Api\StatusRepositoryInterface;
use Psr\Log\LoggerInterface;

class Delete extends Action
{
    /**
     * @param Action\Context $context
     * @param StatusRepositoryInterface $repository
     * @param LoggerInterface $logger
     */
    public function __construct(
        Action\Context                    $context,
        private StatusRepositoryInterface $repository,
        private LoggerInterface           $logger
    ) {
        parent::__construct($context);
    }

    /**
     * Delete action
     */
    public function execute()
    {
        $id = (int)$this->getRequest()->getParam('status_id');

        if ($id) {
            try {
                $this->repository->deleteById($id);
                $this->messageManager->addSuccessMessage(__('The status has been deleted.'));

                return $this->resultRedirectFactory->create()->setPath('return/*/');
            } catch (LocalizedException $e) {
                $this->messageManager->addErrorMessage($e->getMessage());
            } catch (Exception $e) {
                $this->messageManager->addErrorMessage(
                    __('Can\'t delete status right now. Please review the log and try again.')
                );
                $this->logger->critical($e);
            }

            return $this->resultRedirectFactory->create()->setPath(
                'return/*/edit',
                ['status_id' => $id]
            );
        } else {
            $this->messageManager->addErrorMessage(__('Can\'t find a status to delete.'));
        }

        return $this->resultRedirectFactory->create()->setPath('return/*/');
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
