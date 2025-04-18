<?php
/**
 * Hyuga_CacheManagement
 *
 * PHP version 8.x
 *
 * @category  PHP
 * @package   Hyuga\CacheManagement
 * @author    Puneet Mittal <puneet.mittal@pratechbrands.com>
 * @copyright 2025 Copyright (c) Pratech Brands Private Limited
 * @link      https://pratechbrands.com/
 **/

namespace Hyuga\CacheManagement\Console\Command;

use Exception;
use Hyuga\CacheManagement\Api\CacheServiceInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class FlushPincodeCacheCommand extends Command
{
    private const PINCODE_OPTION = 'pincode';

    /**
     * @param CacheServiceInterface $cacheService
     * @param string|null $name
     */
    public function __construct(
        private CacheServiceInterface $cacheService,
        string                        $name = null
    ) {
        parent::__construct($name);
    }

    /**
     * @inheritDoc
     */
    protected function configure()
    {
        $this->setName('hyuga:cache:flush-pincode')
            ->setDescription('Flush the pincode serviceability cache')
            ->addOption(
                self::PINCODE_OPTION,
                'p',
                InputOption::VALUE_OPTIONAL,
                'Specific pincode to flush (optional)'
            );

        parent::configure();
    }

    /**
     * @inheritDoc
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $pincode = $input->getOption(self::PINCODE_OPTION);

        try {
            if ($pincode) {
                // Flush specific pincode cache
                $result = $this->cacheService->cleanPincodeCache((int)$pincode);
                if ($result) {
                    $output->writeln("<info>Pincode {$pincode} cache has been flushed.</info>");
                } else {
                    $output->writeln("<error>Failed to flush pincode {$pincode} cache.</error>");
                    return Command::FAILURE;
                }
            } else {
                // Flush all pincode caches
                $result = $this->cacheService->cleanAllPincodeCaches();
                if ($result) {
                    $output->writeln("<info>All pincode caches have been flushed.</info>");
                } else {
                    $output->writeln("<error>Failed to flush all pincode caches.</error>");
                    return Command::FAILURE;
                }
            }

            return Command::SUCCESS;
        } catch (Exception $e) {
            $output->writeln("<error>Error: {$e->getMessage()}</error>");
            return Command::FAILURE;
        }
    }
}
