<?php
declare(strict_types=1);

namespace Freento\Mcp\Model\Tool;

use Freento\Mcp\Model\ResourceModel\OrderItemResource;
use Freento\Mcp\Model\EntityTool\Schema;
use Freento\Mcp\Model\EntityTool\Field;
use Freento\Mcp\Model\Helper\StringHelper;
use Freento\Mcp\Model\ResourceModel\EntityTool\AbstractResource;
use Freento\Mcp\Model\EntityTool\AbstractTool;
use Freento\Mcp\Model\ToolResultFactory;

class GetOrderItems extends AbstractTool
{
    private OrderItemResource $orderItemResource;

    public function __construct(
        OrderItemResource $orderItemResource,
        ToolResultFactory $resultFactory,
        StringHelper $stringHelper
    ) {
        parent::__construct($resultFactory, $stringHelper);
        $this->orderItemResource = $orderItemResource;
    }

    protected function getResource(): AbstractResource
    {
        return $this->orderItemResource;
    }

    protected function buildSchema(): Schema
    {
        return new Schema(
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
                    description: 'Store ID'
                ),
                new Field(
                    name: 'product_id',
                    type: 'integer',
                    description: 'Product ID'
                ),
                new Field(
                    name: 'product_type',
                    type: 'string',
                    description: 'Product type (simple, configurable, bundle, etc.)'
                ),
                new Field(
                    name: 'sku',
                    type: 'string',
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
                    description: 'Quantity ordered'
                ),
                new Field(
                    name: 'qty_shipped',
                    type: 'numeric',
                    filter: false,
                    sortable: false
                ),
                new Field(
                    name: 'qty_invoiced',
                    type: 'numeric',
                    filter: false,
                    sortable: false
                ),
                new Field(
                    name: 'qty_refunded',
                    type: 'numeric',
                    filter: false,
                    sortable: false
                ),
                new Field(
                    name: 'qty_canceled',
                    type: 'numeric',
                    filter: false,
                    sortable: false
                ),
                new Field(
                    name: 'price',
                    type: 'currency',
                    description: 'Item price'
                ),
                new Field(
                    name: 'row_total',
                    type: 'currency',
                    description: 'Row total'
                ),
                new Field(
                    name: 'discount_amount',
                    type: 'currency',
                    filter: false,
                    sortable: false
                ),
                new Field(
                    name: 'tax_amount',
                    type: 'currency',
                    filter: false,
                    sortable: false
                ),
                new Field(
                    name: 'created_at',
                    type: 'date',
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

    protected function getDescriptionLines(): array
    {
        return [
            'Retrieve order line items',
            'Analyze product sales',
            'Check item quantities and prices',
        ];
    }

    protected function getExamplePrompts(): array
    {
        return [
            'Show items for order ID 5',
            'Find order items with SKU ABC123',
            'List items with qty_ordered > 2',
        ];
    }
}
