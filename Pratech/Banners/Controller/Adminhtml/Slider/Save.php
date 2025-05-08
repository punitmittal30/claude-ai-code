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

namespace Pratech\Banners\Controller\Adminhtml\Slider;

use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\Stdlib\DateTime\TimezoneInterface;
use Magento\Framework\View\Result\PageFactory;
use Pratech\Banners\Model\SliderFactory;
use Magento\Framework\App\ResponseInterface;
use Magento\Framework\Controller\Result\Redirect;
use Magento\Framework\Controller\ResultInterface;

/**
 * Slider Save Controller
 */
class Save extends Action
{
    /**
     * @var PageFactory
     */
    protected $resultPageFactory;

    /**
     * @var SliderFactory
     */
    protected $sliderFactory;

    /**
     * @var TimezoneInterface
     */
    protected $timezoneInterface;

    /**
     * Save constructor.
     *
     * @param Context $context
     * @param PageFactory $resultPageFactory
     * @param SliderFactory $sliderFactory
     * @param TimezoneInterface $timezoneInterface
     */
    public function __construct(
        Context       $context,
        PageFactory   $resultPageFactory,
        SliderFactory $sliderFactory,
        TimezoneInterface $timezoneInterface
    ) {
        $this->resultPageFactory = $resultPageFactory;
        $this->sliderFactory = $sliderFactory;
        $this->timezoneInterface = $timezoneInterface;
        parent::__construct($context);
    }

    /**
     * Execute
     *
     * @return ResponseInterface|Redirect|ResultInterface
     */
    public function execute()
    {
        $resultRedirect = $this->resultRedirectFactory->create();
        $data = $this->getRequest()->getPostValue();
        if (isset($data['pratech_slider_banner'])) {
            $data['pratech_slider_banner'] = $this->getSliderBanners($data['pratech_slider_banner']);
        }

        if ($data) {
            try {
                $id = $data['slider_id'];
                $slider = $this->sliderFactory->create()->load($id);

                if (empty($id)) {
                    unset($data['slider_id']);
                }

                if (!isset($data['start_date'])) {
                    $data['start_date'] = $this->timezoneInterface->date()->format('Y-m-d H:i:s');
                }

                $data['posted_data'] = $this->getRequest()->getPostValue();
                $slider->setData($data);
                $slider->save();
                $this->_eventManager->dispatch('slider_controller_save_after', ['slider' => $slider]);
                $this->messageManager->addSuccessMessage(__('Slider Saved Successfully.'));
                $this->_getSession()->setFormData(false);
                return $resultRedirect->setPath('*/*/');
            } catch (\Exception $e) {
                $this->messageManager->addErrorMessage($e->getMessage());
                $this->_getSession()->setFormData($data);
                return $resultRedirect->setPath('*/*/edit', ['id' => $slider->getId()]);
            }
        }

        return $resultRedirect->setPath('*/*/');
    }

    /**
     * Get Banner Ids
     *
     * @param $postBannerIds
     * @return string
     */
    protected function getSliderBanners($postBannerIds)
    {
        $bannerIds = '';
        if (empty($postBannerIds)) {
            return $bannerIds;
        }

        return implode(',', json_decode($postBannerIds, true));
    }

    /**
     * Return Location Existence
     *
     * @param $location
     * @return bool
     */
    protected function isLocationExist($location)
    {
        $sliderCollection = $this->sliderFactory->create()
            ->getCollection()
            ->addFieldToFilter('location', ['in' => $location]);

        if ($sliderCollection->getSize() > 0) {
            return true;
        }
        return false;
    }
}
