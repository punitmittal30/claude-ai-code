<?php
/**
 * Retry failed product push cron
 *
 * @category  Hyuga
 * @package   Hyuga_WondersoftIntegration
 */

namespace Hyuga\WondersoftIntegration\Cron;

use Exception;
use Hyuga\WondersoftIntegration\Api\WondersoftApiInterface;
use Hyuga\WondersoftIntegration\Helper\Data as Helper;
use Hyuga\WondersoftIntegration\Logger\Logger;
use Magento\Catalog\Model\ResourceModel\Product\CollectionFactory;

class RetryFailedProductPush
{
    /**
     * @var WondersoftApiInterface
     */
    protected $wondersoftApi;

    /**
     * @var Logger
     */
    protected $logger;

    /**
     * @var Helper
     */
    protected $helper;

    /**
     * @var CollectionFactory
     */
    protected $productCollectionFactory;

    /**
     * Constructor
     *
     * @param WondersoftApiInterface $wondersoftApi
     * @param Logger $logger
     * @param Helper $helper
     * @param CollectionFactory $productCollectionFactory
     */
    public function __construct(
        WondersoftApiInterface $wondersoftApi,
        Logger                 $logger,
        Helper                 $helper,
        CollectionFactory      $productCollectionFactory
    ) {
        $this->wondersoftApi = $wondersoftApi;
        $this->logger = $logger;
        $this->helper = $helper;
        $this->productCollectionFactory = $productCollectionFactory;
    }

    /**
     * Execute cron job
     *
     * @return void
     */
    public function execute()
    {
        if (!$this->helper->isProductPushEnabled() || !$this->helper->isProductRetryEnabled()) {
            return;
        }

        $this->logger->info('Running retry for failed product pushes');

        // In a real implementation, you would retrieve products from a queue or database table
        // This is a simplified example that just pushes some recent products
        $collection = $this->productCollectionFactory->create();
        $collection->addAttributeToSelect('*')
            ->addAttributeToFilter('updated_at', ['gteq' => date('Y-m-d H:i:s', strtotime('-1 day'))])
            ->setPageSize(10)
            ->setCurPage(1);

        foreach ($collection as $product) {
            try {
                $this->logger->info('Retrying product push for: ' . $product->getSku());
                $result = $this->wondersoftApi->pushProduct($product);

                if (!$result) {
                    $this->logger->error('Failed to retry product push: ' . $product->getSku());
                }
            } catch (Exception $e) {
                $this->logger->critical('Exception during retry product push: ' . $e->getMessage());
            }
        }
    }
}
