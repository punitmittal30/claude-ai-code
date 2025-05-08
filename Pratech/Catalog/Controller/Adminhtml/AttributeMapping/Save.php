<?php
/**
 * Pratech_Catalog
 *
 * PHP version 8.x
 *
 * @category  PHP
 * @package   Pratech\Catalog
 * @author    Vivek Kumar <vivek.kumar@pratechbrands.com>
 * @copyright 2024 Copyright (c) Pratech Brands Private Limited
 * @link      https://pratechbrands.com/
 **/

namespace Pratech\Catalog\Controller\Adminhtml\AttributeMapping;

use Exception;
use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Backend\Model\View\Result\Redirect;
use Magento\Backend\Model\View\Result\RedirectFactory;
use Magento\Catalog\Model\CategoryFactory;
use Magento\Framework\App\ResponseInterface;
use Magento\Framework\Controller\ResultInterface;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Serialize\Serializer\Json;
use Pratech\Catalog\Model\AttributeMappingFactory;
use Psr\Log\LoggerInterface;

/**
 * AttributeMapping Save Controller
 */
class Save extends Action
{
    /**
     * @var Redirect
     */
    protected $redirect;

    /**
     * Save constructor
     *
     * @param Context                 $context
     * @param RedirectFactory         $redirectFactory
     * @param CategoryFactory         $categoryFactory
     * @param AttributeMappingFactory $attributeMappingFactory
     * @param Json                    $json
     */
    public function __construct(
        Action\Context        $context,
        RedirectFactory       $redirectFactory,
        private CategoryFactory $categoryFactory,
        private AttributeMappingFactory $attributeMappingFactory,
        private Json          $json
    ) {
        $this->redirect = $redirectFactory->create();
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
                $id = $data['mapping_id'];
                $attributeMapping = $this->attributeMappingFactory->create()->load($id);

                if (isset($data['category_id'])) {
                    $category = $this->categoryFactory->create()->load($data['category_id']);
                    $attributeMapping->setCategorySlug($category->getUrlKey());
                    $attributeMapping->setCategoryId($data['category_id']);
                }

                if (isset($data['attributes'])) {
                    $attributes = implode(',', $data['attributes']);
                    $attributeMapping->setAttributes($attributes);
                }
                
                $attributeMapping->save();

                $this->_eventManager->dispatch(
                    'attr_mapping_controller_save_after',
                    ['attributeMapping' => $attributeMapping]
                );

                $this->messageManager->addSuccessMessage(__('AttributeMapping data successfully saved'));

                $this->_getSession()->setFormData(false);

                if ($this->getRequest()->getParam('back')) {
                    return $this->redirect->setPath(
                        '*/*/edit',
                        ['id' => $attributeMapping->getId(), '_current' => true]
                    );
                }
            } catch (Exception $exception) {
                $this->messageManager->addErrorMessage(__($exception->getMessage()));
            }
        }
        return $this->redirect->setPath('*/*/index');
    }

    /**
     * Is Allowed
     *
     * @return bool
     */
    protected function _isAllowed(): bool
    {
        return $this->_authorization->isAllowed('Pratech_Catalog::attributes_mapping');
    }
}
