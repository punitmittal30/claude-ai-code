<?php
/**
 * Pratech_VideoContent
 *
 * PHP version 8.x
 *
 * @category  PHP
 * @package   Pratech\VideoContent
 * @author    Akash Panwar <akash.panwar@pratechbrands.com>
 * @copyright 2025 Copyright (c) Pratech Brands Private Limited
 * @link      https://pratechbrands.com/
 **/

namespace Pratech\VideoContent\Controller\Adminhtml\Slider;

use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\Stdlib\DateTime\DateTime;
use Magento\Framework\View\Result\PageFactory;
use Magento\Framework\App\ResponseInterface;
use Magento\Framework\Controller\Result\Redirect;
use Magento\Framework\Controller\ResultInterface;
use Pratech\VideoContent\Model\SliderFactory;

/**
 * Slider Save Controller
 */
class Save extends Action
{
    /**
     * Save constructor.
     *
     * @param Context $context
     * @param PageFactory $resultPageFactory
     * @param SliderFactory $sliderFactory
     * @param DateTime $dateTime
     */
    public function __construct(
        Context $context,
        protected PageFactory $resultPageFactory,
        protected SliderFactory $sliderFactory,
        protected DateTime $dateTime
    ) {
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
        if (isset($data['video_slider_mapping'])) {
            $data['video_slider_mapping'] = $this->getSliderVideos($data['video_slider_mapping']);
        }

        if ($data) {
            try {
                $id = $data['slider_id'];
                $slider = $this->sliderFactory->create()->load($id);

                if (empty($id)) {
                    unset($data['slider_id']);
                }

                if (isset($data['page']) && is_array($data['page'])) {
                    $data['page'] = implode(',', $data['page']);
                }

                if (!empty($data['start_date'])) {
                    $data['start_date'] = $this->convertDateTime($data['start_date']);
                }

                if (!empty($data['end_date'])) {
                    $data['end_date'] = $this->convertDateTime($data['end_date']);
                }

                $data['posted_data'] = $this->getRequest()->getPostValue();

                $slider->setData($data);
                $slider->save();

                // $this->_eventManager->dispatch('video_slider_controller_save_after', ['slider' => $slider]);

                $this->messageManager->addSuccessMessage(__('Carousel Saved Successfully.'));
                $this->_getSession()->setFormData(false);

                if ($this->getRequest()->getParam('back')) {
                    return $resultRedirect->setPath(
                        '*/*/edit',
                        ['slider_id' => $slider->getId(), '_current' => true]
                    );
                }
            } catch (\Exception $e) {
                $this->messageManager->addErrorMessage($e->getMessage());
                $this->_getSession()->setFormData($data);
                return $resultRedirect->setPath('*/*/edit', ['id' => $slider->getId()]);
            }
        }
        return $resultRedirect->setPath('*/*/');
    }

    /**
     * Get Video Ids
     *
     * @param array $postVideoIds
     * @return string
     */
    protected function getSliderVideos($postVideoIds)
    {
        $videoIds = '';
        if (empty($postVideoIds)) {
            return $videoIds;
        }

        return implode(',', json_decode($postVideoIds, true));
    }

    /**
     * Convert date-time to MySQL format
     *
     * @param  string $dateTimeStr
     * @return string
     */
    private function convertDateTime(string $dateTimeStr): string
    {
        try {
            $dateTime = new \DateTime($dateTimeStr);
            return $dateTime->format('Y-m-d H:i:s');
        } catch (Exception $e) {
            return $this->dateTime->gmtDate();
        }
    }

    /**
     * Is Allowed
     *
     * @return bool
     */
    protected function _isAllowed(): bool
    {
        return $this->_authorization->isAllowed('Pratech_VideoContent::slider');
    }
}
