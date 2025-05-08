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

use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\App\ObjectManager;
use Magento\Framework\App\Request\DataPersistorInterface;
use Magento\Framework\Event\ManagerInterface;
use Magento\Framework\Exception\LocalizedException;
use Pratech\Return\Model\Reason\ReasonFactory;

class Save extends Action
{
    /**
     * @var ManagerInterface
     */
    private $eventManager;

    /**
     * Save Reason Constructor
     *
     * @param Context $context
     * @param DataPersistorInterface $dataPersistor
     * @param ReasonFactory $reasonFactory
     */
    public function __construct(
        Context                        $context,
        private DataPersistorInterface $dataPersistor,
        private ReasonFactory          $reasonFactory
    ) {
        parent::__construct($context);
        $this->eventManager = $context->getEventManager() ?: ObjectManager::getInstance()
            ->get(ManagerInterface::class);
    }

    /**
     * @inheritdoc
     */
    public function execute()
    {
        if ($data = $this->getRequest()->getParams()) {
            try {
                $reasonId = 0;
                if ($reasonId = (int)$this->getRequest()->getParam('reason_id')) {
                    $model = $this->reasonFactory->create()->load($reasonId);
                } else {
                    $model = $this->reasonFactory->create();
                }

                $model->setData($data);
                $model->save();
                $this->eventManager->dispatch('return_reason_saved', ['reason' => $model]);
                $this->messageManager->addSuccessMessage(__('You saved the item.'));

                if ($this->getRequest()->getParam('back')) {
                    return $this->resultRedirectFactory->create()->setPath(
                        '*/*/edit',
                        ['reason_id' => $model->getId()]
                    );
                }
            } catch (LocalizedException $e) {
                $this->messageManager->addErrorMessage($e->getMessage());
                $this->dataPersistor->set('reason_data', $data);
                if ($reasonId) {
                    return $this->resultRedirectFactory->create()->setPath(
                        '*/*/edit',
                        ['reason_id' => $reasonId]
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
        return $this->_authorization->isAllowed('Pratech_Return::reason');
    }
}
