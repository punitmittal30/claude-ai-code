<?php
/**
 * Pratech_AmastyFeedUpdate
 *
 * PHP version 8.x
 *
 * @category  PHP
 * @package   Pratech\AmastyFeedUpdate
 * @author    Vivek Kumar <vivek.kumar@pratechbrands.com>
 * @copyright 2022 Copyright (c) Pratech Brands Private Limited
 * @link      https://pratechbrands.com/
 **/

namespace Pratech\AmastyFeedUpdate\Plugin\Model;

use Amasty\Feed\Api\Data\FeedInterface;
use Amasty\Feed\Model\FeedRepository;
use Amasty\Feed\Model\Config;
use Amasty\Feed\Model\Config\Source\FeedStatus;

/**
 * Feed Export Plugin
 */
class FeedExport extends \Amasty\Feed\Model\FeedExport
{
    /**
     * Feed Export Constructor
     *
     * @param \Amasty\Feed\Model\Export\ProductFactory          $productExportFactory
     * @param \Amasty\Feed\Model\Export\Adapter\AdapterProvider $adapterProvider
     * @param FeedRepository                                    $feedRepository
     * @param \Magento\Framework\Event\ManagerInterface         $eventManager
     * @param \Amasty\Feed\Model\Filesystem\FeedOutput          $feedOutput
     * @param Config                                            $config
     * @param \Psr\Log\LoggerInterface                          $logger
     * @param \Magento\Framework\Filesystem                     $filesystem
     * @param boolean                                           $multiProcessMode
     */
    public function __construct(
        private \Amasty\Feed\Model\Export\ProductFactory $productExportFactory,
        private \Amasty\Feed\Model\Export\Adapter\AdapterProvider $adapterProvider,
        private FeedRepository $feedRepository,
        private \Magento\Framework\Event\ManagerInterface $eventManager,
        private \Amasty\Feed\Model\Filesystem\FeedOutput $feedOutput,
        private Config $config,
        private \Psr\Log\LoggerInterface $logger,
        private \Magento\Framework\Filesystem $filesystem,
        private bool $multiProcessMode = false
    ) {
        parent::__construct(
            $productExportFactory,
            $adapterProvider,
            $feedRepository,
            $eventManager,
            $feedOutput,
            $config,
            $logger,
            $filesystem,
            $multiProcessMode
        );
    }

    /**
     * @inheritDoc
     */
    public function export(FeedInterface $feed, $page, $productIds, $lastPage, $preview = false, $cronGenerated = false)
    {
        $fileName = $this->multiProcessMode
            ? $this->getChunkFileName($feed, $page)
            : $feed->getFilename();
        
        $result = $this->productExportFactory->create(['storeId' => $feed->getStoreId()])
            ->setPage($page)
            ->setWriter($this->getWriter($feed, $fileName, $this->multiProcessMode ? 0 : $page))
            ->setAttributes($this->getAttributes($feed))
            ->setParentAttributes($this->getAttributes($feed, true))
            ->setMatchingProductIds($productIds)
            ->setUtmParams($feed->getUtmParams())
            ->setStoreId($feed->getStoreId())
            ->setProductBaseUrl($feed->getProductBaseUrl())
            ->setFormatPriceCurrency($feed->getFormatPriceCurrency())
            ->setCurrencyShow($feed->getFormatPriceCurrencyShow())
            ->setFormatPriceDecimals($feed->getFormatPriceDecimals())
            ->setFormatPriceDecimalPoint($feed->getFormatPriceDecimalPoint())
            ->setFormatPriceThousandsSeparator($feed->getFormatPriceThousandsSeparator())
            ->export($lastPage);
       
        if ($preview) {
            $this->feedOutput->delete($feed);
            return $result;
        }

        $feed->setGeneratedAt($cronGenerated ?: date('Y-m-d H:i:s'));
        $feed->setProductsAmount($feed->getProductsAmount() + count($productIds));

        $status = $lastPage && !$this->multiProcessMode
            ? FeedStatus::READY
            : FeedStatus::PROCESSING;
        $feed->setStatus($status);
        $this->feedRepository->save($feed);
        if ($feed->getStatus() == FeedStatus::READY) {
            $this->feedOutput->get($feed);
            $this->eventManager->dispatch('amfeed_export_end', ['feed' => $feed]);
        }

        return $result;
    }
}
