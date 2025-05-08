<?php
/**
 * Pratech_Filters
 *
 * PHP version 8.x
 *
 * @category  PHP
 * @package   Pratech\Filters
 * @author    Vivek Kumar <vivek.kumar@pratechbrands.com>
 * @copyright 2023 Copyright (c) Pratech Brands Private Limited
 * @link      https://pratechbrands.com/
 **/

namespace Pratech\Filters\Controller\Adminhtml\Filters;

use Exception;
use Magento\Backend\App\Action;
use Magento\Backend\Model\Auth\Session;
use Magento\Backend\Model\View\Result\Redirect;
use Magento\Backend\Model\View\Result\RedirectFactory;
use Magento\Catalog\Model\ResourceModel\Eav\Attribute as EavAttributes;
use Magento\Framework\App\ResponseInterface;
use Magento\Framework\Controller\ResultInterface;
use Magento\Framework\Exception\LocalizedException;
use Pratech\Filters\Model\FiltersPositionFactory;

/**
 * Filters Position Save Controller
 */
class Save extends Action
{

    /**
     * Save constructor
     *
     * @param Action\Context $context
     * @param FiltersPositionFactory $filtersPositionFactory
     * @param RedirectFactory $redirectFactory
     * @param Session $session
     * @param EavAttributes $eavAttributes
     */
    public function __construct(
        protected Action\Context         $context,
        protected FiltersPositionFactory $filtersPositionFactory,
        protected RedirectFactory        $redirectFactory,
        protected Session                $session,
        protected EavAttributes          $eavAttributes
    ) {
        parent::__construct($context);
    }

    /**
     * Execute
     *
     * @return Redirect|ResponseInterface|ResultInterface
     * @throws LocalizedException
     */
    public function execute()
    {
        $data = $this->getRequest()->getPostValue();
        if ($data) {
            try {
                $id = $data['entity_id'];
                $filtersPosition = $this->filtersPositionFactory->create()->load($id);

                $data = array_filter(
                    $data,
                    function ($value) {
                        return $value !== '';
                    }
                );
                if (isset($data['attribute_code'])) {
                    $attributeDetails = $this->eavAttributes
                        ->loadByCode('catalog_product', $data['attribute_code']);
                    $data['attribute_id'] = $attributeDetails->getId();
                    $data['attribute_name'] = $attributeDetails->getDefaultFrontendLabel();
                }
                $filtersPosition->setData($data);
                $filtersPosition->save();
                $this->_eventManager->dispatch(
                    'filters_position_controller_save_after',
                    ['filtersPosition' => $filtersPosition]
                );
                $this->messageManager->addSuccessMessage(__('Filter position successfully saved'));

                $this->_getSession()->setFormData(false);

                if ($this->getRequest()->getParam('back')) {
                    return $this->redirectFactory->create()->setPath(
                        '*/*/edit',
                        ['id' => $filtersPosition->getId(), '_current' => true]
                    );
                }
            } catch (Exception $exception) {
                $this->messageManager->addErrorMessage(__($exception->getMessage()));
            }
        }
        return $this->redirectFactory->create()->setPath('*/*/index');
    }

    /**
     * Is Allowed
     *
     * @return bool
     */
    protected function _isAllowed(): bool
    {
        return $this->_authorization->isAllowed('Pratech_Filters::filters_position');
    }
}
