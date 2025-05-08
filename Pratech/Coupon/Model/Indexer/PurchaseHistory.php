<?php
/**
 * Pratech_Coupon
 *
 * @category  XML
 * @package   Pratech\Coupon
 * @author    Vivek Kumar <vivek.kumar@pratechbrands.com>
 * @copyright 2022 Copyright (c) Pratech Brands Private Limited
 * @link      https://pratechbrands.com/
 */

namespace Pratech\Coupon\Model\Indexer;

use Pratech\Coupon\Model\Indexer\PurchaseHistory\Action;
use Pratech\Coupon\Model\Indexer\PurchaseHistory\IndexerHandlerFactory;
use Magento\Framework\Indexer\ActionInterface as IndexerActionInterface;
use Magento\Framework\Indexer\SaveHandler\IndexerInterface;
use Magento\Framework\Mview\ActionInterface as MviewActionInterface;

class PurchaseHistory implements IndexerActionInterface, MviewActionInterface
{
    public const INDEXER_ID = 'pratech_purchase_history_index';

    /**
     * Purchage History Constructor
     *
     * @param IndexerHandlerFactory $indexerHandlerFactory
     * @param Action                $indexAction
     * @param array                 $data
     */
    public function __construct(
        private IndexerHandlerFactory $indexerHandlerFactory,
        private Action $indexAction,
        private array $data = ['indexer_id' => self::INDEXER_ID]
    ) {
        $this->indexerHandlerFactory = $indexerHandlerFactory;
        $this->indexAction = $indexAction;
        $this->data = $data;
    }

    /**
     * @inheritDoc
     */
    public function execute($ids)
    {
        /** @var IndexerInterface $indexHandler */
        $indexHandler = $this->indexerHandlerFactory->create(
            [
            'data' => $this->data
            ]
        );
        if (!count($ids)) {
            $indexHandler->cleanIndex([]);
        } else {
            $ids = $this->indexAction->convertOrderIdsToCustomerIds($ids);
            $indexHandler->deleteIndex([], new \ArrayIterator($ids));
        }

        $indexHandler->saveIndex([], $this->indexAction->getIndexInsertIterator($ids));
    }

    /**
     * @inheritDoc
     */
    public function executeFull()
    {
        $this->execute([]);
    }

    /**
     * @inheritDoc
     */
    public function executeList(array $ids)
    {
        $this->execute($ids);
    }

    /**
     * @inheritDoc
     */
    public function executeRow($id)
    {
        $this->execute([$id]);
    }
}
