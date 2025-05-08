<?php
/**
 * Pratech_Banners
 *
 * PHP version 8.x
 *
 * @category  PHP
 * @package   Pratech\Banners
 * @author    Puneet Mittal <puneet.mittal@pratechbrands.com>
 * @copyright 2023 Copyright (c) Pratech Brands Private Limited
 * @link      https://pratechbrands.com/
 **/

namespace Pratech\Banners\Controller\Adminhtml\Slider;

use Magento\Backend\App\Action;
use Magento\Backend\Model\View\Result\Redirect;
use Magento\Backend\Model\View\Result\RedirectFactory;
use Magento\Framework\App\ResponseInterface;
use Magento\Framework\Controller\ResultInterface;
use Magento\Framework\Exception\LocalizedException;
use Magento\Ui\Component\MassAction\Filter;
use Pratech\Banners\Model\ResourceModel\Slider\CollectionFactory;

/**
 * MassDelete slides in slide grid.
 */
class MassDelete extends Action
{
    /**
     * @var Filter
     */
    protected $filter;

    /**
     * @var CollectionFactory
     */
    protected $collectionFactory;

    /**
     * @var RedirectFactory
     */
    protected $redirectFactory;

    /**
     * MassDelete constructor
     *
     * @param Filter $filter
     * @param CollectionFactory $collectionFactory
     * @param RedirectFactory $redirectFactory
     * @param Action\Context $context
     */
    public function __construct(
        Filter            $filter,
        CollectionFactory $collectionFactory,
        RedirectFactory   $redirectFactory,
        Action\Context    $context
    ) {
        $this->filter = $filter;
        $this->redirectFactory = $redirectFactory;
        $this->collectionFactory = $collectionFactory;
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

        $this->_eventManager->dispatch('slider_controller_delete_after', ['collection' => $collection]);

        $this->messageManager->addSuccessMessage(__("Number of records deleted : " . $size));

        return $this->redirectFactory->create()->setPath('*/*/index');
    }
}
