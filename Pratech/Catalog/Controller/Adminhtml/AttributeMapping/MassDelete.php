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

use Magento\Backend\App\Action;
use Magento\Backend\Model\View\Result\Redirect;
use Magento\Backend\Model\View\Result\RedirectFactory;
use Magento\Framework\App\ResponseInterface;
use Magento\Framework\Controller\ResultInterface;
use Magento\Framework\Exception\LocalizedException;
use Magento\Ui\Component\MassAction\Filter;
use Pratech\Catalog\Model\ResourceModel\AttributeMapping\CollectionFactory;

/**
 * MassDelete AttributeMappings in attributeMapping grid.
 */
class MassDelete extends Action
{
    /**
     * MassDelete constructor
     *
     * @param Action\Context    $context
     * @param Filter            $filter
     * @param CollectionFactory $collectionFactory
     * @param RedirectFactory   $redirectFactory
     */
    public function __construct(
        Action\Context    $context,
        protected Filter            $filter,
        protected CollectionFactory $collectionFactory,
        protected RedirectFactory   $redirectFactory
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
        $collection = $this->filter->getCollection($this->collectionFactory->create());
        $size = $collection->getSize();

        $collection->walk('delete');
        $this->_eventManager->dispatch('attr_mapping_controller_delete_after', ['collection' => $collection]);
        $this->messageManager->addSuccessMessage(__("Number of records deleted : " . $size));

        return $this->redirectFactory->create()->setPath('*/*/index');
    }
}
