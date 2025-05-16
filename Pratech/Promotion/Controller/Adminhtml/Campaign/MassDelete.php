<?php
/**
 * Pratech_Promotion
 *
 * PHP version 8.x
 *
 * @category  PHP
 * @package   Pratech\Promotion
 * @author    Puneet Mittal <puneet.mittal@pratechbrands.com>
 * @copyright 2024 Copyright (c) Pratech Brands Private Limited
 * @link      https://pratechbrands.com/
 **/

namespace Pratech\Promotion\Controller\Adminhtml\Campaign;

use Magento\Backend\App\Action;
use Magento\Backend\Model\View\Result\Redirect;
use Magento\Backend\Model\View\Result\RedirectFactory;
use Magento\Framework\App\ResponseInterface;
use Magento\Framework\Controller\ResultInterface;
use Magento\Framework\Exception\LocalizedException;
use Magento\Ui\Component\MassAction\Filter;
use Pratech\Promotion\Model\ResourceModel\Campaign\CollectionFactory;

/**
 * MassDelete campaign in campaign grid.
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
        $this->_eventManager->dispatch('campaign_controller_delete_after', ['collection' => $collection]);
        $this->messageManager->addSuccessMessage(__("Number of records deleted : " . $size));

        return $this->redirectFactory->create()->setPath('*/*/index');
    }
}
