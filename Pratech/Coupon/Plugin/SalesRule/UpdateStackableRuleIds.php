<?php
/**
 * Pratech_Coupon
 *
 * PHP version 8.x
 *
 * @category  PHP
 * @package   Pratech\Coupon
 * @author    Puneet Mittal <puneet.mittal@pratechbrands.com>
 * @copyright 2025 Copyright (c) Pratech Brands Private Limited
 * @link      https://pratechbrands.com/
 **/

namespace Pratech\Coupon\Plugin\SalesRule;

use Exception;
use Magento\Framework\App\ResourceConnection;
use Magento\SalesRule\Model\ResourceModel\Rule as RuleResource;
use Magento\SalesRule\Model\ResourceModel\Rule\CollectionFactory;
use Magento\SalesRule\Model\Rule;
use Psr\Log\LoggerInterface;

class UpdateStackableRuleIds
{
    private const TABLE_NAME = 'salesrule';
    private ?string $originalStackableRuleIds = null;

    /**
     * @param CollectionFactory $ruleCollectionFactory
     * @param LoggerInterface $logger
     * @param ResourceConnection $resourceConnection
     */
    public function __construct(
        private CollectionFactory  $ruleCollectionFactory,
        private LoggerInterface    $logger,
        private ResourceConnection $resourceConnection
    ) {
    }

    /**
     * Before save plugin to store original data
     *
     * @param RuleResource $subject
     * @param Rule $rule
     */
    public function beforeSave(RuleResource $subject, Rule $rule): void
    {
        $this->originalStackableRuleIds = $rule->getOrigData('stackable_rule_ids');
    }

    /**
     * After save plugin
     *
     * @param RuleResource $subject
     * @param RuleResource $result
     * @param Rule $rule
     * @return RuleResource
     */
    public function afterSave(RuleResource $subject, RuleResource $result, Rule $rule): RuleResource
    {
        try {
            $existingStackableRuleIds = $this->getExistingStackableRuleIds();
            $this->updateRelatedRulesStackableIds($rule, $existingStackableRuleIds);
        } catch (Exception $e) {
            $this->logError($e, $rule->getId());
        }
        return $result;
    }

    /**
     * Get existing stackable rule IDs
     *
     * @return array
     */
    private function getExistingStackableRuleIds(): array
    {
        return $this->originalStackableRuleIds
            ? array_filter(explode(',', $this->originalStackableRuleIds))
            : [];
    }

    /**
     * Update related rules based on the saved rule
     *
     * @param Rule $triggerRule
     * @param array $existingStackableRuleIds
     * @return void
     */
    private function updateRelatedRulesStackableIds(Rule $triggerRule, array $existingStackableRuleIds): void
    {
        $triggerRuleStackableRuleIds = $this->getStackableRuleIds($triggerRule);
        $removedRelatedRuleIds = array_diff($existingStackableRuleIds, $triggerRuleStackableRuleIds);

        $this->updateAddedRules($triggerRule, $triggerRuleStackableRuleIds);
        $this->updateRemovedRules($triggerRule, $removedRelatedRuleIds);
    }

    /**
     * Get stackable rule IDs from a rule
     *
     * @param Rule $rule
     * @return array
     */
    private function getStackableRuleIds(Rule $rule): array
    {
        return $rule->getStackableRuleIds()
            ? array_filter(explode(',', $rule->getStackableRuleIds()))
            : [];
    }

    /**
     * Update rules that were added to stackable rules
     *
     * @param Rule $triggerRule
     * @param array $stackableRuleIds
     * @return void
     */
    private function updateAddedRules(Rule $triggerRule, array $stackableRuleIds): void
    {
        if (empty($stackableRuleIds)) {
            return;
        }

        $relatedRules = $this->ruleCollectionFactory->create()
            ->addFieldToFilter('rule_id', ['in' => $stackableRuleIds]);

        foreach ($relatedRules as $relatedRule) {
            if ($relatedRule->getId() == $triggerRule->getId()) {
                continue;
            }

            $updatedIds = $this->getUpdatedStackableIds($triggerRule, $relatedRule);
            $this->updateRuleStackableIds($relatedRule->getId(), $updatedIds);
        }
    }

    /**
     * Get updated stackable IDs for a related rule
     *
     * @param Rule $triggerRule
     * @param Rule $relatedRule
     * @return array
     */
    private function getUpdatedStackableIds(Rule $triggerRule, Rule $relatedRule): array
    {
        $updatedIds = array_unique(
            array_merge(
                [$triggerRule->getId()],
                $this->getStackableRuleIds($relatedRule)
            )
        );

        return array_diff($updatedIds, [$relatedRule->getId()]);
    }

    /**
     * Update rule's stackable IDs in database
     *
     * @param int $ruleId
     * @param array $stackableIds
     * @return void
     */
    private function updateRuleStackableIds(int $ruleId, array $stackableIds): void
    {
        if (empty($stackableIds)) {
            return;
        }

        $connection = $this->resourceConnection->getConnection();
        $tableName = $this->resourceConnection->getTableName(self::TABLE_NAME);

        $connection->update(
            $tableName,
            ['stackable_rule_ids' => implode(',', $stackableIds)],
            ['rule_id = ?' => $ruleId]
        );
    }

    /**
     * Update rules that were removed from stackable rules
     *
     * @param Rule $triggerRule
     * @param array $removedRuleIds
     * @return void
     */
    private function updateRemovedRules(Rule $triggerRule, array $removedRuleIds): void
    {
        if (empty($removedRuleIds)) {
            return;
        }

        $removedRules = $this->ruleCollectionFactory->create()
            ->addFieldToFilter('rule_id', ['in' => $removedRuleIds]);

        foreach ($removedRules as $removedRule) {
            $updatedIds = array_diff(
                $this->getStackableRuleIds($removedRule),
                [$triggerRule->getId()]
            );
            $this->updateRuleStackableIds($removedRule->getId(), $updatedIds);
        }
    }

    /**
     * Log error message
     *
     * @param Exception $exception
     * @param int $ruleId
     * @return void
     */
    private function logError(Exception $exception, int $ruleId): void
    {
        $this->logger->error(
            'Error updating related sales rules: ' . $exception->getMessage(),
            [
                'rule_id' => $ruleId,
                'trace' => $exception->getTraceAsString()
            ]
        );
    }
}
