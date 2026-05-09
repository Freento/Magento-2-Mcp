<?php

declare(strict_types=1);

namespace Freento\Mcp\Model\Tool;

use Freento\Mcp\Model\EntityTool\AbstractTool;
use Freento\Mcp\Model\EntityTool\Field;
use Freento\Mcp\Model\EntityTool\Schema;
use Freento\Mcp\Model\Helper\DateTimeHelper;
use Freento\Mcp\Model\Helper\StringHelper;
use Freento\Mcp\Model\ResourceModel\EntityTool\AbstractResource;
use Freento\Mcp\Model\ResourceModel\EntityTool\QuoteItemResource;
use Freento\Mcp\Model\ToolResultFactory;
use Freento\Mcp\Model\EntityTool\SchemaFactory;

class GetQuoteItems extends AbstractTool
{
    /**
     * @param QuoteItemResource $quoteItemResource
     * @param SchemaFactory $schemaFactory
     * @param ToolResultFactory $resultFactory
     * @param StringHelper $stringHelper
     * @param DateTimeHelper $dateTimeHelper
     */
    public function __construct(
        private readonly QuoteItemResource $quoteItemResource,
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
        return $this->quoteItemResource;
    }

    /**
     * @inheritdoc
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    protected function buildSchema(): Schema
    {
        return $this->schemaFactory->create(
            entity: 'quote_item',
            table: 'quote_item',
            fields: [
                new Field(
                    name: 'item_id',
                    type: 'integer',
                    description: 'Quote item ID'
                ),
                new Field(
                    name: 'quote_id',
                    type: 'integer',
                    allowGroupBy: true,
                    description: 'Quote entity ID'
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
                    name: 'qty',
                    type: 'numeric',
                    allowAggregate: true,
                    description: 'Quantity in cart'
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
            'Retrieve shopping cart line items',
            'Analyze cart contents',
            'Check abandoned cart items',
        ];
    }

    /**
     * @inheritDoc
     */
    protected function getExamplePrompts(): array
    {
        return [
            'Show items in quote ID 5',
            'Find cart items with SKU ABC123',
            'List items with qty > 2',
        ];
    }
}
