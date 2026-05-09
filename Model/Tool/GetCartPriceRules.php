<?php

declare(strict_types=1);

namespace Freento\Mcp\Model\Tool;

use Freento\Mcp\Model\EntityTool\AbstractTool;
use Freento\Mcp\Model\EntityTool\Field;
use Freento\Mcp\Model\EntityTool\Schema;
use Freento\Mcp\Model\Helper\DateTimeHelper;
use Freento\Mcp\Model\Helper\RuleConditionFormatter;
use Freento\Mcp\Model\Helper\StringHelper;
use Freento\Mcp\Model\ResourceModel\EntityTool\AbstractResource;
use Freento\Mcp\Model\ResourceModel\EntityTool\CartPriceRuleResource;
use Freento\Mcp\Model\ToolResultFactory;
use Freento\Mcp\Model\EntityTool\SchemaFactory;
use Psr\Log\LoggerInterface;

class GetCartPriceRules extends AbstractTool
{
    /**
     * @param CartPriceRuleResource $cartPriceRuleResource
     * @param SchemaFactory $schemaFactory
     * @param ToolResultFactory $resultFactory
     * @param StringHelper $stringHelper
     * @param DateTimeHelper $dateTimeHelper
     * @param RuleConditionFormatter $conditionFormatter
     * @param LoggerInterface $logger
     */
    public function __construct(
        private readonly CartPriceRuleResource $cartPriceRuleResource,
        private readonly SchemaFactory $schemaFactory,
        ToolResultFactory $resultFactory,
        StringHelper $stringHelper,
        DateTimeHelper $dateTimeHelper,
        private readonly RuleConditionFormatter $conditionFormatter,
        private readonly LoggerInterface $logger
    ) {
        parent::__construct($resultFactory, $stringHelper, $dateTimeHelper);
    }

    /**
     * @inheritDoc
     */
    protected function getResource(): AbstractResource
    {
        return $this->cartPriceRuleResource;
    }

    /**
     * @inheritDoc
     */
    public function getName(): string
    {
        return 'get_cart_price_rules';
    }

    /**
     * @inheritdoc
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    protected function buildSchema(): Schema
    {
        return $this->schemaFactory->create(
            entity: 'cart_price_rule',
            table: 'salesrule',
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
                    name: 'from_date',
                    type: 'date',
                    description: 'Start date'
                ),
                new Field(
                    name: 'to_date',
                    type: 'date',
                    description: 'End date'
                ),
                new Field(
                    name: 'uses_per_customer',
                    type: 'integer',
                    sortable: false,
                    description: 'Uses per customer limit (0 = unlimited)'
                ),
                new Field(
                    name: 'uses_per_coupon',
                    type: 'integer',
                    sortable: false,
                    description: 'Uses per coupon limit (0 = unlimited)'
                ),
                new Field(
                    name: 'times_used',
                    type: 'integer',
                    allowAggregate: true,
                    description: 'Total times used'
                ),
                new Field(
                    name: 'coupon_type',
                    type: 'integer',
                    allowGroupBy: true,
                    description: 'Coupon type (1 = no coupon, 2 = specific coupon, 3 = auto-generated)'
                ),
                new Field(
                    name: 'simple_action',
                    type: 'string',
                    allowGroupBy: true,
                    description: 'Action type (by_percent, by_fixed, cart_fixed, buy_x_get_y)'
                ),
                new Field(
                    name: 'discount_amount',
                    type: 'currency',
                    allowAggregate: true,
                    description: 'Discount amount or percentage'
                ),
                new Field(
                    name: 'discount_qty',
                    type: 'numeric',
                    sortable: false,
                    description: 'Max qty discount applied to'
                ),
                new Field(
                    name: 'discount_step',
                    type: 'integer',
                    sortable: false,
                    description: 'Discount step (buy X)'
                ),
                new Field(
                    name: 'apply_to_shipping',
                    type: 'integer',
                    sortable: false,
                    description: 'Apply to shipping (1 = yes)'
                ),
                new Field(
                    name: 'stop_rules_processing',
                    type: 'integer',
                    sortable: false,
                    description: 'Stop further rules processing'
                ),
                new Field(
                    name: 'sort_order',
                    type: 'integer',
                    description: 'Priority'
                ),
                new Field(
                    name: 'conditions',
                    type: 'string',
                    column: 'main_table.conditions_serialized',
                    filter: false,
                    sortable: false,
                    description: 'Rule trigger conditions (e.g. subtotal >= 200)'
                ),
                new Field(
                    name: 'actions',
                    type: 'string',
                    column: 'main_table.actions_serialized',
                    filter: false,
                    sortable: false,
                    description: 'Product conditions for discount eligibility'
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
            'Retrieve shopping cart price rules (discounts)',
            'Check active promotions',
            'Analyze discount usage',
        ];
    }

    /**
     * @inheritDoc
     */
    protected function getExamplePrompts(): array
    {
        return [
            'Show me active cart price rules',
            'Which promotions expire this week?',
            'List rules with discount > 20%',
            'Find most used discount rules',
        ];
    }

    /**
     * @inheritDoc
     *
     * Format serialized condition/action JSON as human-readable text
     */
    protected function formatValue(mixed $value, ?Field $field): string
    {
        if ($field !== null && in_array($field->getName(), ['conditions', 'actions'])) {
            return $this->formatCondition($value);
        }

        return parent::formatValue($value, $field);
    }

    /**
     * @inheritDoc
     */
    protected function transformRows(array $rows): array
    {
        $couponTypes = [
            1 => 'No Coupon',
            2 => 'Specific Coupon',
            3 => 'Auto-generated',
        ];

        $actionLabels = [
            'by_percent' => 'Percent of product price',
            'by_fixed' => 'Fixed amount per product',
            'cart_fixed' => 'Fixed amount for cart',
            'buy_x_get_y' => 'Buy X get Y free',
        ];

        foreach ($rows as &$row) {
            if (isset($row['is_active'])) {
                $row['is_active'] = $row['is_active'] ? 'Active' : 'Inactive';
            }
            if (isset($row['coupon_type'])) {
                $row['coupon_type'] = $couponTypes[(int)$row['coupon_type']] ?? $row['coupon_type'];
            }
            if (isset($row['simple_action'])) {
                $row['simple_action'] = $actionLabels[$row['simple_action']] ?? $row['simple_action'];
            }
        }

        return $rows;
    }

    /**
     * Decode serialized condition JSON and format as human-readable string
     *
     * @param mixed $serialized JSON-encoded condition tree
     * @return string Human-readable condition string
     */
    private function formatCondition(mixed $serialized): string
    {
        if (!$serialized || !is_string($serialized)) {
            return '(none)';
        }

        try {
            $tree = json_decode($serialized, true, 512, JSON_THROW_ON_ERROR);
        } catch (\JsonException $e) {
            $this->logger->error('Failed to decode rule condition JSON', ['exception' => $e]);
            return '(parse error)';
        }

        return $this->conditionFormatter->format($tree);
    }
}
