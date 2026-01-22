<?php
declare(strict_types=1);

namespace Freento\Mcp\Model\Tool;

use Freento\Mcp\Model\ResourceModel\QuoteItemResource;
use Freento\Mcp\Model\EntityTool\Schema;
use Freento\Mcp\Model\EntityTool\Field;
use Freento\Mcp\Model\Helper\StringHelper;
use Freento\Mcp\Model\ResourceModel\EntityTool\AbstractResource;
use Freento\Mcp\Model\EntityTool\AbstractTool;
use Freento\Mcp\Model\ToolResultFactory;

class GetQuoteItems extends AbstractTool
{
    private QuoteItemResource $quoteItemResource;

    public function __construct(
        QuoteItemResource $quoteItemResource,
        ToolResultFactory $resultFactory,
        StringHelper $stringHelper
    ) {
        parent::__construct($resultFactory, $stringHelper);
        $this->quoteItemResource = $quoteItemResource;
    }

    protected function getResource(): AbstractResource
    {
        return $this->quoteItemResource;
    }

    protected function buildSchema(): Schema
    {
        return new Schema(
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
                    name: 'qty',
                    type: 'numeric',
                    description: 'Quantity in cart'
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
            'Retrieve shopping cart line items',
            'Analyze cart contents',
            'Check abandoned cart items',
        ];
    }

    protected function getExamplePrompts(): array
    {
        return [
            'Show items in quote ID 5',
            'Find cart items with SKU ABC123',
            'List items with qty > 2',
        ];
    }
}
