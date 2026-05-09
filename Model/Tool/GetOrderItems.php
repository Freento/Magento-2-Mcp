<?php

declare(strict_types=1);

namespace Freento\Mcp\Model\Tool;

use Freento\Mcp\Model\EntityTool\AbstractTool;
use Freento\Mcp\Model\EntityTool\Field;
use Freento\Mcp\Model\EntityTool\Schema;
use Freento\Mcp\Model\Helper\DateTimeHelper;
use Freento\Mcp\Model\Helper\StringHelper;
use Freento\Mcp\Model\ResourceModel\EntityTool\AbstractResource;
use Freento\Mcp\Model\ResourceModel\EntityTool\OrderItemResource;
use Freento\Mcp\Model\ToolResultFactory;
use Freento\Mcp\Model\EntityTool\SchemaFactory;

class GetOrderItems extends AbstractTool
{
    /**
     * @param OrderItemResource $orderItemResource
     * @param SchemaFactory $schemaFactory
     * @param ToolResultFactory $resultFactory
     * @param StringHelper $stringHelper
     * @param DateTimeHelper $dateTimeHelper
     */
    public function __construct(
        private readonly OrderItemResource $orderItemResource,
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
        return $this->orderItemResource;
    }

    /**
     * @inheritdoc
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    protected function buildSchema(): Schema
    {
        return $this->schemaFactory->create(
            entity: 'order_item',
            table: 'sales_order_item',
            fields: [
                new Field(
                    name: 'item_id',
                    type: 'integer',
                    description: 'Order item ID'
                ),
                new Field(
                    name: 'order_id',
                    type: 'integer',
                    allowGroupBy: true,
                    description: 'Order entity ID'
                ),
                new Field(
                    name: 'parent_item_id',
                    type: 'integer',
                    description: 'Parent item ID (for configurable/bundle children)'
                ),
                new Field(
                    name: 'store_id',
                    type: 'integer',
                    allowGroupBy: true,
                    description: 'Store ID'
                ),
                new Field(
                    name: 'product_id',
                    type: 'integer',
                    allowGroupBy: true,
                    description: 'Product ID'
                ),
                new Field(
                    name: 'product_type',
                    type: 'string',
                    allowGroupBy: true,
                    description: 'Product type (simple, configurable, bundle, etc.)'
                ),
                new Field(
                    name: 'sku',
                    type: 'string',
                    allowGroupBy: true,
                    description: 'Product SKU'
                ),
                new Field(
                    name: 'name',
                    type: 'string',
                    filter: false,
                    sortable: false
                ),
                new Field(
                    name: 'qty_ordered',
                    type: 'numeric',
                    allowAggregate: true,
                    description: 'Quantity ordered'
                ),
                new Field(
                    name: 'qty_shipped',
                    type: 'numeric',
                    filter: false,
                    allowAggregate: true,
                    sortable: false
                ),
                new Field(
                    name: 'qty_invoiced',
                    type: 'numeric',
                    filter: false,
                    allowAggregate: true,
                    sortable: false
                ),
                new Field(
                    name: 'qty_refunded',
                    type: 'numeric',
                    filter: false,
                    allowAggregate: true,
                    sortable: false
                ),
                new Field(
                    name: 'qty_canceled',
                    type: 'numeric',
                    filter: false,
                    allowAggregate: true,
                    sortable: false
                ),
                new Field(
                    name: 'base_price',
                    type: 'currency',
                    description: 'Base item price',
                    allowAggregate: true
                ),
                new Field(
                    name: 'base_row_total',
                    type: 'currency',
                    description: 'Base row total',
                    allowAggregate: true
                ),
                new Field(
                    name: 'base_discount_amount',
                    type: 'currency',
                    filter: false,
                    sortable: false,
                    allowAggregate: true
                ),
                new Field(
                    name: 'base_tax_amount',
                    type: 'currency',
                    filter: false,
                    sortable: false,
                    allowAggregate: true
                ),
                new Field(
                    name: 'created_at',
                    type: 'date',
                    allowGroupBy: true,
                    groupByOptions: ['month', 'day'],
                    description: 'Item creation date'
                ),
                new Field(
                    name: 'updated_at',
                    type: 'date'
                ),
            ],
            defaultLimit: 100,
            maxLimit: 500
        );
    }

    /**
     * @inheritDoc
     */
    protected function getDescriptionLines(): array
    {
        return [
            'Retrieve order line items',
            'Analyze product sales',
            'Check item quantities and prices',
        ];
    }

    /**
     * @inheritDoc
     */
    protected function getExamplePrompts(): array
    {
        return [
            'Show items for order ID 5',
            'Find order items with SKU ABC123',
            'List items with qty_ordered > 2',
        ];
    }
}
