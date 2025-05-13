<?php
/**
 * Pratech_VideoContent
 *
 * PHP version 8.x
 *
 * @category  PHP
 * @package   Pratech\VideoContent
 * @author    Akash Panwar <akash.panwar@pratechbrands.com>
 * @copyright 2025 Copyright (c) Pratech Brands Private Limited
 * @link      https://pratechbrands.com/
 **/

namespace Pratech\VideoContent\Controller\Adminhtml\Slider;

use Magento\Backend\App\Action;
use Magento\Backend\Model\View\Result\Redirect;
use Magento\Backend\Model\View\Result\RedirectFactory;
use Magento\Framework\App\ResponseInterface;
use Magento\Framework\Controller\ResultInterface;
use Magento\Framework\Exception\LocalizedException;
use Magento\Ui\Component\MassAction\Filter;
use Pratech\VideoContent\Model\ResourceModel\Slider\CollectionFactory;

/**
 * MassDelete slides in slide grid.
 */
class MassDelete extends Action
{
    /**
     * MassDelete constructor
     *
     * @param Filter $filter
     * @param CollectionFactory $collectionFactory
     * @param RedirectFactory $redirectFactory
     * @param Action\Context $context
     */
    public function __construct(
        protected Filter $filter,
        protected CollectionFactory $collectionFactory,
        protected RedirectFactory $redirectFactory,
        Action\Context    $context
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

        $this->_eventManager->dispatch('video_slider_controller_delete_after', ['collection' => $collection]);

        $this->messageManager->addSuccessMessage(__("Number of records deleted : " . $size));

        return $this->redirectFactory->create()->setPath('*/*/index');
    }
}
