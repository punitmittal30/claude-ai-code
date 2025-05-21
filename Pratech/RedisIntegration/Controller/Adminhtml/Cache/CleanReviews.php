<?php
/**
 * Pratech_RedisIntegration
 *
 * PHP version 8.x
 *
 * @category  PHP
 * @package   Pratech\RedisIntegration
 * @author    Vivek Kumar <vivek.kumar@pratechbrands.com>
 * @copyright 2024 Copyright (c) Pratech Brands Private Limited
 * @link      https://pratechbrands.com/
 **/

namespace Pratech\RedisIntegration\Controller\Adminhtml\Cache;

use Exception;
use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Backend\Model\View\Result\Redirect;
use Magento\Backend\Model\View\Result\RedirectFactory;
use Magento\Framework\App\Action\HttpGetActionInterface as HttpGetActionInterface;
use Pratech\RedisIntegration\Model\ProductsRedisCache;

class CleanReviews extends Action implements HttpGetActionInterface
{
    /**
     * @var Redirect
     */
    protected $redirect;

    /**
     * @param Context $context
     * @param RedirectFactory $redirectFactory
     * @param ProductsRedisCache $productsRedisCache
     */
    public function __construct(
        Action\Context             $context,
        protected RedirectFactory  $redirectFactory,
        private ProductsRedisCache $productsRedisCache
    ) {
        $this->redirect = $redirectFactory->create();
        parent::__construct($context);
    }

    /**
     * Clean static files cache
     *
     * @return Redirect
     */
    public function execute(): Redirect
    {
        try {
            $this->productsRedisCache->deleteSearch();
            $this->productsRedisCache->deletePlp();
            $this->_eventManager->dispatch('clean_reviews_cache_after');
            $this->messageManager->addSuccessMessage(__('Reviews cache has been cleaned.'));
        } catch (Exception $exception) {
            $this->messageManager->addErrorMessage(__($exception->getMessage()));
            return $this->redirect->setPath('adminhtml/*');
        }
        return $this->redirect->setPath('adminhtml/*');
    }

    /**
     * Is Allowed
     *
     * @return bool
     */
    protected function _isAllowed(): bool
    {
        return $this->_authorization->isAllowed('Magento_Backend::flush_reviews');
    }
}
