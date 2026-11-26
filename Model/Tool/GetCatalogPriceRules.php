<?php

declare(strict_types=1);

namespace Freento\Mcp\Model\Tool;

use Freento\Mcp\Model\EntityTool\AbstractTool;
use Freento\Mcp\Model\EntityTool\Field;
use Freento\Mcp\Model\EntityTool\Schema;
use Freento\Mcp\Model\EntityTool\SchemaFactory;
use Freento\Mcp\Model\Helper\DateTimeHelper;
use Freento\Mcp\Model\Helper\RuleConditionFormatter;
use Freento\Mcp\Model\Helper\StringHelper;
use Freento\Mcp\Model\ResourceModel\EntityTool\AbstractResource;
use Freento\Mcp\Model\ResourceModel\EntityTool\CatalogPriceRuleResource;
use Freento\Mcp\Model\ToolResultFactory;

class GetCatalogPriceRules extends AbstractTool
{
    private const ACTION_LABELS = [
        'by_percent' => 'Apply as percentage of original',
        'by_fixed' => 'Apply as fixed amount',
        'to_percent' => 'Adjust final price to this percentage',
        'to_fixed' => 'Adjust final price to discount value',
    ];

    /**
     * @param CatalogPriceRuleResource $catalogPriceRuleResource
     * @param SchemaFactory $schemaFactory
     * @param ToolResultFactory $resultFactory
     * @param StringHelper $stringHelper
     * @param DateTimeHelper $dateTimeHelper
     * @param RuleConditionFormatter $conditionFormatter
     */
    public function __construct(
        private readonly CatalogPriceRuleResource $catalogPriceRuleResource,
        private readonly SchemaFactory $schemaFactory,
        ToolResultFactory $resultFactory,
        StringHelper $stringHelper,
        DateTimeHelper $dateTimeHelper,
        private readonly RuleConditionFormatter $conditionFormatter
    ) {
        parent::__construct($resultFactory, $stringHelper, $dateTimeHelper);
    }

    /**
     * @inheritDoc
     */
    protected function getResource(): AbstractResource
    {
        return $this->catalogPriceRuleResource;
    }

    /**
     * @inheritDoc
     */
    public function getName(): string
    {
        return 'get_catalog_price_rules';
    }

    /**
     * @inheritDoc
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    protected function buildSchema(): Schema
    {
        return $this->schemaFactory->create(
            entity: 'catalog_price_rule',
            table: 'catalogrule',
            fields: [
                new Field(
                    name: 'rule_id',
                    type: 'integer',
                    description: 'Rule ID'
                ),
                new Field(
                    name: 'name',
                    type: 'string',
                    description: 'Rule name'
                ),
                new Field(
                    name: 'description',
                    type: 'string',
                    sortable: false
                ),
                new Field(
                    name: 'is_active',
                    type: 'integer',
                    allowGroupBy: true,
                    description: 'Active status (1 = active, 0 = inactive)'
                ),
                new Field(
                    name: CatalogPriceRuleResource::FIELD_WEBSITE_ID,
                    type: 'integer',
                    column: false,
                    sortable: false,
                    description: 'Rules assigned to website ID'
                ),
                new Field(
                    name: CatalogPriceRuleResource::FIELD_CUSTOMER_GROUP_ID,
                    type: 'integer',
                    column: false,
                    sortable: false,
                    description: 'Rules assigned to customer group ID (0 = NOT LOGGED IN)'
                ),
                new Field(
                    name: CatalogPriceRuleResource::FIELD_WEBSITES,
                    type: 'string',
                    column: false,
                    filter: false,
                    sortable: false,
                    description: 'Assigned websites'
                ),
                new Field(
                    name: CatalogPriceRuleResource::FIELD_CUSTOMER_GROUPS,
                    type: 'string',
                    column: false,
                    filter: false,
                    sortable: false,
                    description: 'Assigned customer groups'
                ),
                new Field(
                    name: 'from_date',
                    type: 'calendar_date',
                    description: 'Start date (YYYY-MM-DD, empty = no start limit)'
                ),
                new Field(
                    name: 'to_date',
                    type: 'calendar_date',
                    description: 'End date (YYYY-MM-DD, empty = no end limit)'
                ),
                new Field(
                    name: 'simple_action',
                    type: 'string',
                    allowGroupBy: true,
                    description: 'Action type (by_percent, by_fixed, to_percent, to_fixed)'
                ),
                new Field(
                    name: 'discount_amount',
                    type: 'numeric',
                    allowAggregate: true,
                    description: 'Discount amount or percentage (depends on simple_action)'
                ),
                new Field(
                    name: 'stop_rules_processing',
                    type: 'integer',
                    sortable: false,
                    description: 'Discard subsequent rules (1 = yes, 0 = no)'
                ),
                new Field(
                    name: 'sort_order',
                    type: 'integer',
                    description: 'Priority (lower value is applied first)'
                ),
                new Field(
                    name: 'conditions',
                    type: 'string',
                    column: 'main_table.conditions_serialized',
                    filter: false,
                    sortable: false,
                    description: 'Product conditions the rule applies to (e.g. category_ids in 27,18)'
                ),
            ],
            defaultLimit: 50,
            maxLimit: 200
        );
    }

    /**
     * @inheritDoc
     */
    protected function getDescriptionLines(): array
    {
        return [
            'Retrieve catalog price rules (product discounts applied without coupon)',
            'Check which rules apply to a website or customer group',
            'Review discount conditions, amounts and validity dates',
        ];
    }

    /**
     * @inheritDoc
     */
    protected function getExamplePrompts(): array
    {
        return [
            'Show me active catalog price rules',
            'Which catalog rules apply to the Wholesale customer group?',
            'Which catalog promotions expire this month?',
            'List catalog rules with discount > 20%',
        ];
    }

    /**
     * @inheritDoc
     *
     * Format serialized condition JSON as human-readable text
     */
    protected function formatValue(mixed $value, ?Field $field): string
    {
        if ($field !== null && $field->getName() === 'conditions') {
            return $this->conditionFormatter->formatSerialized($value);
        }

        return parent::formatValue($value, $field);
    }

    /**
     * @inheritDoc
     */
    protected function transformRows(array $rows): array
    {
        foreach ($rows as &$row) {
            if (isset($row['is_active'])) {
                $row['is_active'] = $row['is_active'] ? 'Active' : 'Inactive';
            }
            if (isset($row['stop_rules_processing'])) {
                $row['stop_rules_processing'] = $row['stop_rules_processing'] ? 'Yes' : 'No';
            }
            if (isset($row['simple_action'])) {
                $row['simple_action'] = self::ACTION_LABELS[$row['simple_action']] ?? $row['simple_action'];
            }
        }

        return $rows;
    }
}
