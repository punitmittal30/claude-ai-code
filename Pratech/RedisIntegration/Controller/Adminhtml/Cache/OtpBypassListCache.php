<?php
/**
 * Pratech_RedisIntegration
 *
 * PHP version 8.x
 *
 * @category  PHP
 * @package   Pratech\RedisIntegration
 * @author    Vivek Kumar <vivek.kumar@pratechbrands.com>
 * @copyright 2025 Copyright (c) Pratech Brands Private Limited
 * @link      https://pratechbrands.com/
 **/

namespace Pratech\RedisIntegration\Controller\Adminhtml\Cache;

use Exception;
use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Backend\Model\View\Result\Redirect;
use Magento\Backend\Model\View\Result\RedirectFactory;
use Magento\Framework\App\Action\HttpGetActionInterface as HttpGetActionInterface;
use Pratech\RedisIntegration\Model\SystemConfigurationRedisCache;

class OtpBypassListCache extends Action implements HttpGetActionInterface
{
    /**
     * @var Redirect
     */
    protected $redirect;

    /**
     * @param Context $context
     * @param SystemConfigurationRedisCache $configRedisCache
     * @param RedirectFactory $redirectFactory
     */
    public function __construct(
        Action\Context                          $context,
        protected SystemConfigurationRedisCache $configRedisCache,
        protected RedirectFactory               $redirectFactory
    )
    {
        $this->redirect = $redirectFactory->create();
        parent::__construct($context);
    }

    /**
     * Clean redis cache
     *
     * @return Redirect
     */
    public function execute(): Redirect
    {
        try {
            $this->configRedisCache->deleteOtpBypassListCache();
            $this->messageManager->addSuccessMessage(__('OTP Bypass List cache has been cleaned.'));
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
        return $this->_authorization->isAllowed('Magento_Backend::flush_system_config');
    }
}
