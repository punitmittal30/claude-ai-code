<?php
/**
 * Pratech_StoreCredit
 *
 * PHP version 8.x
 *
 * @category  PHP
 * @package   Pratech\StoreCredit
 * @author    Akash Panwar <akash.panwar@pratechbrands.com>
 * @copyright 2024 Copyright (c) Pratech Brands Private Limited
 * @link      https://pratechbrands.com/
 **/
declare(strict_types=1);

namespace Pratech\StoreCredit\Controller\Adminhtml\PendingOrder;

use Exception;
use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Backend\Model\View\Result\Redirect;
use Magento\Backend\Model\View\Result\RedirectFactory;
use Magento\Framework\App\ResponseInterface;
use Magento\Framework\Controller\ResultInterface;
use Magento\Framework\Exception\LocalizedException;
use Magento\Ui\Component\MassAction\Filter;
use Pratech\Base\Logger\Logger;
use Pratech\RedisIntegration\Model\CustomerRedisCache;
use Pratech\StoreCredit\Model\CreditPointsFactory;
use Pratech\StoreCredit\Model\ResourceModel\CreditPoints\CollectionFactory;

/**
 * Mass Mark Credited controller to change status to credited.
 */
class MassMarkCredited extends Action
{
    /**
     * Mass Mark Credited Constructor
     *
     * @param Filter $filter
     * @param CollectionFactory $collectionFactory
     * @param RedirectFactory $redirectFactory
     * @param Context $context
     * @param Logger $apiLogger
     * @param CustomerRedisCache $customerRedisCache
     * @param CreditPointsFactory $creditPointsFactory
     */
    public function __construct(
        protected Filter            $filter,
        protected CollectionFactory $collectionFactory,
        protected RedirectFactory   $redirectFactory,
        protected Action\Context    $context,
        private Logger              $apiLogger,
        private CustomerRedisCache  $customerRedisCache,
        private CreditPointsFactory $creditPointsFactory
    ) {
        parent::__construct($context);
    }

    /**
     * Catalog Filters delete action
     *
     * @return Redirect|ResponseInterface|ResultInterface
     * @throws LocalizedException
     */
    public function execute()
    {
        $collection = $this->filter->getCollection($this->collectionFactory->create());
        $recordsUpdated = 0;

        foreach ($collection as $creditPoint) {
            try {
                $creditPointsModel = $this->creditPointsFactory->create();
                $creditPointsModel->load($creditPoint->getStorecreditId())
                    ->setCreditedStatus(1)
                    ->save();
                $recordsUpdated++;
                $this->customerRedisCache->deleteCustomerStoreCreditTransactions(
                    (int)$creditPoint->getCustomerId()
                );
            } catch (Exception $exception) {
                $this->apiLogger->error($exception->getMessage() . __METHOD__);
            }
        }

        $this->messageManager->addSuccessMessage(__("Number of records updated : " . $recordsUpdated));

        return $this->redirectFactory->create()->setPath('*/*/');
    }

    /**
     * Is Allowed
     *
     * @return bool
     */
    protected function _isAllowed(): bool
    {
        return $this->_authorization->isAllowed('Pratech_StoreCredit::pendingorders');
    }
}
