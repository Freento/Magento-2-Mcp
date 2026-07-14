<?php

declare(strict_types=1);

namespace Freento\Mcp\Model\Tool;

use Freento\Mcp\Model\EntityTool\AbstractTool;
use Freento\Mcp\Model\EntityTool\Field;
use Freento\Mcp\Model\EntityTool\Schema;
use Freento\Mcp\Model\Helper\DateTimeHelper;
use Freento\Mcp\Model\Helper\StringHelper;
use Freento\Mcp\Model\ResourceModel\EntityTool\AbstractResource;
use Freento\Mcp\Model\ResourceModel\EntityTool\QuoteResource;
use Freento\Mcp\Model\ToolResultFactory;
use Freento\Mcp\Model\EntityTool\SchemaFactory;

class GetQuotes extends AbstractTool
{
    /**
     * @param QuoteResource $quoteResource
     * @param SchemaFactory $schemaFactory
     * @param ToolResultFactory $resultFactory
     * @param StringHelper $stringHelper
     * @param DateTimeHelper $dateTimeHelper
     */
    public function __construct(
        private readonly QuoteResource $quoteResource,
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
        return $this->quoteResource;
    }

    /**
     * @inheritdoc
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    protected function buildSchema(): Schema
    {
        return $this->schemaFactory->create(
            entity: 'quote',
            table: 'quote',
            fields: [
                new Field(
                    name: 'entity_id',
                    type: 'integer',
                    description: 'Quote entity ID'
                ),
                new Field(
                    name: 'store_id',
                    type: 'integer',
                    allowGroupBy: true,
                    description: 'Store ID'
                ),
                new Field(
                    name: 'created_at',
                    type: 'date',
                    allowGroupBy: true,
                    groupByOptions: ['month', 'day'],
                    description: 'Quote creation date (YYYY-MM-DD)'
                ),
                new Field(
                    name: 'updated_at',
                    type: 'date',
                    description: 'Quote last update date (YYYY-MM-DD)'
                ),
                new Field(
                    name: 'is_active',
                    type: 'integer',
                    allowGroupBy: true,
                    description: 'Active status (1 = active cart, 0 = converted to order)'
                ),
                new Field(
                    name: 'is_virtual',
                    type: 'integer',
                    filter: false,
                    sortable: false
                ),
                new Field(
                    name: 'items_count',
                    type: 'integer',
                    column: 'items.items_count',
                    filter: false,
                    sortable: false,
                    allowAggregate: true
                ),
                new Field(
                    name: 'items_qty',
                    type: 'numeric',
                    column: 'items.items_qty',
                    filter: false,
                    sortable: false,
                    allowAggregate: true
                ),
                new Field(
                    name: 'grand_total',
                    type: 'currency',
                    filter: false,
                    sortable: false,
                ),
                new Field(
                    name: 'base_grand_total',
                    type: 'currency',
                    description: 'Quote base grand total',
                    allowAggregate: true
                ),
                new Field(
                    name: 'base_currency_code',
                    type: 'string',
                    filter: false,
                    sortable: false
                ),
                new Field(
                    name: 'quote_currency_code',
                    type: 'string',
                    filter: false,
                    sortable: false
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
                    type: 'string',
                    filter: false,
                    sortable: false,
                    anonymous: true
                ),
                new Field(
                    name: 'customer_lastname',
                    type: 'string',
                    filter: false,
                    sortable: false,
                    anonymous: true
                ),
                new Field(
                    name: 'customer_is_guest',
                    type: 'integer',
                    allowGroupBy: true,
                    description: 'Guest flag (1 = guest, 0 = registered)'
                ),
                new Field(
                    name: 'reserved_order_id',
                    type: 'string',
                    description: 'Reserved order increment ID'
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
            'Retrieve shopping cart (quote) information',
            'Find abandoned carts',
            'Check customer cart contents',
        ];
    }

    /**
     * @inheritDoc
     */
    protected function getExamplePrompts(): array
    {
        return [
            'Show me active shopping carts',
            'Find quotes for customer@example.com',
            'List abandoned carts from last week',
            'Get quote for customer ID 5',
        ];
    }
}
