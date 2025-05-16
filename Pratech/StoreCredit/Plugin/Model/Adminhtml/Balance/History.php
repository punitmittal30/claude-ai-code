<?php
/**
 * Pratech_StoreCredit
 *
 * PHP version 8.x
 *
 * @category  PHP
 * @package   Pratech\StoreCredit
 * @author    Himmat Singh <himmat.singh@pratechbrands.com>
 * @copyright 2024 Copyright (c) Pratech Brands Private Limited
 * @link      https://pratechbrands.com/
 **/
namespace Pratech\StoreCredit\Plugin\Model\Adminhtml\Balance;

use Magento\Framework\Model\Context;
use Magento\Framework\Registry;
use Magento\Framework\Mail\Template\TransportBuilder;
use Magento\Store\Model\StoreManagerInterface;
use Magento\Framework\View\DesignInterface;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Customer\Model\CustomerRegistry;
use Magento\Customer\Helper\View;
use Pratech\SqsIntegration\Model\SqsEvent;
use Pratech\StoreCredit\Helper\Data as StoreCreditHelper;
use Magento\Backend\Model\Auth\Session;
use Magento\Framework\Model\ResourceModel\AbstractResource;
use Magento\Framework\Data\Collection\AbstractDb;

class History extends \Magento\CustomerBalance\Model\Balance\History
{
    /**
     * @var Session
     */
    protected $_authSession;

    /**
     * @param Context $context
     * @param Registry $registry
     * @param TransportBuilder $transportBuilder
     * @param StoreManagerInterface $storeManager
     * @param DesignInterface $design
     * @param ScopeConfigInterface $scopeConfig
     * @param CustomerRegistry $customerRegistry
     * @param View $customerHelperView
     * @param SqsEvent $sqsEvent
     * @param StoreCreditHelper $storeCreditHelper
     * @param Session $authSession
     * @param AbstractResource $resource
     * @param AbstractDb $resourceCollection
     * @param array $data
     * @SuppressWarnings(PHPMD.ExcessiveParameterList)
     */
    public function __construct(
        Context $context,
        Registry $registry,
        TransportBuilder $transportBuilder,
        StoreManagerInterface $storeManager,
        DesignInterface $design,
        ScopeConfigInterface $scopeConfig,
        CustomerRegistry $customerRegistry,
        View $customerHelperView,
        private SqsEvent $sqsEvent,
        private StoreCreditHelper $storeCreditHelper,
        Session $authSession,
        AbstractResource $resource = null,
        AbstractDb $resourceCollection = null,
        array $data = []
    ) {
        $this->_authSession = $authSession;
        parent::__construct(
            $context,
            $registry,
            $transportBuilder,
            $storeManager,
            $design,
            $scopeConfig,
            $customerRegistry,
            $customerHelperView,
            $resource,
            $resourceCollection,
            $data
        );
    }

    /**
     * Send HCash Credit Event To SQS
     *
     * @return $this
     */
    public function beforeSave()
    {
        $balance = $this->getBalanceModel();
        
        if (in_array((int)$balance->getHistoryAction(), [self::ACTION_UPDATED])
            && !$balance->getUpdatedActionAdditionalInfo()
        ) {
            $eventData = $this->storeCreditHelper->getStoreCreditEventData(
                $balance->getCustomerId(),
                $balance->getAmountDelta(),
                $balance->getComment(),
                [
                    'event_name' => 'updateHcash'
                ]
            );
            $this->sqsEvent->sendStoreCreditEvent($eventData);
            $user = $this->_authSession->getUser();
            if ($user && $user->getUsername()) {
                if ($balance->getComment() === null || !trim($balance->getComment())) {
                    $this->setAdditionalInfo(__('By Hyugalife', $user->getUsername()));
                } else {
                    $this->setAdditionalInfo(__('By Hyugalife: %1', $balance->getComment()));
                }
            }
        }

        return parent::beforeSave();
    }
}
