<?php
/**
 * Pratech_CmsBlock
 *
 * PHP version 8.x
 *
 * @category  PHP
 * @package   Pratech\CmsBlock
 * @author    Vivek Kumar <vivek.kumar@pratechbrands.com>
 * @copyright 2022 Copyright (c) Pratech Brands Private Limited
 * @link      https://pratechbrands.com/
 **/

namespace Pratech\CmsBlock\Controller\Adminhtml\Author;

use Exception;
use Magento\Backend\App\Action;
use Magento\Backend\Model\View\Result\Redirect;
use Magento\Backend\Model\View\Result\RedirectFactory;
use Magento\Framework\App\ResponseInterface;
use Magento\Framework\Controller\ResultInterface;
use Pratech\CmsBlock\Model\Author;

/**
 * Class Delete
 * Delete controller to delete a author.
 */
class Delete extends Action
{
    /**
     * @var Redirect
     */
    protected $redirect;

    /**
     * Delete Author constructor
     *
     * @param Action\Context  $context
     * @param Author          $author
     * @param RedirectFactory $redirectFactory
     */
    public function __construct(
        Action\Context  $context,
        protected Author          $author,
        protected RedirectFactory $redirectFactory
    ) {
        $this->redirect = $redirectFactory->create();
        parent::__construct($context);
    }

    /**
     * Execute
     *
     * @return Redirect|ResponseInterface|ResultInterface
     */
    public function execute()
    {
        $id = $this->getRequest()->getParam('id');
        if ($id) {
            $this->author->load($id);
            try {
                $this->author->delete();
                $this->_eventManager->dispatch('author_controller_delete_after', ['author' => $this->author]);
                $this->messageManager->addSuccessMessage(__('Author has been successfully removed'));
            } catch (Exception $exception) {
                $this->messageManager->addErrorMessage(__($exception->getMessage()));
                return $this->redirect->setPath('*/*/edit', ['id' => $id]);
            }
        }
        return $this->redirect->setPath('*/*/index');
    }

    /**
     * Is Allowed
     *
     * @return bool
     */
    protected function _isAllowed()
    {
        return $this->_authorization->isAllowed('Pratech_CmsBlock::authors');
    }
}
