<?php
declare(strict_types=1);

namespace Freento\Mcp\Model\Tool;

use Freento\Mcp\Model\ResourceModel\OrderResource;
use Freento\Mcp\Model\EntityTool\Schema;
use Freento\Mcp\Model\EntityTool\Field;
use Freento\Mcp\Model\Helper\StringHelper;
use Freento\Mcp\Model\ResourceModel\EntityTool\AbstractResource;
use Freento\Mcp\Model\EntityTool\AbstractTool;
use Freento\Mcp\Model\ToolResultFactory;

class GetOrders extends AbstractTool
{
    private OrderResource $orderResource;

    public function __construct(
        OrderResource $orderResource,
        ToolResultFactory $resultFactory,
        StringHelper $stringHelper
    ) {
        parent::__construct($resultFactory, $stringHelper);
        $this->orderResource = $orderResource;
    }

    protected function getResource(): AbstractResource
    {
        return $this->orderResource;
    }

    protected function buildSchema(): Schema
    {
        return new Schema(
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
                    name: 'customer_email',
                    type: 'string',
                    allowGroupBy: true,
                    description: 'Customer email address'
                ),
                new Field(
                    name: 'customer_firstname',
                    sortable: false
                ),
                new Field(
                    name: 'customer_lastname',
                    sortable: false
                ),
                new Field(
                    name: 'grand_total',
                    type: 'currency',
                    allowAggregate: true,
                    description: 'Order grand total amount'
                ),
                new Field(
                    name: 'order_currency_code',
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

    protected function getDescriptionLines(): array
    {
        return [
            'Export order information',
        ];
    }

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
