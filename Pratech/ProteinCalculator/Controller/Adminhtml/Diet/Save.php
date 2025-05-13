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

use Magento\Backend\App\Action\Context;
use Magento\Framework\App\Request\DataPersistorInterface;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Controller\ResultInterface;
use Magento\Backend\Model\View\Result\Redirect;
use Pratech\ProteinCalculator\Model\Diet;

class Save extends \Magento\Backend\App\Action
{

    /**
     * @var DataPersistorInterface
     */
    protected $dataPersistor;

    /**
     * @param Context $context
     * @param DataPersistorInterface $dataPersistor
     */
    public function __construct(
        Context $context,
        DataPersistorInterface $dataPersistor
    ) {
        parent::__construct($context);
        $this->dataPersistor = $dataPersistor;
    }

    /**
     * Save action
     *
     * @return ResultInterface
     */
    public function execute()
    {
        /** @var Redirect $resultRedirect */
        $resultRedirect = $this->resultRedirectFactory->create();
        $data = $this->getRequest()->getPostValue();
        
        if ($data) {
            $id = $this->getRequest()->getParam('entity_id');
           
            $model = $this->_objectManager->create(Diet::class)->load($id);
            if (!$model->getId() && $id) {
                $this->messageManager->addErrorMessage(__('This Diet no longer exists.'));
                return $resultRedirect->setPath('*/*/');
            }
            
            // Ensure diet field is an array
            if (isset($data['diet']) && is_array($data['diet'])) {
                $model->setDiet($data['diet']);
            }
            
            // Decode the Product Id JSON data
            if (isset($data['product_id'])) {
                $dietProducts = json_decode($data['product_id'], true);

                // Ensure $dietProducts is a proper array before filtering and encoding
                if (is_array($dietProducts)) {
                    // Filter out invalid keys
                    $filteredDietProducts = array_filter($dietProducts, function ($key) {
                        return is_numeric($key);
                    }, ARRAY_FILTER_USE_KEY);
                
                    $data['product_id'] = json_encode($filteredDietProducts);
                    
                } else {
                    $data['product_id'] = '[]'; // or handle as necessary
                }
            }

            $model->setDietType($data['diet_type']);
            $model->setBudget($data['budget']);
            
            // Encode the diet_products array back to a JSON string
            if (isset($data['product_id'])) {
                $model->setProductId($data['product_id']);
            }

            try {
                $model->save();
                $this->messageManager->addSuccessMessage(__('You saved the Diet.'));
                $this->dataPersistor->clear('pratech_protein_diet');
        
                if ($this->getRequest()->getParam('back')) {
                    return $resultRedirect->setPath('*/*/edit', ['entity_id' => $model->getId()]);
                }
                return $resultRedirect->setPath('*/*/');
            } catch (LocalizedException $e) {
                $this->messageManager->addErrorMessage($e->getMessage());
            } catch (\Exception $e) {
                $this->messageManager->addExceptionMessage($e, __('Something went wrong while saving the Diet.'));
            }
        
            $this->dataPersistor->set('pratech_protein_diet', $data);
            return $resultRedirect->setPath('*/*/edit', ['entity_id' => $this->getRequest()->getParam('entity_id')]);
        }
        return $resultRedirect->setPath('*/*/');
    }

    /**
     * Is Allowed
     *
     * @return bool
     */
    protected function _isAllowed()
    {
        return $this->_authorization->isAllowed('Pratech_ProteinCalculator::dietData');
    }
}
