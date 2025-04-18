<?php
/**
 * Pratech_VideoContent
 *
 * PHP version 8.x
 *
 * @category  PHP
 * @package   Pratech\VideoContent
 * @author    Vivek Kumar
 * @copyright 2025 Copyright (c) Pratech Brands Private Limited
 * @link      https://pratechbrands.com/
 **/

namespace Pratech\VideoContent\Controller\Adminhtml\Videos;

use Exception;
use Magento\Backend\App\Action;
use Magento\Backend\Model\Auth\Session;
use Magento\Backend\Model\View\Result\Redirect;
use Magento\Backend\Model\View\Result\RedirectFactory;
use Magento\Framework\App\ResponseInterface;
use Magento\Framework\Controller\ResultInterface;
use Magento\Framework\Exception\LocalizedException;
use Pratech\VideoContent\Model\VideoFactory;
use Magento\Framework\Stdlib\DateTime\DateTime;

/**
 * Videos Save Controller
 */
class Save extends Action
{
    /**
     * Save constructor
     *
     * @param Action\Context $context
     * @param VideoFactory $videoFactory
     * @param RedirectFactory $redirectFactory
     * @param Session $session
     * @param DateTime $dateTime
     */
    public function __construct(
        protected Action\Context  $context,
        protected VideoFactory    $videoFactory,
        protected RedirectFactory $redirectFactory,
        protected Session         $session,
        protected DateTime        $dateTime
    )
    {
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

        if ($data) {
            try {
                $id = $data['entity_id'] ?? null;
                $video = $this->videoFactory->create();

                if ($id) {
                    $video->load($id);
                }

                $data = array_filter(
                    $data, function ($value) {
                        return $value !== '';
                    }
                );
                if (isset($data['platform']) && is_array($data['platform'])) {
                    $data['platform'] = implode(',', $data['platform']);
                }

                if (isset($data['page']) && is_array($data['page'])) {
                    $data['page'] = implode(',', $data['page']);
                }

                if (isset($data['cities']) && is_array($data['cities'])) {
                    $data['cities'] = implode(',', $data['cities']);
                }

                if (isset($data['video_for']) && is_array($data['video_for'])) {
                    $data['video_for'] = implode(',', $data['video_for']);
                }

                if (isset($data['url']) && is_array($data['url']) && isset($data['url'][0]['url'])) {
                    $data['url'] = $data['url'][0]['file'];
                } else {
                    unset($data['url']);
                }

                if (!empty($data['start_date'])) {
                    $data['start_date'] = $this->convertDateTime($data['start_date']);
                }

                if (!empty($data['end_date'])) {
                    $data['end_date'] = $this->convertDateTime($data['end_date']);
                }

                $video->setData($data);
                $video->save();

                $this->_eventManager->dispatch(
                    'video_controller_save_after',
                    ['video' => $video]
                );

                $this->messageManager->addSuccessMessage(__('Video successfully saved'));
                $this->_getSession()->setFormData(false);

                if ($this->getRequest()->getParam('back')) {
                    return $this->redirectFactory->create()->setPath(
                        '*/*/edit',
                        ['id' => $video->getId(), '_current' => true]
                    );
                }
            } catch (Exception $exception) {
                $this->messageManager->addErrorMessage(__($exception->getMessage()));
            }
        }
        return $this->redirectFactory->create()->setPath('*/*/index');
    }

    /**
     * Convert date-time to MySQL format
     *
     * @param string $dateTimeStr
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
        return $this->_authorization->isAllowed('Pratech_VideoContent::manage_videos');
    }
}
