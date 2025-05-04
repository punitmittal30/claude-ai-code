<?php
/**
 * Pratech_CmsBlock
 *
 * PHP version 8.x
 *
 * @category  PHP
 * @package   Pratech\CmsBlock
 * @author    Puneet Mittal <puneet.mittal@pratechbrands.com>
 * @copyright 2022 Copyright (c) Pratech Brands Private Limited
 * @link      https://pratechbrands.com/
 **/

namespace Pratech\CmsBlock\Plugin\Controller\Adminhtml\Page;

use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Backend\Model\View\Result\Redirect;
use Magento\Cms\Api\Data\PageInterface;
use Magento\Cms\Api\PageRepositoryInterface;
use Magento\Cms\Controller\Adminhtml\Page\PostDataProcessor;
use Magento\Cms\Model\Page;
use Magento\Cms\Model\PageFactory;
use Magento\Framework\App\Action\HttpPostActionInterface;
use Magento\Framework\App\ObjectManager;
use Magento\Framework\App\Request\DataPersistorInterface;
use Magento\Framework\App\ResponseInterface;
use Magento\Framework\Controller\ResultInterface;
use Magento\Framework\Exception\LocalizedException;
use Pratech\Base\Logger\Logger;
use Pratech\CmsBlock\Model\ImageUploader;
use Psr\Log\LoggerInterface;
use Throwable;

class Save extends Action implements HttpPostActionInterface
{
    /**
     * Authorization level of a basic admin session
     *
     * @see _isAllowed()
     */
    public const ADMIN_RESOURCE = 'Magento_Cms::save';

    /**
     * @var PageFactory
     */
    private $pageFactory;

    /**
     * @var PageRepositoryInterface
     */
    private $pageRepository;

    /**
     * @param Context $context
     * @param PostDataProcessor $dataProcessor
     * @param DataPersistorInterface $dataPersistor
     * @param ImageUploader $imageUploader
     * @param Logger $apiLogger
     * @param PageFactory|null $pageFactory
     * @param PageRepositoryInterface|null $pageRepository
     */
    public function __construct(
        Action\Context                   $context,
        protected PostDataProcessor      $dataProcessor,
        protected DataPersistorInterface $dataPersistor,
        protected ImageUploader          $imageUploader,
        protected Logger                 $apiLogger,
        PageFactory                      $pageFactory = null,
        PageRepositoryInterface          $pageRepository = null
    ) {
        $this->pageFactory = $pageFactory ?: ObjectManager::getInstance()->get(PageFactory::class);
        $this->pageRepository = $pageRepository ?: ObjectManager::getInstance()->get(PageRepositoryInterface::class);
        parent::__construct($context);
    }

    /**
     * Execute Method
     *
     * @return Redirect|ResponseInterface|ResultInterface
     */
    public function execute()
    {
        $data = $this->getRequest()->getPostValue();
        /** @var Redirect $resultRedirect */
        $resultRedirect = $this->resultRedirectFactory->create();
        if ($data) {
            $data = $this->dataProcessor->filter($data);
            if (isset($data['is_active']) && $data['is_active'] === 'true') {
                $data['is_active'] = Page::STATUS_ENABLED;
            }
            if (empty($data['page_id'])) {
                $data['page_id'] = null;
            }

            /** @var Page $model */
            $model = $this->pageFactory->create();

            $id = $this->getRequest()->getParam('page_id');
            if ($id) {
                try {
                    $model = $this->pageRepository->getById($id);
                } catch (LocalizedException $e) {
                    $this->messageManager->addErrorMessage(__('This page no longer exists.'));
                    return $resultRedirect->setPath('*/*/');
                }
            }

            $data['layout_update_xml'] = $model->getLayoutUpdateXml();
            $data['custom_layout_update_xml'] = $model->getCustomLayoutUpdateXml();
            $data['featured_image'] = $this->getFeaturedImage($data);
            $data['thumbnail_image'] = $this->getThumbnailImage($data);
            $model->setData($data);

            try {
                $this->_eventManager->dispatch(
                    'cms_page_prepare_save',
                    ['page' => $model, 'request' => $this->getRequest()]
                );

                $this->pageRepository->save($model);
                $this->messageManager->addSuccessMessage(__('You saved the page.'));
                return $this->processResultRedirect($model, $resultRedirect, $data);
            } catch (LocalizedException $e) {
                $this->messageManager->addExceptionMessage($e->getPrevious() ?: $e);
            } catch (Throwable $e) {
                $this->messageManager->addErrorMessage(__('Something went wrong while saving the page.'));
            }

            $this->dataPersistor->set('cms_page', $data);
            return $resultRedirect->setPath('*/*/edit', ['page_id' => $this->getRequest()->getParam('page_id')]);
        }
        return $resultRedirect->setPath('*/*/');
    }

    /**
     * Get Featured Image
     *
     * @param array $data
     * @return ?string
     */
    protected function getFeaturedImage(array $data): ?string
    {
        try {
            if (isset($data['featured_image'][0]['name']) &&
                isset($data['featured_image'][0]['tmp_name'])) {
                $data['featured_image'] = $data['featured_image'][0]['name'];
                $this->imageUploader->moveFileFromTmp($data['featured_image']);
            } elseif (isset($data['featured_image'][0]['name']) &&
                !isset($data['featured_image'][0]['tmp_name'])) {
                $data['featured_image'] = $data['featured_image'][0]['name'];
            } else {
                $data['featured_image'] = null;
            }
            return $data['featured_image'];
        } catch (LocalizedException $exception) {
            $this->apiLogger->critical($exception->getMessage() . __METHOD__);
        }
        return null;
    }

    /**
     * Get Image
     *
     * @param array $data
     * @return ?string
     */
    protected function getThumbnailImage(array $data): ?string
    {
        try {
            if (isset($data['thumbnail_image'][0]['name']) &&
                isset($data['thumbnail_image'][0]['tmp_name'])) {
                $data['thumbnail_image'] = $data['thumbnail_image'][0]['name'];
                $this->imageUploader->moveFileFromTmp($data['thumbnail_image']);
            } elseif (isset($data['thumbnail_image'][0]['name']) &&
                !isset($data['thumbnail_image'][0]['tmp_name'])) {
                $data['thumbnail_image'] = $data['thumbnail_image'][0]['name'];
            } else {
                $data['thumbnail_image'] = null;
            }
            return $data['thumbnail_image'];
        } catch (LocalizedException $exception) {
            $this->apiLogger->critical($exception->getMessage() . __METHOD__);
        }
        return null;
    }

    /**
     * Process result redirect
     *
     * @param PageInterface $model
     * @param Redirect $resultRedirect
     * @param array $data
     * @return Redirect
     * @throws LocalizedException
     */
    private function processResultRedirect($model, $resultRedirect, $data)
    {
        if ($this->getRequest()->getParam('back', false) === 'duplicate') {
            $newPage = $this->pageFactory->create(['data' => $data]);
            $newPage->setId(null);
            $identifier = $model->getIdentifier() . '-' . uniqid();
            $newPage->setIdentifier($identifier);
            $newPage->setIsActive(false);
            $this->pageRepository->save($newPage);
            $this->messageManager->addSuccessMessage(__('You duplicated the page.'));
            return $resultRedirect->setPath(
                '*/*/edit',
                [
                    'page_id' => $newPage->getId(),
                    '_current' => true,
                ]
            );
        }
        $this->dataPersistor->clear('cms_page');
        if ($this->getRequest()->getParam('back')) {
            return $resultRedirect->setPath('*/*/edit', ['page_id' => $model->getId(), '_current' => true]);
        }
        return $resultRedirect->setPath('*/*/');
    }
}
