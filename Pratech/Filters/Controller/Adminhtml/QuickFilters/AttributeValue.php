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

use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Eav\Model\Config;
use Magento\Eav\Model\ResourceModel\Entity\Attribute\Option\CollectionFactory as OptionCollectionFactory;
use Magento\Framework\App\ResponseInterface;
use Magento\Framework\Controller\Result\Json;
use Magento\Framework\Controller\Result\JsonFactory;
use Magento\Framework\Controller\ResultInterface;
use Magento\Framework\Exception\LocalizedException;

class AttributeValue extends Action
{
    /**
     *
     * @param Context $context
     * @param Config $eavConfig
     * @param JsonFactory $resultJsonFactory
     * @param OptionCollectionFactory $optionCollectionFactory
     */
    public function __construct(
        Context                         $context,
        private Config                  $eavConfig,
        private JsonFactory             $resultJsonFactory,
        private OptionCollectionFactory $optionCollectionFactory
    ) {
        parent::__construct($context);
    }

    /**
     * Execute Method.
     *
     * @return ResponseInterface|Json|ResultInterface
     * @throws LocalizedException
     */
    public function execute()
    {
        $attributeCode = $this->getRequest()->getParam('attributeCode');
        $resultJson = $this->resultJsonFactory->create();

        $options = [];
        if ($attributeCode) {
            $collection = $this->optionCollectionFactory->create()->setAttributeFilter($attributeCode);
            $attribute = $this->eavConfig->getAttribute('catalog_product', $attributeCode);

            if ($attribute && $attribute->usesSource()) {
                $options = $attribute->getSource()->getAllOptions();
            }
        }

        return $resultJson->setData($options);
    }

    /**
     * Is Allowed
     *
     * @return bool
     */
    protected function _isAllowed(): bool
    {
        return $this->_authorization->isAllowed('Pratech_Filters::quick_filter');
    }
}
