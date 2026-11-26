<?php

declare(strict_types=1);

namespace Freento\Mcp\Model\ResourceModel\EntityTool;

use Freento\Mcp\Model\EntityTool\ConditionApplier;
use Freento\Mcp\Model\EntityTool\ListResultFactory;
use Freento\Mcp\Model\EntityTool\Schema;
use Freento\Mcp\Model\Helper\DateTimeHelper;
use Magento\Framework\App\ResourceConnection;
use Magento\Framework\DB\Select;

class CatalogPriceRuleResource extends AbstractResource
{
    public const FIELD_WEBSITE_ID = 'website_id';
    public const FIELD_CUSTOMER_GROUP_ID = 'customer_group_id';
    public const FIELD_WEBSITES = 'websites';
    public const FIELD_CUSTOMER_GROUPS = 'customer_groups';

    /**
     * Relation filters: filter field => [relation table, relation column]
     */
    private const RELATION_FILTERS = [
        self::FIELD_WEBSITE_ID => ['catalogrule_website', 'website_id'],
        self::FIELD_CUSTOMER_GROUP_ID => ['catalogrule_customer_group', 'customer_group_id'],
    ];

    /**
     * Negated operators mapped to their positive form
     */
    private const NEGATED_OPERATORS = [
        'neq' => 'eq',
        'nin' => 'in',
        'not_in' => 'in',
    ];

    /**
     * @param ResourceConnection $resourceConnection
     * @param ConditionApplier $conditionApplier
     * @param ListResultFactory $listResultFactory
     * @param DateTimeHelper $dateTimeHelper
     * @param LinkField $linkField
     */
    public function __construct(
        ResourceConnection $resourceConnection,
        ConditionApplier $conditionApplier,
        ListResultFactory $listResultFactory,
        DateTimeHelper $dateTimeHelper,
        private readonly LinkField $linkField
    ) {
        parent::__construct($resourceConnection, $conditionApplier, $listResultFactory, $dateTimeHelper);
    }

    /**
     * Apply schema filters plus website and customer group filters via relation tables
     *
     * @param Select $select
     * @param Schema $schema
     * @param array $arguments
     * @return string[]
     * @throws \Exception
     */
    protected function applyFilters(Select $select, Schema $schema, array $arguments): array
    {
        $appliedFilters = parent::applyFilters($select, $schema, $arguments);

        foreach (self::RELATION_FILTERS as $name => [$table, $column]) {
            if (!isset($arguments[$name]) || $arguments[$name] === '') {
                continue;
            }

            $value = $arguments[$name];
            [$includeCondition, $excludeCondition] = $this->splitRelationCondition($value);

            $isIncludeApplied = $includeCondition !== null
                && $this->applyRelationCondition($select, $table, $column, $includeCondition, false);
            $isExcludeApplied = $excludeCondition !== null
                && $this->applyRelationCondition($select, $table, $column, $excludeCondition, true);

            if ($isIncludeApplied || $isExcludeApplied) {
                $appliedFilters[] = $name . ': ' . (is_array($value) ? json_encode($value) : $value);
            }
        }

        return $appliedFilters;
    }

    /**
     * Split relation condition into positive and negated parts
     *
     * Negated operators are converted to their positive form so that they can be applied
     * as "rule has no such relation" instead of "rule has any other relation".
     *
     * @param mixed $condition Condition value or array with operators
     * @return array{0: mixed, 1: array|null} [include condition or null, exclude condition or null]
     */
    private function splitRelationCondition(mixed $condition): array
    {
        if (is_string($condition)) {
            $decoded = json_decode($condition, true);
            if (is_array($decoded)) {
                $condition = $decoded;
            }
        }

        if (!is_array($condition)) {
            return [$condition, null];
        }

        $include = [];
        $exclude = [];
        foreach ($condition as $operator => $operatorValue) {
            $operator = strtolower((string)$operator);
            if (isset(self::NEGATED_OPERATORS[$operator])) {
                $exclude[self::NEGATED_OPERATORS[$operator]] = $operatorValue;
                continue;
            }
            $include[$operator] = $operatorValue;
        }

        return [$include ?: null, $exclude ?: null];
    }

    /**
     * Restrict rules to those that have (or do not have) a matching relation row
     *
     * @param Select $select
     * @param string $table Relation table
     * @param string $column Relation column
     * @param mixed $condition Positive condition value or array with operators
     * @param bool $isExclude Whether to exclude rules with matching relation
     * @return bool Whether the condition was applied
     * @throws \Exception
     */
    private function applyRelationCondition(
        Select $select,
        string $table,
        string $column,
        mixed $condition,
        bool $isExclude
    ): bool {
        $sqlCondition = $this->conditionApplier->buildCondition("relation.{$column}", $condition, 'integer');
        if ($sqlCondition === null) {
            return false;
        }

        $linkField = $this->linkField->forCatalogRule();
        $subSelect = $this->resourceConnection->getConnection()->select()
            ->from(['relation' => $this->resourceConnection->getTableName($table)], [$linkField])
            ->where($sqlCondition);
        $operator = $isExclude ? 'NOT IN' : 'IN';
        $select->where("main_table.{$linkField} {$operator} ?", $subSelect);

        return true;
    }

    /**
     * Fetch rows and attach assigned websites and customer groups
     *
     * @param Select $select
     * @param Schema $schema
     * @param array $arguments
     * @return array
     * @throws \Exception
     */
    protected function fetchAll(Select $select, Schema $schema, array $arguments): array
    {
        $rows = parent::fetchAll($select, $schema, $arguments);
        if (empty($rows) || !isset($rows[0]['rule_id'])) {
            return $rows;
        }

        $ruleIds = array_map('intval', array_column($rows, 'rule_id'));
        $websites = $this->getRuleRelations(
            $ruleIds,
            'catalogrule_website',
            'website_id',
            'store_website',
            'website_id',
            'code'
        );
        $customerGroups = $this->getRuleRelations(
            $ruleIds,
            'catalogrule_customer_group',
            'customer_group_id',
            'customer_group',
            'customer_group_id',
            'customer_group_code'
        );

        foreach ($rows as &$row) {
            $ruleId = (int)$row['rule_id'];
            $row[self::FIELD_WEBSITES] = implode(', ', $websites[$ruleId] ?? []);
            $row[self::FIELD_CUSTOMER_GROUPS] = implode(', ', $customerGroups[$ruleId] ?? []);
        }

        return $rows;
    }

    /**
     * Load related entity labels for given rules in a single query
     *
     * @param int[] $ruleIds
     * @param string $relationTable Rule relation table (e.g., catalogrule_website)
     * @param string $relationColumn Related entity ID column in relation table
     * @param string $entityTable Related entity table (e.g., store_website)
     * @param string $entityIdColumn Related entity ID column in entity table
     * @param string $entityLabelColumn Related entity label column
     * @return array<int, string[]> Rule ID => list of "label (id)"
     * @throws \Exception
     */
    private function getRuleRelations(
        array $ruleIds,
        string $relationTable,
        string $relationColumn,
        string $entityTable,
        string $entityIdColumn,
        string $entityLabelColumn
    ): array {
        $connection = $this->resourceConnection->getConnection();
        $linkField = $this->linkField->forCatalogRule();

        $select = $connection->select()
            ->from(['rule' => $this->resourceConnection->getTableName('catalogrule')], ['rule_id'])
            ->join(
                ['relation' => $this->resourceConnection->getTableName($relationTable)],
                "relation.{$linkField} = rule.{$linkField}",
                ['entity_id' => $relationColumn]
            )
            ->joinLeft(
                ['entity' => $this->resourceConnection->getTableName($entityTable)],
                "entity.{$entityIdColumn} = relation.{$relationColumn}",
                ['label' => $entityLabelColumn]
            )
            ->where('rule.rule_id IN (?)', $ruleIds)
            ->distinct()
            ->order(['rule.rule_id', "relation.{$relationColumn}"]);

        $result = [];
        foreach ($connection->fetchAll($select) as $relation) {
            $label = $relation['label'] !== null
                ? "{$relation['label']} ({$relation['entity_id']})"
                : (string)$relation['entity_id'];
            $result[(int)$relation['rule_id']][] = $label;
        }

        return $result;
    }
}
