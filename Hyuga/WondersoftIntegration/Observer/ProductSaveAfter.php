<?php
declare(strict_types=1);

namespace Hyuga\WondersoftIntegration\Observer;

use Exception;
use Hyuga\WondersoftIntegration\Helper\Config;
use Hyuga\WondersoftIntegration\Logger\Logger;
use Hyuga\WondersoftIntegration\Service\ProductSyncService;
use Magento\Catalog\Model\Product;
use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;

class ProductSaveAfter implements ObserverInterface
{
    /**
     * @var Config
     */
    private $config;

    /**
     * @var Logger
     */
    private $logger;

    /**
     * @var ProductSyncService
     */
    private $productSyncService;

    /**
     * ProductSaveAfter constructor.
     *
     * @param Config $config
     * @param Logger $logger
     * @param ProductSyncService $productSyncService
     */
    public function __construct(
        Config             $config,
        Logger             $logger,
        ProductSyncService $productSyncService
    )
    {
        $this->config = $config;
        $this->logger = $logger;
        $this->productSyncService = $productSyncService;
    }

    /**
     * Execute observer
     *
     * @param Observer $observer
     * @return void
     */
    public function execute(Observer $observer)
    {
        if (!$this->config->isEnabled()) {
            return;
        }

        /** @var Product $product */
        $product = $observer->getEvent()->getProduct();
        if (!$product || !$product->getId()) {
            return;
        }

        try {
            $this->logger->info('Product saved, syncing to eShopaid: ' . $product->getSku());
            $this->productSyncService->syncProduct($product);

            // Sync default price list
            $this->productSyncService->syncProductPriceList(
                $product,
                'PL1',
                'Default Price',
                (float)$product->getPrice()
            );
        } catch (Exception $e) {
            $this->logger->error('Error in product save observer: ' . $e->getMessage());
        }
    }
}
