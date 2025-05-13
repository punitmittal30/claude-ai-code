<?php
/**
 * Pratech_Blog
 *
 * PHP version 8.x
 *
 * @category  PHP
 * @package   Pratech\Blog
 * @author    Akash Panwar <akash.panwar@pratechbrands.com>
 * @copyright 2024 Copyright (c) Pratech Brands Private Limited
 * @link      https://pratechbrands.com/
 **/
declare(strict_types=1);

namespace Pratech\Blog\Controller\Adminhtml\Category;

use Magento\Framework\Exception\LocalizedException;
use Pratech\Base\Logger\Logger;
use Pratech\CmsBlock\Model\ImageUploader;

class Save extends \Magento\Backend\App\Action
{
    /**
     * @var \Magento\Framework\App\Request\DataPersistorInterface
     */
    protected $dataPersistor;

    /**
     * @param \Magento\Backend\App\Action\Context $context
     * @param \Magento\Framework\App\Request\DataPersistorInterface $dataPersistor
     * @param ImageUploader $imageUploader
     */
    public function __construct(
        \Magento\Backend\App\Action\Context $context,
        \Magento\Framework\App\Request\DataPersistorInterface $dataPersistor,
        protected ImageUploader $imageUploader
    ) {
        $this->dataPersistor = $dataPersistor;
        parent::__construct($context);
    }

    /**
     * Save action
     *
     * @return \Magento\Framework\Controller\ResultInterface
     */
    public function execute()
    {
        /** @var \Magento\Backend\Model\View\Result\Redirect $resultRedirect */
        $resultRedirect = $this->resultRedirectFactory->create();
        $data = $this->getRequest()->getPostValue();
        if ($data) {
            $id = $this->getRequest()->getParam('category_id');
        
            $model = $this->_objectManager->create(\Pratech\Blog\Model\Category::class)->load($id);
            if (!$model->getId() && $id) {
                $this->messageManager->addErrorMessage(__('This Category no longer exists.'));
                return $resultRedirect->setPath('*/*/');
            }

            $data['thumbnail_image'] = $this->getThumbnailImage($data);
            $data['thumbnail_image_mobile'] = $this->getThumbnailImageMobile($data);
            if (!$data['thumbnail_image_app_check'] || $data['thumbnail_image_app_check'] == 'false') {
                $data['thumbnail_image_app'] = $data['thumbnail_image_mobile'];
            } else {
                $data['thumbnail_image_app'] = $this->getThumbnailImageApp($data);
            }
            $data['banner_image'] = $this->getBannerImage($data);
            $data['banner_image_mobile'] = $this->getBannerImageMobile($data);
            if (!$data['banner_image_app_check'] || $data['banner_image_app_check'] == 'false') {
                $data['banner_image_app'] = $data['banner_image_mobile'];
            } else {
                $data['banner_image_app'] = $this->getBannerImageApp($data);
            }
            $model->setData($data);

            try {
                $model->save();
                $this->messageManager->addSuccessMessage(__('You saved the Category.'));
                $this->dataPersistor->clear('pratech_blog_category');
        
                if ($this->getRequest()->getParam('back')) {
                    return $resultRedirect->setPath('*/*/edit', ['category_id' => $model->getId()]);
                }
                return $resultRedirect->setPath('*/*/');
            } catch (LocalizedException $e) {
                $this->messageManager->addErrorMessage($e->getMessage());
            } catch (\Exception $e) {
                $this->messageManager->addExceptionMessage($e, __('Something went wrong while saving the Category.'));
            }
        
            $this->dataPersistor->set('pratech_blog_category', $data);
            return $resultRedirect->setPath(
                '*/*/edit',
                ['category_id' => $this->getRequest()->getParam('category_id')]
            );
        }
        return $resultRedirect->setPath('*/*/');
    }

    /**
     * Get Thumbnail Image
     *
     * @param array $data
     * @return mixed
     * @throws LocalizedException
     */
    protected function getThumbnailImage(array $data): mixed
    {
        if (isset($data['thumbnail_image'][0]['name']) &&
            isset($data['thumbnail_image'][0]['tmp_name'])) {
            $data['thumbnail_image'] = $data['thumbnail_image'][0]['name'];
            $this->imageUploader->moveFileFromTmp($data['thumbnail_image']);
        } elseif (isset($data['thumbnail_image'][0]['name']) &&
            !isset($data['thumbnail_image'][0]['tmp_name'])) {
            $data['thumbnail_image'] = $data['thumbnail_image'][0]['name'];
        } else {
            $data['thumbnail_image'] = '';
        }
        return $data['thumbnail_image'];
    }

    /**
     * Get Thumbnail Image
     *
     * @param array $data
     * @return mixed
     * @throws LocalizedException
     */
    protected function getThumbnailImageMobile(array $data): mixed
    {
        if (isset($data['thumbnail_image_mobile'][0]['name']) &&
            isset($data['thumbnail_image_mobile'][0]['tmp_name'])) {
            $data['thumbnail_image_mobile'] = $data['thumbnail_image_mobile'][0]['name'];
            $this->imageUploader->moveFileFromTmp($data['thumbnail_image_mobile']);
        } elseif (isset($data['thumbnail_image_mobile'][0]['name']) &&
            !isset($data['thumbnail_image_mobile'][0]['tmp_name'])) {
            $data['thumbnail_image_mobile'] = $data['thumbnail_image_mobile'][0]['name'];
        } else {
            $data['thumbnail_image_mobile'] = '';
        }
        return $data['thumbnail_image_mobile'];
    }

    /**
     * Get Thumbnail Image
     *
     * @param array $data
     * @return mixed
     * @throws LocalizedException
     */
    protected function getThumbnailImageApp(array $data): mixed
    {
        if (isset($data['thumbnail_image_app'][0]['name']) &&
            isset($data['thumbnail_image_app'][0]['tmp_name'])) {
            $data['thumbnail_image_app'] = $data['thumbnail_image_app'][0]['name'];
            $this->imageUploader->moveFileFromTmp($data['thumbnail_image_app']);
        } elseif (isset($data['thumbnail_image_app'][0]['name']) &&
            !isset($data['thumbnail_image_app'][0]['tmp_name'])) {
            $data['thumbnail_image_app'] = $data['thumbnail_image_app'][0]['name'];
        } else {
            $data['thumbnail_image_app'] = '';
        }
        return $data['thumbnail_image_app'];
    }

    /**
     * Get Banner Image
     *
     * @param array $data
     * @return mixed
     * @throws LocalizedException
     */
    protected function getBannerImage(array $data): mixed
    {
        if (isset($data['banner_image'][0]['name']) &&
            isset($data['banner_image'][0]['tmp_name'])) {
            $data['banner_image'] = $data['banner_image'][0]['name'];
            $this->imageUploader->moveFileFromTmp($data['banner_image']);
        } elseif (isset($data['banner_image'][0]['name']) &&
            !isset($data['banner_image'][0]['tmp_name'])) {
            $data['banner_image'] = $data['banner_image'][0]['name'];
        } else {
            $data['banner_image'] = '';
        }
        return $data['banner_image'];
    }

    /**
     * Get Banner Image
     *
     * @param array $data
     * @return mixed
     * @throws LocalizedException
     */
    protected function getBannerImageMobile(array $data): mixed
    {
        if (isset($data['banner_image_mobile'][0]['name']) &&
            isset($data['banner_image_mobile'][0]['tmp_name'])) {
            $data['banner_image_mobile'] = $data['banner_image_mobile'][0]['name'];
            $this->imageUploader->moveFileFromTmp($data['banner_image_mobile']);
        } elseif (isset($data['banner_image_mobile'][0]['name']) &&
            !isset($data['banner_image_mobile'][0]['tmp_name'])) {
            $data['banner_image_mobile'] = $data['banner_image_mobile'][0]['name'];
        } else {
            $data['banner_image_mobile'] = '';
        }
        return $data['banner_image_mobile'];
    }

    /**
     * Get Banner Image
     *
     * @param array $data
     * @return mixed
     * @throws LocalizedException
     */
    protected function getBannerImageApp(array $data): mixed
    {
        if (isset($data['banner_image_app'][0]['name']) &&
            isset($data['banner_image_app'][0]['tmp_name'])) {
            $data['banner_image_app'] = $data['banner_image_app'][0]['name'];
            $this->imageUploader->moveFileFromTmp($data['banner_image_app']);
        } elseif (isset($data['banner_image_app'][0]['name']) &&
            !isset($data['banner_image_app'][0]['tmp_name'])) {
            $data['banner_image_app'] = $data['banner_image_app'][0]['name'];
        } else {
            $data['banner_image_app'] = '';
        }
        return $data['banner_image_app'];
    }
}
