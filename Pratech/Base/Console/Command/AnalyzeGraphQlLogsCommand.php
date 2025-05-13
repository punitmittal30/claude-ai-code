<?php
/**
 * A CLI script to analyze GraphQL performance logs
 *
 * Usage:
 * php bin/magento pratech:analyze:graphql-logs [--date=YYYY-MM-DD] [--threshold=100] [--top=10]
 */

namespace Pratech\Base\Console\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Helper\Table;

class AnalyzeGraphQlLogsCommand extends Command
{
    protected function configure()
    {
        $this->setName('pratech:analyze:graphql-logs')
            ->setDescription('Analyze GraphQL performance logs')
            ->addOption(
                'date',
                null,
                InputOption::VALUE_OPTIONAL,
                'Date to analyze (YYYY-MM-DD)',
                date('Y-m-d')
            )
            ->addOption(
                'threshold',
                null,
                InputOption::VALUE_OPTIONAL,
                'Minimum execution time to report (ms)',
                100
            )
            ->addOption(
                'top',
                null,
                InputOption::VALUE_OPTIONAL,
                'Number of slowest resolvers to show',
                10
            );
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $date = $input->getOption('date');
        $threshold = (int)$input->getOption('threshold');
        $topCount = (int)$input->getOption('top');

        $logFile = BP . '/var/log/graphql_performance.log';

        if (!file_exists($logFile)) {
            $output->writeln("<error>Log file not found: $logFile</error>");
            return 1;
        }

        $output->writeln("<info>Analyzing GraphQL performance for $date (threshold: {$threshold}ms)</info>");

        // Parse log file
        $resolvers = [];
        $pathTimes = [];
        $totalQueries = 0;
        $datePrefixToMatch = "[$date";

        $handle = fopen($logFile, 'r');
        while (($line = fgets($handle)) !== false) {
            if (strpos($line, $datePrefixToMatch) === false) {
                continue;
            }

            if (preg_match('/Resolver: (.*)\.(.*) took ([\d\.]+) ms \(Resolver: (.*), Product ID: (.*)\)/', $line, $matches)) {
                $path = $matches[1];
                $field = $matches[2];
                $time = (float)$matches[3];
                $resolver = $matches[4];
                $productId = $matches[5];

                $key = "$resolver:$field";

                if (!isset($resolvers[$key])) {
                    $resolvers[$key] = [
                        'resolver' => $resolver,
                        'field' => $field,
                        'count' => 0,
                        'total_time' => 0,
                        'max_time' => 0,
                        'min_time' => PHP_FLOAT_MAX,
                    ];
                }

                $resolvers[$key]['count']++;
                $resolvers[$key]['total_time'] += $time;
                $resolvers[$key]['max_time'] = max($resolvers[$key]['max_time'], $time);
                $resolvers[$key]['min_time'] = min($resolvers[$key]['min_time'], $time);

                // Track path timing
                if (!isset($pathTimes[$path])) {
                    $pathTimes[$path] = 0;
                }
                $pathTimes[$path] += $time;

                $totalQueries++;
            }
        }
        fclose($handle);

        if (empty($resolvers)) {
            $output->writeln("<comment>No data found for $date</comment>");
            return 0;
        }

        // Calculate average times
        foreach ($resolvers as &$data) {
            $data['avg_time'] = $data['total_time'] / $data['count'];
        }

        // Sort by total time
        usort($resolvers, function ($a, $b) {
            return $b['total_time'] <=> $a['total_time'];
        });

        // Show top slowest resolvers
        $output->writeln("\n<info>Top {$topCount} Slowest Resolvers (by total time):</info>");

        $table = new Table($output);
        $table->setHeaders(['Resolver', 'Field', 'Count', 'Total (ms)', 'Avg (ms)', 'Max (ms)']);

        $count = 0;
        foreach ($resolvers as $data) {
            if (++$count > $topCount) {
                break;
            }

            $shortResolver = substr($data['resolver'], strrpos($data['resolver'], '\\') + 1);

            $table->addRow([
                $shortResolver,
                $data['field'],
                $data['count'],
                number_format($data['total_time'], 1),
                number_format($data['avg_time'], 1),
                number_format($data['max_time'], 1)
            ]);
        }

        $table->render();

        // Show paths by total time
        arsort($pathTimes);
        $pathTimes = array_slice($pathTimes, 0, $topCount);

        $output->writeln("\n<info>Top {$topCount} Slowest Query Paths:</info>");

        $table = new Table($output);
        $table->setHeaders(['Path', 'Total Time (ms)']);

        foreach ($pathTimes as $path => $time) {
            $table->addRow([$path, number_format($time, 1)]);
        }

        $table->render();

        $output->writeln("\n<info>Total queries analyzed: $totalQueries</info>");

        return 0;
    }
}
