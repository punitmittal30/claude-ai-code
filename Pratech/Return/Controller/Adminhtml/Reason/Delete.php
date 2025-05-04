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

namespace Pratech\Return\Controller\Adminhtml\Reason;

use Exception;
use Magento\Backend\App\Action;
use Magento\Framework\Exception\CouldNotDeleteException;
use Magento\Framework\Exception\LocalizedException;
use Pratech\Return\Model\Reason\ReasonFactory;
use Psr\Log\LoggerInterface;

class Delete extends Action
{
    /**
     * Delete Reason Record Constructor
     *
     * @param Action\Context $context
     * @param ReasonFactory $reasonFactory
     * @param LoggerInterface $logger
     */
    public function __construct(
        Action\Context          $context,
        private ReasonFactory   $reasonFactory,
        private LoggerInterface $logger
    ) {
        parent::__construct($context);
    }

    /**
     * Delete action
     *
     * @throws CouldNotDeleteException
     */
    public function execute()
    {
        $id = (int)$this->getRequest()->getParam('reason_id');

        if ($id) {
            try {
                $reason = $this->reasonFactory->create()->load($id);
                $reason->delete();
                $this->messageManager->addSuccessMessage(__('The reason has been deleted.'));

                return $this->resultRedirectFactory->create()->setPath('return/*/');
            } catch (LocalizedException $e) {
                $this->messageManager->addErrorMessage($e->getMessage());
            } catch (Exception $e) {
                $this->messageManager->addErrorMessage(
                    __('Can\'t delete reason right now. Please review the log and try again.')
                );
                $this->logger->critical($e);
            }

            return $this->resultRedirectFactory->create()->setPath(
                'return/*/edit',
                ['reason_id' => $id]
            );
        } else {
            $this->messageManager->addErrorMessage(__('Can\'t find a reason to delete.'));
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
        return $this->_authorization->isAllowed('Pratech_Return::reason');
    }
}
