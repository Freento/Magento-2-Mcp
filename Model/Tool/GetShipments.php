<?php

declare(strict_types=1);

namespace Freento\Mcp\Model\Tool;

use Freento\Mcp\Model\EntityTool\AbstractTool;
use Freento\Mcp\Model\EntityTool\Field;
use Freento\Mcp\Model\EntityTool\Schema;
use Freento\Mcp\Model\EntityTool\SchemaFactory;
use Freento\Mcp\Model\Helper\DateTimeHelper;
use Freento\Mcp\Model\Helper\StringHelper;
use Freento\Mcp\Model\ResourceModel\EntityTool\AbstractResource;
use Freento\Mcp\Model\ResourceModel\EntityTool\ShipmentResource;
use Freento\Mcp\Model\ToolResultFactory;

class GetShipments extends AbstractTool
{
    /**
     * @param ShipmentResource $shipmentResource
     * @param SchemaFactory $schemaFactory
     * @param ToolResultFactory $resultFactory
     * @param StringHelper $stringHelper
     * @param DateTimeHelper $dateTimeHelper
     */
    public function __construct(
        private readonly ShipmentResource $shipmentResource,
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
        return $this->shipmentResource;
    }

    /**
     * @inheritDoc
     */
    protected function buildSchema(): Schema
    {
        return $this->schemaFactory->create(
            entity: 'shipment',
            table: 'sales_shipment',
            fields: [
                new Field(
                    name: 'entity_id',
                    type: 'integer',
                    sortable: false
                ),
                new Field(
                    name: 'increment_id',
                    type: 'string',
                    description: 'Shipment number (e.g., 000000001)'
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
                    name: 'total_qty',
                    type: 'numeric',
                    allowAggregate: true,
                    description: 'Total quantity of items shipped'
                ),
                new Field(
                    name: 'total_weight',
                    type: 'numeric',
                    sortable: false,
                    allowAggregate: true,
                    description: 'Total weight of the shipment'
                ),
                new Field(
                    name: 'store_id',
                    type: 'integer',
                    allowGroupBy: true,
                    description: 'Store ID'
                ),
                new Field(
                    name: 'customer_id',
                    type: 'integer',
                    allowGroupBy: true,
                    description: 'Customer ID (null for guest)'
                ),
                new Field(
                    name: 'email_sent',
                    type: 'integer',
                    sortable: false,
                    allowGroupBy: true,
                    description: 'Whether the shipment email was sent (1 = yes, 0 = no)'
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
            'Retrieve shipment information',
            'Track shipped quantities and weight',
            'Analyze fulfillment patterns',
        ];
    }

    /**
     * @inheritDoc
     */
    protected function getExamplePrompts(): array
    {
        return [
            'Show me recent shipments',
            'How many shipments were created last month?',
            'Find shipments for order 000000123',
            'List shipments for customer@example.com',
        ];
    }
}
