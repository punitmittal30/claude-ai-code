<?php
declare(strict_types=1);

namespace Hyuga\WondersoftIntegration\Console\Command;

use Exception;
use Hyuga\WondersoftIntegration\Helper\Config;
use Hyuga\WondersoftIntegration\Logger\Logger;
use Hyuga\WondersoftIntegration\Service\ProductSyncService;
use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Framework\Api\SearchCriteriaBuilder;
use Magento\Framework\Exception\LocalizedException;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class SyncProductCommand extends Command
{
    const SKU_OPTION = 'sku';
    const ALL_OPTION = 'all';

    // Success and failure constants for exit codes
    const SUCCESS = 0;
    const FAILURE = 1;

    /**
     * @var Config
     */
    private $config;

    /**
     * @var Logger
     */
    private $logger;

    /**
     * @var ProductRepositoryInterface
     */
    private $productRepository;

    /**
     * @var SearchCriteriaBuilder
     */
    private $searchCriteriaBuilder;

    /**
     * @var ProductSyncService
     */
    private $productSyncService;

    /**
     * SyncProductCommand constructor.
     *
     * @param Config $config
     * @param Logger $logger
     * @param ProductRepositoryInterface $productRepository
     * @param SearchCriteriaBuilder $searchCriteriaBuilder
     * @param ProductSyncService $productSyncService
     * @param string|null $name
     */
    public function __construct(
        Config                     $config,
        Logger                     $logger,
        ProductRepositoryInterface $productRepository,
        SearchCriteriaBuilder      $searchCriteriaBuilder,
        ProductSyncService         $productSyncService,
        string                     $name = null
    )
    {
        parent::__construct($name);
        $this->config = $config;
        $this->logger = $logger;
        $this->productRepository = $productRepository;
        $this->searchCriteriaBuilder = $searchCriteriaBuilder;
        $this->productSyncService = $productSyncService;
    }

    /**
     * Configure the command
     *
     * @return void
     */
    protected function configure()
    {
        $this->setName('wondersoft:sync:product')
            ->setDescription('Sync products to Wondersoft eShopaid')
            ->addOption(
                self::SKU_OPTION,
                's',
                InputOption::VALUE_OPTIONAL,
                'SKU of the product to sync'
            )
            ->addOption(
                self::ALL_OPTION,
                'a',
                InputOption::VALUE_NONE,
                'Sync all products'
            );

        parent::configure();
    }

    /**
     * Execute the command
     *
     * @param InputInterface $input
     * @param OutputInterface $output
     * @return int
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        if (!$this->config->isEnabled()) {
            $output->writeln('<error>Wondersoft eShopaid integration is disabled</error>');
            return self::FAILURE;
        }

        try {
            $sku = $input->getOption(self::SKU_OPTION);
            $all = $input->getOption(self::ALL_OPTION);

            if ($sku) {
                // Sync a single product
                return $this->syncProductBySku($sku, $output);
            } elseif ($all) {
                // Sync all products
                return $this->syncAllProducts($output);
            } else {
                $output->writeln('<error>Please provide either --sku or --all option</error>');
                return self::FAILURE;
            }
        } catch (Exception $e) {
            $output->writeln('<error>' . $e->getMessage() . '</error>');
            $this->logger->error('Error in sync product command: ' . $e->getMessage());
            return self::FAILURE;
        }
    }

    /**
     * Sync a single product by SKU
     *
     * @param string $sku
     * @param OutputInterface $output
     * @return int
     */
    private function syncProductBySku(string $sku, OutputInterface $output): int
    {
        try {
            $output->writeln('<info>Syncing product with SKU: ' . $sku . '</info>');
            $product = $this->productRepository->get($sku);

            $result = $this->productSyncService->syncProduct($product);

            if ($result) {
                $output->writeln('<info>Product synced successfully</info>');

                // Sync default price list
                $priceResult = $this->productSyncService->syncProductPriceList(
                    $product,
                    'PL1',
                    'Default Price',
                    $product->getPrice()
                );

                if ($priceResult) {
                    $output->writeln('<info>Price list synced successfully</info>');
                } else {
                    $output->writeln('<error>Failed to sync price list</error>');
                }

                return self::SUCCESS;
            } else {
                $output->writeln('<error>Failed to sync product</error>');
                return self::FAILURE;
            }
        } catch (LocalizedException $e) {
            $output->writeln('<error>Product with SKU ' . $sku . ' not found: ' . $e->getMessage() . '</error>');
            return self::FAILURE;
        }
    }

    /**
     * Sync all products
     *
     * @param OutputInterface $output
     * @return int
     */
    private function syncAllProducts(OutputInterface $output): int
    {
        $output->writeln('<info>Syncing all products...</info>');

        $searchCriteria = $this->searchCriteriaBuilder->create();
        $products = $this->productRepository->getList($searchCriteria)->getItems();

        $success = 0;
        $failed = 0;

        foreach ($products as $product) {
            $output->write('Syncing product with SKU: ' . $product->getSku() . '... ');

            $result = $this->productSyncService->syncProduct($product);

            if ($result) {
                $output->writeln('<info>Success</info>');
                $success++;

                // Sync default price list
                $output->write('Syncing price list for SKU: ' . $product->getSku() . '... ');
                $priceResult = $this->productSyncService->syncProductPriceList(
                    $product,
                    'PL1',
                    'Default Price',
                    $product->getPrice()
                );

                if ($priceResult) {
                    $output->writeln('<info>Success</info>');
                } else {
                    $output->writeln('<error>Failed</error>');
                }
            } else {
                $output->writeln('<error>Failed</error>');
                $failed++;
            }
        }

        $output->writeln('<info>Sync completed. Success: ' . $success . ', Failed: ' . $failed . '</info>');

        return ($failed === 0) ? self::SUCCESS : self::FAILURE;
    }
}
