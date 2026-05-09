<?php

declare(strict_types=1);

namespace Freento\Mcp\Model\Tool;

use Freento\Mcp\Model\EntityTool\AbstractTool;
use Freento\Mcp\Model\EntityTool\Field;
use Freento\Mcp\Model\EntityTool\Schema;
use Freento\Mcp\Model\Helper\DateTimeHelper;
use Freento\Mcp\Model\Helper\StringHelper;
use Freento\Mcp\Model\ResourceModel\EntityTool\AbstractResource;
use Freento\Mcp\Model\ResourceModel\EntityTool\CreditmemoResource;
use Freento\Mcp\Model\ToolResultFactory;
use Freento\Mcp\Model\EntityTool\SchemaFactory;

class GetCreditmemos extends AbstractTool
{
    /**
     * @param CreditmemoResource $creditmemoResource
     * @param SchemaFactory $schemaFactory
     * @param ToolResultFactory $resultFactory
     * @param StringHelper $stringHelper
     * @param DateTimeHelper $dateTimeHelper
     */
    public function __construct(
        private readonly CreditmemoResource $creditmemoResource,
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
        return $this->creditmemoResource;
    }

    /**
     * @inheritDoc
     */
    protected function buildSchema(): Schema
    {
        return $this->schemaFactory->create(
            entity: 'creditmemo',
            table: 'sales_creditmemo',
            fields: [
                new Field(
                    name: 'entity_id',
                    type: 'integer',
                    sortable: false
                ),
                new Field(
                    name: 'increment_id',
                    type: 'string',
                    description: 'Credit memo number (e.g., 000000001)'
                ),
                new Field(
                    name: 'order_id',
                    type: 'integer',
                    description: 'Order entity ID'
                ),
                new Field(
                    name: 'order_increment_id',
                    type: 'string',
                    column: 'order.increment_id',
                    sortable: false,
                    description: 'Order number'
                ),
                new Field(
                    name: 'state',
                    type: 'integer',
                    allowGroupBy: true,
                    description: 'State (1 = open, 2 = refunded, 3 = canceled)'
                ),
                new Field(
                    name: 'created_at',
                    type: 'date',
                    allowGroupBy: true,
                    groupByOptions: ['month', 'day'],
                    description: 'Creation date (YYYY-MM-DD)'
                ),
                new Field(
                    name: 'updated_at',
                    type: 'date'
                ),
                new Field(
                    name: 'grand_total',
                    type: 'currency',
                    allowAggregate: true,
                    description: 'Credit memo grand total'
                ),
                new Field(
                    name: 'subtotal',
                    type: 'currency',
                    sortable: false,
                    allowAggregate: true
                ),
                new Field(
                    name: 'shipping_amount',
                    type: 'currency',
                    sortable: false,
                    allowAggregate: true
                ),
                new Field(
                    name: 'adjustment_positive',
                    type: 'currency',
                    sortable: false,
                    description: 'Adjustment refund amount'
                ),
                new Field(
                    name: 'adjustment_negative',
                    type: 'currency',
                    sortable: false,
                    description: 'Adjustment fee amount'
                ),
                new Field(
                    name: 'store_id',
                    type: 'integer',
                    allowGroupBy: true,
                    description: 'Store ID'
                ),
                new Field(
                    name: 'order_currency_code',
                    type: 'string',
                    sortable: false
                ),
                new Field(
                    name: 'customer_email',
                    type: 'string',
                    column: 'order.customer_email',
                    sortable: false,
                    description: 'Customer email',
                    anonymous: true
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
            'Retrieve credit memo (refund) information',
            'Track refund amounts and reasons',
            'Analyze return patterns',
        ];
    }

    /**
     * @inheritDoc
     */
    protected function getExamplePrompts(): array
    {
        return [
            'Show me recent credit memos',
            'How much was refunded last month?',
            'Find refunds for order 000000123',
            'List credit memos with total > $100',
        ];
    }

    /**
     * @inheritDoc
     */
    protected function transformRows(array $rows): array
    {
        $stateLabels = [
            1 => 'Open',
            2 => 'Refunded',
            3 => 'Canceled',
        ];

        foreach ($rows as &$row) {
            if (isset($row['state'])) {
                $row['state'] = $stateLabels[(int)$row['state']] ?? $row['state'];
            }
        }

        return $rows;
    }
}
