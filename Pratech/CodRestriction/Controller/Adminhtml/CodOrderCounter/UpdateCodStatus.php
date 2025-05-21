<?php
/**
 * Pratech_CodRestriction
 *
 * @category  XML
 * @package   Pratech\CodRestriction
 * @author    Vivek Kumar <vivek.kumar@pratechbrands.com>
 * @copyright 2025 Copyright (c) Pratech Brands Private Limited
 * @link      https://pratechbrands.com/
 */
namespace Pratech\CodRestriction\Controller\Adminhtml\CodOrderCounter;

use Exception;
use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\Controller\Result\RedirectFactory;
use Magento\Framework\Message\ManagerInterface;
use Pratech\CodRestriction\Model\CodOrderCounterFactory;

class UpdateCodStatus extends Action
{
    const ADMIN_RESOURCE = 'Pratech_CodRestriction::cod_order_counter';

    /**
     * @param Context                $context
     * @param RedirectFactory        $redirectFactory
     * @param CodOrderCounterFactory $codOrderCounterFactory
     */
    public function __construct(
        Context                          $context,
        protected RedirectFactory        $redirectFactory,
        protected CodOrderCounterFactory $codOrderCounterFactory
    ) {
        parent::__construct($context);
    }

    /**
     * Update COD Disable Status
     */
    public function execute()
    {
        $redirect = $this->redirectFactory->create();
        $customerId = (int)$this->getRequest()->getParam('customer_id');
        $status = (int)$this->getRequest()->getParam('status');

        if (!$customerId || !in_array($status, [0, 1])) {
            $this->messageManager->addErrorMessage(__('Invalid request.'));
            return $redirect->setPath('codrestriction/codordercounter/index');
        }

        try {
            $model = $this->codOrderCounterFactory->create()->load($customerId, 'customer_id');
            if (!$model->getId()) {
                $this->messageManager->addErrorMessage(__('Customer record not found.'));
                return $redirect->setPath('codrestriction/codordercounter/index');
            }

            $model->setIsCodDisabled($status);
            $model->save();

            $this->messageManager->addSuccessMessage(
                $status ? __('COD disabled successfully.') : __('COD enabled successfully.')
            );
        } catch (Exception $e) {
            $this->messageManager->addErrorMessage(__('Error: ') . $e->getMessage());
        }

        return $redirect->setPath('codrestriction/codordercounter/index');
    }
}
