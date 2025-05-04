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

 namespace Pratech\Search\Controller\Adminhtml;

use Magento\Framework\App\ObjectManager;
use Magento\Store\Model\Store;
use Magento\Framework\Controller\ResultFactory;

/**
 * Search Terms controller
 */
abstract class SearchTerms extends \Magento\Backend\App\Action
{
    /**
     * Authorization level of a basic admin session
     *
     * @see _isAllowed()
     */
    const ADMIN_RESOURCE = 'Pratech_Search::pratech_search';

    /**
     * @var \Magento\Framework\Registry
     */
    private $registry;


    /**
     * Search Terms Constructor
     *
     * @param \Magento\Backend\App\Action\Context $context
     * @param \Magento\Framework\Registry|null    $registry
     */
    public function __construct(
        \Magento\Backend\App\Action\Context $context,
        \Magento\Framework\Registry $registry = null,
    ) {
        $this->registry = $registry ?: ObjectManager::getInstance()->get(
            \Magento\Framework\Registry::class
        );
        parent::__construct($context);
    }

    /**
     * Initialize requested search term and put it into registry.
     *
     * @param  bool $getRootInstead
     * @return \Pratech\Search\Model\SearchTerms|false
     */
    protected function _initSearchTerm($getRootInstead = false)
    {
        $searchTermId = $this->resolveSearchTermId();
        $searchTerms = $this->_objectManager->create(\Pratech\Search\Model\SearchTerms::class);

        if ($searchTermId) {
            $searchTerms->load($searchTermId);
        }

        $this->registry->unregister('search_terms');
        $this->registry->unregister('current_search_terms');
        $this->registry->register('search_terms', $searchTerms);
        $this->registry->register('current_search_terms', $searchTerms);
        return $searchTerms;
    }

    /**
     * Resolve Search Term Id (from get or from post)
     *
     * @return int
     */
    private function resolveSearchTermId() : int
    {
        $searchTermId = (int)$this->getRequest()->getParam('id', false);

        return $searchTermId ?: (int)$this->getRequest()->getParam('entity_id', false);
    }

    /**
     * Build response for ajax request
     *
     * @param \Pratech\Search\Model\SearchTerms       $searchTerms
     * @param \Magento\Backend\Model\View\Result\Page $resultPage
     *
     * @return \Magento\Framework\Controller\Result\Json
     */
    protected function ajaxRequestResponse($searchTerm, $resultPage)
    {
        $eventResponse = new \Magento\Framework\DataObject(
            [
                'content' => $resultPage->getLayout()->getUiComponent('search_terms_form')->getFormHtml(),
                'messages' => $resultPage->getLayout()->getMessagesBlock()->getGroupedHtml(),
                'toolbar' => $resultPage->getLayout()->getBlock('page.actions.toolbar')->toHtml()
            ]
        );
        $this->_eventManager->dispatch(
            'search_terms_prepare_ajax_response',
            ['response' => $eventResponse, 'controller' => $this]
        );
        /**
 * @var \Magento\Framework\Controller\Result\Json $resultJson
*/
        $resultJson = $this->resultFactory->create(ResultFactory::TYPE_JSON);
        $resultJson->setHeader('Content-type', 'application/json', true);
        $resultJson->setData($eventResponse->getData());
        return $resultJson;
    }
}
