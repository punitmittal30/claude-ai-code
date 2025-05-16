<?php
/**
 * Pratech_ReviewRatings
 *
 * PHP version 8.x
 *
 * @category  PHP
 * @package   Pratech\ReviewRatings
 * @author    Akash Panwar <akash.panwar@pratechbrands.com>
 * @copyright 2024 Copyright (c) Pratech Brands Private Limited
 * @link      https://pratechbrands.com/
 **/
declare(strict_types=1);

namespace Pratech\ReviewRatings\Controller\Adminhtml\Media;

use Exception;
use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Backend\Model\View\Result\Redirect;
use Magento\Framework\Controller\ResultFactory;
use Magento\Framework\App\ResponseInterface;
use Magento\Framework\Controller\ResultInterface;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\View\Result\PageFactory;
use Magento\Ui\Component\MassAction\Filter;
use Pratech\Base\Logger\Logger;
use Pratech\ReviewRatings\Model\MediaFactory;
use Pratech\ReviewRatings\Model\ResourceModel\Media\CollectionFactory;

/**
 * Mass Change Status controller to change status.
 */
class MassChangeStatus extends Action
{
    /**
     * Mass Change Status Constructor
     *
     * @param Context $context
     * @param PageFactory $resultPageFactory
     * @param Filter $filter
     * @param MediaFactory $mediaFactory
     * @param CollectionFactory $collectionFactory
     * @param Logger $apiLogger
     */
    public function __construct(
        Context                     $context,
        protected PageFactory       $resultPageFactory,
        protected Filter            $filter,
        protected MediaFactory      $mediaFactory,
        protected CollectionFactory $collectionFactory,
        protected Logger            $apiLogger
    ) {
        parent::__construct($context);
    }

    /**
     * Catalog Filters delete action
     *
     * @return Redirect|ResponseInterface|ResultInterface
     * @throws LocalizedException
     */
    public function execute()
    {
        try {
            $collection = $this->filter->getCollection($this->collectionFactory->create());
            $updated = 0;
            foreach ($collection as $item) {
                $media = $this->mediaFactory->create()->load($item->getMediaId());
                $media->setData('status', $this->getRequest()->getParam('status'))->save();
                $updated++;
            }
            if ($updated) {
                $this->messageManager->addSuccess(__('A total of %1 record(s) were updated.', $updated));
            }
        } catch (Exception $exception) {
            $this->apiLogger->error($exception->getMessage() . __METHOD__);
        }
        $resultRedirect = $this->resultFactory->create(ResultFactory::TYPE_REDIRECT);
        $resultRedirect->setUrl($this->_redirect->getRefererUrl());
        return $resultRedirect;
    }

    /**
     * Is Allowed
     *
     * @return bool
     */
    protected function _isAllowed()
    {
        return true;
    }
}
