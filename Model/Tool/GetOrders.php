<?php

declare(strict_types=1);

namespace Freento\Mcp\Model\Tool;

use Freento\Mcp\Model\EntityTool\AbstractTool;
use Freento\Mcp\Model\EntityTool\Field;
use Freento\Mcp\Model\EntityTool\Schema;
use Freento\Mcp\Model\Helper\DateTimeHelper;
use Freento\Mcp\Model\Helper\StringHelper;
use Freento\Mcp\Model\ResourceModel\EntityTool\AbstractResource;
use Freento\Mcp\Model\ResourceModel\EntityTool\OrderResource;
use Freento\Mcp\Model\EntityTool\SchemaFactory;
use Freento\Mcp\Model\ToolResultFactory;

class GetOrders extends AbstractTool
{
    /**
     * @param OrderResource $orderResource
     * @param SchemaFactory $schemaFactory
     * @param ToolResultFactory $resultFactory
     * @param StringHelper $stringHelper
     * @param DateTimeHelper $dateTimeHelper
     */
    public function __construct(
        private readonly OrderResource $orderResource,
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
        return $this->orderResource;
    }

    /**
     * @inheritdoc
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    protected function buildSchema(): Schema
    {
        return $this->schemaFactory->create(
            entity: 'order',
            table: 'sales_order',
            fields: [
                new Field(
                    name: 'entity_id',
                    sortable: false
                ),
                new Field(
                    name: 'increment_id',
                    type: 'string',
                    description: 'Order number (e.g., 000000001)'
                ),
                new Field(
                    name: 'status',
                    type: 'string',
                    allowGroupBy: true,
                    description: 'Order status (pending, processing, complete, canceled, closed, holded)'
                ),
                new Field(
                    name: 'state',
                    sortable: false
                ),
                new Field(
                    name: 'created_at',
                    type: 'date',
                    allowGroupBy: true,
                    groupByOptions: ['month', 'day'],
                    description: 'Order creation date (YYYY-MM-DD or YYYY-MM-DD HH:MM:SS)'
                ),
                new Field(
                    name: 'updated_at',
                    type: 'date'
                ),
                new Field(
                    name: 'customer_id',
                    type: 'integer',
                    allowGroupBy: true,
                    description: 'Customer ID (null for guest)'
                ),
                new Field(
                    name: 'customer_email',
                    type: 'string',
                    allowGroupBy: true,
                    description: 'Customer email address',
                    anonymous: true
                ),
                new Field(
                    name: 'customer_firstname',
                    sortable: false,
                    anonymous: true
                ),
                new Field(
                    name: 'customer_lastname',
                    sortable: false,
                    anonymous: true
                ),
                new Field(
                    name: 'base_grand_total',
                    type: 'currency',
                    description: 'Order base grand total amount',
                    allowAggregate: true
                ),
                new Field(
                    name: 'base_currency_code',
                    type: 'string',
                    sortable: false
                ),
                new Field(
                    name: 'order_currency_code',
                    type: 'string',
                    sortable: false
                ),
                new Field(
                    name: 'total_qty_ordered',
                    type: 'numeric',
                    sortable: false,
                    allowAggregate: true
                ),
                new Field(
                    name: 'total_item_count',
                    type: 'integer',
                    sortable: false,
                    allowAggregate: true
                ),
                new Field(
                    name: 'store_id',
                    type: 'integer',
                    allowGroupBy: true,
                    description: 'Store ID'
                ),
                new Field(
                    name: 'shipping_description',
                    type: 'string'
                ),
                new Field(
                    name: 'payment_method',
                    type: 'string',
                    column: 'payment.method',
                    allowGroupBy: true,
                    description: 'Payment method code (checkmo, cashondelivery, paypal_express, etc.)'
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
            'Export order information',
        ];
    }

    /**
     * @inheritDoc
     */
    protected function getExamplePrompts(): array
    {
        return [
            'Show me recent orders',
            'Get orders from last week',
            'Find orders for customer@example.com',
            'List pending orders',
        ];
    }
}
