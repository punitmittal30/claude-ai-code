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

use Magento\Backend\App\Action;
use Magento\Framework\App\ResponseInterface;
use Magento\Framework\Controller\Result\Redirect;
use Magento\Framework\Controller\ResultInterface;
use Magento\Framework\Registry;
use Magento\Framework\View\Result\Page;
use Magento\Framework\View\Result\PageFactory;
use Pratech\CmsBlock\Model\Author;

/**
 * Edit controller to manage edit action in author.
 */
class Edit extends Action
{
    /**
     * Edit constructor
     *
     * @param Action\Context $context
     * @param PageFactory    $pageFactory
     * @param Registry       $registry
     * @param Author         $author
     */
    public function __construct(
        Action\Context $context,
        protected PageFactory    $pageFactory,
        protected Registry       $registry,
        protected Author         $author
    ) {
        parent::__construct($context);
    }

    /**
     * Execute
     *
     * @return ResponseInterface|Redirect|ResultInterface|Page
     */
    public function execute()
    {
        $id = $this->getRequest()->getParam('id');
        $author = $this->author;
        if ($id) {
            $author->load($id);
            if (!$author->getId()) {
                $this->messageManager->addErrorMessage(__('This author does not exists'));
                $result = $this->resultRedirectFactory->create();
                return $result->setPath('custom/author/index');
            }
        }
        $data = $this->_getSession()->getFormData(true);
        if (!empty($data)) {
            $author->setData($data);
        }

        $this->registry->register('author', $author);
        /**
 * @var Page $resultPage
*/
        $resultPage = $this->pageFactory->create();

        if ($id) {
            $resultPage->getConfig()->getTitle()->prepend('Edit Author');
        } else {
            $resultPage->getConfig()->getTitle()->prepend('Add Author');
        }

        return $resultPage;
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
