<?php

namespace Pratech\Recurring\Block\Adminhtml\Subscription;

use Magento\Framework\View\Element\UiComponent\Control\ButtonProviderInterface;

class UnsubscribeButton extends \Magento\Customer\Block\Adminhtml\Edit\GenericButton implements ButtonProviderInterface
{
    public const REQUEST_KEY = "id";

    /**
     * @var \Magento\Framework\App\RequestInterface
     */
    private $request;

    /**
     * Construct function
     *
     * @param \Magento\Backend\Block\Widget\Context $context
     * @param \Magento\Framework\Registry $registry
     * @param \Magento\Framework\App\RequestInterface $request
     */
    public function __construct(
        \Magento\Backend\Block\Widget\Context $context,
        \Magento\Framework\Registry $registry,
        \Magento\Framework\App\RequestInterface $request
    ) {
        parent::__construct($context, $registry);
        $this->request = $request;
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
