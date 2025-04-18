<?php
/**
 * Hyuga_CacheManagement
 *
 * PHP version 8.x
 *
 * @category  PHP
 * @package   Hyuga\CacheManagement
 * @author    Puneet Mittal <puneet.mittal@pratechbrands.com>
 * @copyright 2025 Copyright (c) Pratech Brands Private Limited
 * @link      https://pratechbrands.com/
 **/

namespace Hyuga\CacheManagement\Controller\Adminhtml\Cache;

use Exception;
use Hyuga\CacheManagement\Api\CacheServiceInterface;
use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\Controller\Result\Redirect;

class FlushDarkStoreCache extends Action
{
    /**
     * @param Context $context
     * @param CacheServiceInterface $cacheService
     */
    public function __construct(
        Context                       $context,
        private CacheServiceInterface $cacheService
    ) {
        parent::__construct($context);
    }

    /**
     * Flush dark store cache
     *
     * @return Redirect
     */
    public function execute()
    {
        try {
            $this->cacheService->cleanAllDarkStoreCaches();
            $this->messageManager->addSuccessMessage(__('The dark store cache has been cleaned.'));
        } catch (Exception $e) {
            $this->messageManager->addErrorMessage(__(
                'An error occurred while cleaning the dark store cache: %1',
                $e->getMessage()
            ));
        }

        $resultRedirect = $this->resultRedirectFactory->create();
        return $resultRedirect->setPath('adminhtml/cache');
    }

    /**
     * Check admin permissions
     *
     * @return bool
     */
    protected function _isAllowed(): bool
    {
        return $this->_authorization->isAllowed('Magento_Backend::cache');
    }
}
