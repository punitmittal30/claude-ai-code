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

use Exception;
use Magento\Backend\App\Action;
use Magento\Backend\Model\Auth\Session;
use Magento\Backend\Model\View\Result\Redirect;
use Magento\Backend\Model\View\Result\RedirectFactory;
use Magento\Framework\App\ResponseInterface;
use Magento\Framework\Controller\ResultInterface;
use Magento\Framework\Exception\LocalizedException;
use Pratech\Search\Model\SearchTermsFactory;

/**
 * Search Term Save Controller
 */
class Save extends \Pratech\Search\Controller\Adminhtml\SearchTerms
{

    /**
     * Save constructor
     *
     * @param Action\Context     $context
     * @param SearchTermsFactory $searchTermsFactory
     * @param RedirectFactory    $redirectFactory
     * @param Session            $session
     */
    public function __construct(
        protected Action\Context  $context,
        protected SearchTermsFactory   $searchTermsFactory,
        protected RedirectFactory $redirectFactory,
        protected Session         $session
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
        $searchTerm = $this->_initSearchTerm(true);
        if (!$searchTerm) {
            /**
 * @var \Magento\Backend\Model\View\Result\Redirect $resultRedirect
*/
            $resultRedirect = $this->resultRedirectFactory->create();
            return $resultRedirect->setPath('pratech_search/*/', ['_current' => true, 'id' => null]);
        }
        $data = $this->getRequest()->getPostValue();
        if ($data) {
            try {
                $id = $data['entity_id'];
                $searchTerm = $this->searchTermsFactory->create()->load($id);

                $data = array_filter(
                    $data,
                    function ($value) {
                        return $value !== '';
                    }
                );

                $searchTerm->setData($data);
                $searchTerm->save();
                $this->_eventManager->dispatch('search_term_controller_save_after', ['searchTerm' => $searchTerm]);
                $this->messageManager->addSuccessMessage(__('Search term data successfully saved'));

                $this->_getSession()->setFormData(false);

                if ($this->getRequest()->getParam('back')) {
                    return $this->redirectFactory->create()->setPath(
                        '*/*/edit',
                        ['id' => $searchTerm->getId(), '_current' => true]
                    );
                }
            } catch (Exception $exception) {
                $this->messageManager->addErrorMessage(__($exception->getMessage()));
            }
        }
        return $this->redirectFactory->create()->setPath('*/*/index');
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
