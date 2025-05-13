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

use Exception;
use Magento\Backend\App\Action;
use Magento\Backend\Model\View\Result\Redirect;
use Magento\Backend\Model\View\Result\RedirectFactory;
use Magento\Framework\App\ResponseInterface;
use Magento\Framework\Controller\ResultInterface;
use Pratech\VideoContent\Model\VideoFactory;

/**
 * Delete controller to delete a videos.
 */
class Delete extends Action
{

    /**
     * NewAction constructor
     *
     * @param Action\Context $context
     * @param VideoFactory $videoFactory
     * @param RedirectFactory $redirectFactory
     */
    public function __construct(
        protected Action\Context  $context,
        protected VideoFactory    $videoFactory,
        protected RedirectFactory $redirectFactory
    )
    {
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
            $video = $this->videoFactory->create()->load($id);
            try {
                $video->delete();
                $this->_eventManager->dispatch(
                    'video_controller_delete_after',
                    ['video' => $video]
                );
                $this->messageManager->addSuccessMessage(
                    __('Video has been successfully removed')
                );
            } catch (Exception $exception) {
                $this->messageManager->addErrorMessage(__($exception->getMessage()));
                return $this->redirectFactory->create()
                    ->setPath('*/*/edit', ['id' => $id]);
            }
        }
        return $this->redirectFactory->create()->setPath('*/*/index');
    }

    /**
     * Is Allowed
     *
     * @return bool
     */
    protected function _isAllowed(): bool
    {
        return $this->_authorization->isAllowed('Pratech_VideoContent::video');
    }
}
