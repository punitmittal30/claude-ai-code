<?php
/**
 * Pratech_Filters
 *
 * PHP version 8.x
 *
 * @category  PHP
 * @package   Pratech\Filters
 * @author    Vivek Kumar <vivek.kumar@pratechbrands.com>
 * @copyright 2024 Copyright (c) Pratech Brands Private Limited
 * @link      https://pratechbrands.com/
 **/

namespace Pratech\Filters\Controller\Adminhtml\QuickFilters;

use Exception;
use Magento\Backend\App\Action;
use Magento\Backend\Model\Auth\Session;
use Magento\Backend\Model\View\Result\Redirect;
use Magento\Backend\Model\View\Result\RedirectFactory;
use Magento\Catalog\Api\CategoryRepositoryInterface;
use Magento\Eav\Model\Config;
use Magento\Framework\App\ResponseInterface;
use Magento\Framework\Controller\ResultInterface;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use Pratech\Filters\Model\QuickFiltersFactory;

/**
 * Quick Filters Save Controller
 */
class Save extends Action
{

    /**
     * Save constructor
     *
     * @param Action\Context $context
     * @param QuickFiltersFactory $quickFiltersFactory
     * @param RedirectFactory $redirectFactory
     * @param Session $session
     * @param Config $eavConfig
     * @param CategoryRepositoryInterface $categoryRepository
     */
    public function __construct(
        protected Action\Context              $context,
        protected QuickFiltersFactory         $quickFiltersFactory,
        protected RedirectFactory             $redirectFactory,
        protected Session                     $session,
        protected Config                      $eavConfig,
        protected CategoryRepositoryInterface $categoryRepository
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
            $filtersData = [];
            try {
                $id = $data['entity_id'];
                $quickFilters = $this->quickFiltersFactory->create()->load($id);

                $data = array_filter(
                    $data,
                    function ($value) {
                        return $value !== '';
                    }
                );
                if (isset($data['filters_data']) && is_array($data['filters_data'])) {
                    $filtersAttrData = $this->prepareFiltersAttrData($data['filters_data']);
                }
                if (isset($data['category_id'])) {
                    $data['category_name'] = $this->getCategoryNameById($data['category_id']);
                    $data['filters_data'] = json_encode($filtersAttrData);
                }
                $quickFilters->setData($data);
                $quickFilters->save();
                $this->_eventManager->dispatch(
                    'quick_filter_controller_save_after',
                    ['quickFilters' => $quickFilters]
                );
                $this->messageManager->addSuccessMessage(__('Quick Filters successfully saved'));

                $this->_getSession()->setFormData(false);

                if ($this->getRequest()->getParam('back')) {
                    return $this->redirectFactory->create()->setPath(
                        '*/*/edit',
                        ['id' => $quickFilters->getId(), '_current' => true]
                    );
                }
            } catch (Exception $exception) {
                $this->messageManager->addErrorMessage(__($exception->getMessage()));
            }
        }
        return $this->redirectFactory->create()->setPath('*/*/index');
    }

    /**
     * Prepare Filters Attribute Data
     *
     * @param array $filters
     * @return array
     */
    public function prepareFiltersAttrData(array $filters): array
    {
        $filterData = [];
        foreach ($filters as $filter) {
            if (isset($filter['attribute_type'])) {
                $attribute = $this->eavConfig->getAttribute('catalog_product', $filter['attribute_type']);
                if ($attribute) {
                    $attrdata = [
                        'attribute_type' => $filter['attribute_type'],
                        'attribute_label' => $filter['attribute_label'],
                        'sort_order' => $filter['sort_order']
                    ];
                    foreach ($filter['attribute_value'] as $value) {
                        $attrdata['attribute_value'][] = [
                            'value' => $value,
                            'label' => $attribute->getSource()->getOptionText($value)
                        ];
                    }
                    $filterData[] = $attrdata;
                }
            }
        }
        return $filterData;
    }

    /**
     * Get Category Name By Id
     *
     * @param int $categoryId
     * @return string
     */
    public function getCategoryNameById(int $categoryId): string
    {
        try {
            $category = $this->categoryRepository->get($categoryId);
            return $category->getName();
        } catch (NoSuchEntityException $e) {
            $this->messageManager->addErrorMessage(__($exception->getMessage()));
        }
        return '';
    }

    /**
     * Is Allowed
     *
     * @return bool
     */
    protected function _isAllowed()
    {
        return $this->_authorization->isAllowed('Pratech_Filters::quick_filter');
    }
}
