<?php
/**
 * Pratech_VideoContent
 *
 * PHP version 8.x
 *
 * @category  PHP
 * @package   Pratech\VideoContent
 * @author    Vivek Kumar <vivek.kumar@pratechbrands.com>
 * @copyright 2025 Copyright (c) Pratech Brands Private Limited
 * @link      https://pratechbrands.com/
 **/

namespace Pratech\VideoContent\Controller\Adminhtml\Videos;

use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\App\ResponseInterface;
use Magento\Framework\Controller\Result\Redirect;
use Magento\Framework\Controller\ResultInterface;
use Magento\Framework\View\Result\Page;
use Magento\Framework\View\Result\PageFactory;
use Pratech\VideoContent\Model\Video;

/**
 * Edit controller to manage edit action in video management.
 */
class Edit extends Action
{

    /**
     * Edit constructor
     *
     * @param Context $context
     * @param PageFactory $pageFactory
     * @param Video $video
     */
    public function __construct(
        protected Action\Context $context,
        protected PageFactory    $pageFactory,
        protected Video          $video,
    )
    {
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
        $video = $this->video;
        if ($id) {
            $video->load($id);
            if (!$video->getId()) {
                $this->messageManager->addErrorMessage(
                    __('This Video does not exists')
                );
                $result = $this->resultRedirectFactory->create();
                return $result->setPath('video/video/index');
            }
        }

        $resultPage = $this->pageFactory->create();

        if ($id) {
            $resultPage->getConfig()->getTitle()->prepend('Edit Video');
        } else {
            $resultPage->getConfig()->getTitle()->prepend('Add Video');
        }

        return $resultPage;
    }

    /**
     * Is Allowed
     *
     * @return bool
     */
    protected function _isAllowed(): bool
    {
        return $this->_authorization->isAllowed('Pratech_VideoContent::manage_videos');
    }
}
