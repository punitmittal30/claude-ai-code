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

namespace Pratech\ReviewRatings\Controller\Adminhtml\Keywords;

use Exception;
use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\Controller\Result\Json;
use Magento\Framework\Controller\Result\JsonFactory;
use Magento\Framework\Controller\ResultInterface;
use Pratech\ReviewRatings\Model\Keywords;

class InlineEdit extends Action
{

    /**
     * @param Context $context
     * @param JsonFactory $jsonFactory
     */
    public function __construct(
        Context               $context,
        protected JsonFactory $jsonFactory
    ) {
        parent::__construct($context);
    }

    /**
     * Inline edit action
     *
     * @return ResultInterface
     */
    public function execute()
    {
        /** @var Json $resultJson */
        $resultJson = $this->jsonFactory->create();
        $error = false;
        $messages = [];

        if ($this->getRequest()->getParam('isAjax')) {
            $postItems = $this->getRequest()->getParam('items', []);
            if (!count($postItems)) {
                $messages[] = __('Please correct the data sent.');
                $error = true;
            } else {
                foreach (array_keys($postItems) as $modelId) {
                    /** @var Keywords $model */
                    $model = $this->_objectManager->create(Keywords::class)->load($modelId);
                    try {
                        $model->setData(array_merge($model->getData(), $postItems[$modelId]));
                        $model->save();
                    } catch (Exception $e) {
                        $messages[] = "[Keywords ID: {$modelId}]  {$e->getMessage()}";
                        $error = true;
                    }
                }
            }
        }

        return $resultJson->setData([
            'messages' => $messages,
            'error' => $error
        ]);
    }
}
