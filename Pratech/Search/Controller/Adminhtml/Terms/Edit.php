<?php
/**
 * Pratech_Search
 *
 * PHP version 8.x
 *
 * @category  PHP
 * @package   Pratech\Search
 * @author    Vivek Kumar <vivek.kumar@pratechbrands.com>
 * @copyright 2023 Copyright (c) Pratech Brands Private Limited
 * @link      https://pratechbrands.com/
 **/

namespace Pratech\Search\Controller\Adminhtml\Terms;

use Magento\Backend\App\Action;
use Magento\Framework\App\ResponseInterface;
use Magento\Framework\Controller\Result\Redirect;
use Magento\Framework\Controller\ResultInterface;
use Magento\Framework\Registry;
use Magento\Framework\View\Result\Page;
use Magento\Framework\View\Result\PageFactory;
use Pratech\Search\Model\SearchTerms;

/**
 * Edit controller to manage edit action in terms management.
 */
class Edit extends \Pratech\Search\Controller\Adminhtml\SearchTerms
{

    /**
     * Edit constructor
     *
     * @param Action\Context $context
     * @param PageFactory    $pageFactory
     * @param Registry       $registry
     * @param Banner         $searchTerms
     */
    public function __construct(
        protected Action\Context $context,
        protected PageFactory    $pageFactory,
        protected Registry       $registry,
        protected SearchTerms         $searchTerms
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
        $searchTerm = $this->_initSearchTerm(true);
        if (!$searchTerm) {
            /**
 * @var \Magento\Backend\Model\View\Result\Redirect $resultRedirect
*/
            $resultRedirect = $this->resultRedirectFactory->create();
            return $resultRedirect->setPath('pratech_search/*/', ['_current' => true, 'id' => null]);
        }

        $id = $this->getRequest()->getParam('id');
        $searchTerms = $this->searchTerms;
        if ($id) {
            $searchTerms->load($id);
            if (!$searchTerms->getId()) {
                $this->messageManager->addErrorMessage(__('This Search Term does not exists'));
                $result = $this->resultRedirectFactory->create();
                return $result->setPath('pratech_search/terms/index');
            }
        }
        $data = $this->_getSession()->getFormData(true);
        if (!empty($data)) {
            $searchTerms->setData($data);
        }

        $this->registry->register('searchTerms', $searchTerms);
        /**
 * @var Page $resultPage
*/
        $resultPage = $this->pageFactory->create();

        if ($id) {
            $resultPage->getConfig()->getTitle()->prepend('Edit Search Term');
        } else {
            $resultPage->getConfig()->getTitle()->prepend('Add Search Term');
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
        return $this->_authorization->isAllowed('Pratech_Search::pratech_search');
    }
}
