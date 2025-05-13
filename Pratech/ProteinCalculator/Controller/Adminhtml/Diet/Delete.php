<?php
/**
 * Pratech_ProteinCalculator
 *
 * PHP version 8.x
 *
 * @category  PHP
 * @package   Pratech\ProteinCalculator
 * @author    Himmat Singh <himmat.singh@pratechbrands.com>
 * @copyright 2024 Copyright (c) Pratech Brands Private Limited
 * @link      https://pratechbrands.com/
 **/

namespace Pratech\ProteinCalculator\Controller\Adminhtml\Diet;

use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Pratech\ProteinCalculator\Model\DietFactory;
use Magento\Framework\Controller\ResultFactory;
use Magento\Backend\Model\View\Result\Redirect;
use Magento\Framework\App\ResponseInterface;
use Magento\Framework\Controller\ResultInterface;

class Delete extends Action
{
    /**
     * @var DietFactory
     */
    protected DietFactory $dietFactory;

    /**
     * Delete constructor
     *
     * @param Context     $context
     * @param DietFactory $dietFactory
     */
    public function __construct(
        Context $context,
        DietFactory $dietFactory
    ) {
        parent::__construct($context);
        $this->dietFactory = $dietFactory;
    }

    /**
     * Execute
     *
     * @return Redirect|ResponseInterface|ResultInterface
     */
    public function execute()
    {
        $id = $this->getRequest()->getParam('entity_id');
        if ($id) {
            try {
                $model = $this->dietFactory->create();
                $model->load($id);
                $model->delete();
                $this->messageManager->addSuccessMessage(__('You deleted the diet.'));
            } catch (\Exception $e) {
                $this->messageManager->addErrorMessage($e->getMessage());
            }
        }

        return $this->_redirect('*/*/');
    }
}
