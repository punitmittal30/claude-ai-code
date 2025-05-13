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

use Exception;
use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Backend\Model\View\Result\Redirect;
use Magento\Backend\Model\View\Result\RedirectFactory;
use Magento\Framework\App\ResponseInterface;
use Magento\Framework\Controller\ResultInterface;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Serialize\Serializer\Json;
use Pratech\Banners\Model\BannerFactory;
use Pratech\Banners\Model\ImageUploader;
use Psr\Log\LoggerInterface;

/**
 * Banner Save Controller
 */
class Save extends Action
{
    /**
     * @var Redirect
     */
    protected $redirect;

    /**
     * Save constructor
     *
     * @param Context $context
     * @param RedirectFactory $redirectFactory
     * @param BannerFactory $bannerFactory
     * @param ImageUploader $imageUploader
     * @param Json $json
     */
    public function __construct(
        Action\Context        $context,
        RedirectFactory       $redirectFactory,
        private BannerFactory $bannerFactory,
        private ImageUploader $imageUploader,
        private Json          $json
    ) {
        $this->redirect = $redirectFactory->create();
        parent::__construct($context);
    }

    /**
     * Execute
     *
     * @return Redirect|ResponseInterface|ResultInterface
     * @throws LocalizedException
     */
    public function execute()
    {
        $data = $this->getRequest()->getPostValue();

        if (isset($data['banner_products'])) {
            $data['posted_banner_products'] = $this->getBannerProducts($data['banner_products']);
            unset($data['banner_products']);
        }

        if ($data) {
            try {
                $id = $data['banner_id'];
                $banner = $this->bannerFactory->create()->load($id);

                if (empty($id)) {
                    unset($data['banner_id']);
                }

                $data['desktop_image'] = $this->getDesktopImage($data);
                $data['mobile_image'] = $this->getMobileImage($data);
                if (!$data['app_image_check'] || $data['app_image_check'] == 'false') {
                    $data['app_image'] = $data['mobile_image'];
                } else {
                    $data['app_image'] = $this->getAppImage($data);
                }

                $banner->setData($data);
                $banner->save();

                $this->_eventManager->dispatch(
                    'banner_controller_save_after',
                    ['banner' => $banner]
                );

                $this->messageManager->addSuccessMessage(__('Banner data successfully saved'));

                $this->_getSession()->setFormData(false);

                if ($this->getRequest()->getParam('back')) {
                    return $this->redirect->setPath(
                        '*/*/edit',
                        ['id' => $banner->getId(), '_current' => true]
                    );
                }
            } catch (Exception $exception) {
                $this->messageManager->addErrorMessage(__($exception->getMessage()));
            }
        }
        return $this->redirect->setPath('*/*/index');
    }

    /**
     * Convert Json To String.
     *
     * @param string $bannerProducts
     * @return array
     */
    protected function getBannerProducts(string $bannerProducts): array
    {
        $productIds = [];
        if (empty($bannerProducts)) {
            return $productIds;
        }

        return $this->json->unserialize($bannerProducts);
    }

    /**
     * Get Desktop Image
     *
     * @param array $data
     * @return mixed
     * @throws LocalizedException
     */
    protected function getDesktopImage(array $data): mixed
    {
        if (isset($data['desktop_image'][0]['name']) &&
            isset($data['desktop_image'][0]['tmp_name'])) {
            $data['desktop_image'] = $data['desktop_image'][0]['name'];
            $this->imageUploader->moveFileFromTmp($data['desktop_image']);
        } elseif (isset($data['desktop_image'][0]['name']) &&
            !isset($data['desktop_image'][0]['tmp_name'])) {
            $data['desktop_image'] = $data['desktop_image'][0]['name'];
        } else {
            $data['desktop_image'] = '';
        }

        return $data['desktop_image'];
    }

    /**
     * Get Mobile Image
     *
     * @param array $data
     * @return mixed
     * @throws LocalizedException
     */
    protected function getMobileImage(array $data): mixed
    {
        if (isset($data['mobile_image'][0]['name']) &&
            isset($data['mobile_image'][0]['tmp_name'])) {
            $data['mobile_image'] = $data['mobile_image'][0]['name'];
            $this->imageUploader->moveFileFromTmp($data['mobile_image']);
        } elseif (isset($data['mobile_image'][0]['name']) &&
            !isset($data['mobile_image'][0]['tmp_name'])) {
            $data['mobile_image'] = $data['mobile_image'][0]['name'];
        } else {
            $data['mobile_image'] = '';
        }
        return $data['mobile_image'];
    }

    /**
     * Get App Image
     *
     * @param array $data
     * @return mixed
     * @throws LocalizedException
     */
    protected function getAppImage(array $data): mixed
    {
        if (isset($data['app_image'][0]['name']) &&
            isset($data['app_image'][0]['tmp_name'])) {
            $data['app_image'] = $data['app_image'][0]['name'];
            $this->imageUploader->moveFileFromTmp($data['app_image']);
        } elseif (isset($data['app_image'][0]['name']) &&
            !isset($data['app_image'][0]['tmp_name'])) {
            $data['app_image'] = $data['app_image'][0]['name'];
        } else {
            $data['app_image'] = '';
        }
        return $data['app_image'];
    }

    /**
     * Is Allowed
     *
     * @return bool
     */
    protected function _isAllowed(): bool
    {
        return $this->_authorization->isAllowed('Pratech_Banners::banner');
    }
}
