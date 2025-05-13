<?php
/**
 * Pratech_Recurring
 *
 * PHP version 8.x
 *
 * @category  PHP
 * @package   Pratech\Recurring
 * @author    Akash Panwar <akash.panwarr@pratechbrands.com>
 * @copyright 2025 Copyright (c) Pratech Brands Private Limited
 * @link      https://pratechbrands.com/
 **/
namespace Pratech\Recurring\Block\Adminhtml\Subscription;

use Magento\Backend\Block\Widget\Context;
use Magento\Customer\Block\Adminhtml\Edit\GenericButton;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\Registry;
use Magento\Framework\View\Element\UiComponent\Control\ButtonProviderInterface;

class UnsubscribeButton extends GenericButton implements ButtonProviderInterface
{
    public const REQUEST_KEY = "id";

    /**
     * Construct function
     *
     * @param Context $context
     * @param Registry $registry
     * @param RequestInterface $request
     */
    public function __construct(
        Context $context,
        Registry $registry,
        private RequestInterface $request
    ) {
        parent::__construct($context, $registry);
    }
    /**
     * Get button
     *
     * @return array
     */
    public function getButtonData()
    {
        return [
            'label' => __('Unsubscribe'),
            'on_click' => sprintf("location.href = '%s';", $this->getUnsubscribeUrl()),
            'sort_order' => 12
        ];
    }

    /**
     * Get URL for back (reset) button
     *
     * @return string
     */
    public function getUnsubscribeUrl()
    {
        $id = $this->request->getParam(self::REQUEST_KEY);
        return $this->getUrl(
            '*/*/unsubscribe',
            ['id' => $id]
        );
    }
}
