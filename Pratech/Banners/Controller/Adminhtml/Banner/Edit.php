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

namespace Pratech\Banners\Controller\Adminhtml\Banner;

use Magento\Backend\App\Action;
use Magento\Framework\App\ResponseInterface;
use Magento\Framework\Controller\Result\Redirect;
use Magento\Framework\Controller\ResultInterface;
use Magento\Framework\Registry;
use Magento\Framework\View\Result\Page;
use Magento\Framework\View\Result\PageFactory;
use Pratech\Banners\Model\Banner;

/**
 * Edit controller to manage edit action in slide management.
 */
class Edit extends Action
{
    /**
     * @var PageFactory
     */
    protected $pageFactory;

    /**
     * @var Banner
     */
    protected $banner;

    /**
     * @var Registry
     */
    protected $registry;

    /**
     * Edit constructor
     *
     * @param Action\Context $context
     * @param PageFactory $pageFactory
     * @param Registry $registry
     * @param Banner $banner
     */
    public function __construct(
        Action\Context $context,
        PageFactory    $pageFactory,
        Registry       $registry,
        Banner         $banner
    ) {
        $this->registry = $registry;
        $this->banner = $banner;
        $this->pageFactory = $pageFactory;
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
        $banner = $this->banner;
        if ($id) {
            $banner->load($id);
            if (!$banner->getId()) {
                $this->messageManager->addErrorMessage(__('This banner does not exists'));
                $result = $this->resultRedirectFactory->create();
                return $result->setPath('custom/banner/index');
            }
        }
        $data = $this->_getSession()->getFormData(true);
        if (!empty($data)) {
            $banner->setData($data);
        }

        $this->registry->register('banner', $banner);
        /** @var Page $resultPage */
        $resultPage = $this->pageFactory->create();

        if ($id) {
            $resultPage->getConfig()->getTitle()->prepend('Edit Banner');
        } else {
            $resultPage->getConfig()->getTitle()->prepend('Add Banner');
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
        return $this->_authorization->isAllowed('Pratech_Banners::banner');
    }
}
