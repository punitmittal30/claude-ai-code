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
use Magento\Framework\App\Request\DataPersistorInterface;
use Magento\Framework\Exception\LocalizedException;
use Pratech\Return\Api\StatusRepositoryInterface;
use Pratech\Return\Model\Status\Status;

class Save extends Action
{
    /**
     * @param Action\Context $context
     * @param StatusRepositoryInterface $repository
     * @param DataPersistorInterface $dataPersistor
     */
    public function __construct(
        Action\Context                    $context,
        private StatusRepositoryInterface $repository,
        private DataPersistorInterface    $dataPersistor
    ) {
        parent::__construct($context);
    }

    /**
     * @inheritdoc
     */
    public function execute()
    {
        if ($data = $this->getRequest()->getParams()) {
            try {
                $statusId = 0;
                if ($statusId = (int)$this->getRequest()->getParam('status_id')) {
                    $model = $this->repository->getById($statusId);
                } else {
                    /**
                     * @var Status $model
                     */
                    $model = $this->repository->getEmptyStatusModel();
                }

                $model->addData($data);
                $this->repository->save($model);
                $this->messageManager->addSuccessMessage(__('You saved the item.'));

                if ($this->getRequest()->getParam('back')) {
                    return $this->resultRedirectFactory->create()->setPath(
                        '*/*/edit',
                        ['status_id' => $model->getId()]
                    );
                }
            } catch (LocalizedException $e) {
                $this->messageManager->addErrorMessage($e->getMessage());
                $this->dataPersistor->set('status_data', $data);
                if ($statusId) {
                    return $this->resultRedirectFactory->create()->setPath(
                        '*/*/edit',
                        ['status_id' => $statusId]
                    );
                } else {
                    return $this->resultRedirectFactory->create()->setPath('*/*/create');
                }
            }
        }

        return $this->resultRedirectFactory->create()->setPath('*/*/');
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
