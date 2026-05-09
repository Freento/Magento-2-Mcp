<?php

declare(strict_types=1);

namespace Freento\Mcp\Model\Tool;

use Freento\Mcp\Model\EntityTool\AbstractTool;
use Freento\Mcp\Model\EntityTool\Field;
use Freento\Mcp\Model\EntityTool\Schema;
use Freento\Mcp\Model\Helper\DateTimeHelper;
use Freento\Mcp\Model\Helper\StringHelper;
use Freento\Mcp\Model\ResourceModel\EntityTool\AbstractResource;
use Freento\Mcp\Model\ResourceModel\EntityTool\CouponResource;
use Freento\Mcp\Model\ToolResultFactory;
use Freento\Mcp\Model\EntityTool\SchemaFactory;

class GetCoupons extends AbstractTool
{
    /**
     * @param CouponResource $couponResource
     * @param SchemaFactory $schemaFactory
     * @param ToolResultFactory $resultFactory
     * @param StringHelper $stringHelper
     * @param DateTimeHelper $dateTimeHelper
     */
    public function __construct(
        private readonly CouponResource $couponResource,
        private readonly SchemaFactory $schemaFactory,
        ToolResultFactory $resultFactory,
        StringHelper $stringHelper,
        DateTimeHelper $dateTimeHelper
    ) {
        parent::__construct($resultFactory, $stringHelper, $dateTimeHelper);
    }

    /**
     * @inheritDoc
     */
    protected function getResource(): AbstractResource
    {
        return $this->couponResource;
    }

    /**
     * @inheritDoc
     */
    protected function buildSchema(): Schema
    {
        return $this->schemaFactory->create(
            entity: 'coupon',
            table: 'salesrule_coupon',
            fields: [
                new Field(
                    name: 'coupon_id',
                    type: 'integer',
                    description: 'Coupon ID'
                ),
                new Field(
                    name: 'rule_id',
                    type: 'integer',
                    description: 'Associated rule ID'
                ),
                new Field(
                    name: 'rule_name',
                    type: 'string',
                    column: 'rule.name',
                    sortable: false,
                    description: 'Rule name'
                ),
                new Field(
                    name: 'code',
                    type: 'string',
                    description: 'Coupon code'
                ),
                new Field(
                    name: 'usage_limit',
                    type: 'integer',
                    sortable: false,
                    description: 'Usage limit (null = unlimited)'
                ),
                new Field(
                    name: 'usage_per_customer',
                    type: 'integer',
                    sortable: false,
                    description: 'Usage per customer limit'
                ),
                new Field(
                    name: 'times_used',
                    type: 'integer',
                    allowAggregate: true,
                    description: 'Times used'
                ),
                new Field(
                    name: 'expiration_date',
                    type: 'date',
                    description: 'Expiration date'
                ),
                new Field(
                    name: 'is_primary',
                    type: 'integer',
                    sortable: false,
                    description: 'Is primary coupon (1 = yes)'
                ),
                new Field(
                    name: 'created_at',
                    type: 'date',
                    allowGroupBy: true,
                    groupByOptions: ['month', 'day'],
                    description: 'Creation date'
                ),
                new Field(
                    name: 'type',
                    type: 'integer',
                    allowGroupBy: true,
                    description: 'Type (0 = manual, 1 = auto-generated)'
                ),
                new Field(
                    name: 'rule_is_active',
                    type: 'integer',
                    column: 'rule.is_active',
                    allowGroupBy: true,
                    sortable: false,
                    description: 'Rule active status'
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
            'Retrieve coupon codes and usage statistics',
            'Find unused or expired coupons',
            'Analyze coupon performance',
        ];
    }

    /**
     * @inheritDoc
     */
    protected function getExamplePrompts(): array
    {
        return [
            'Show me all coupon codes',
            'Find coupon by code SALE20',
            'Which coupons are expiring soon?',
            'List most used coupons',
            'Show unused coupons',
        ];
    }

    /**
     * @inheritDoc
     */
    protected function transformRows(array $rows): array
    {
        foreach ($rows as &$row) {
            if (isset($row['type'])) {
                $row['type'] = $row['type'] ? 'Auto-generated' : 'Manual';
            }
            if (isset($row['rule_is_active'])) {
                $row['rule_is_active'] = $row['rule_is_active'] ? 'Active' : 'Inactive';
            }
            if (isset($row['is_primary'])) {
                $row['is_primary'] = $row['is_primary'] ? 'Yes' : 'No';
            }
        }

        return $rows;
    }
}
