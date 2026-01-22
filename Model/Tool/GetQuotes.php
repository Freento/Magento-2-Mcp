<?php
declare(strict_types=1);

namespace Freento\Mcp\Model\Tool;

use Freento\Mcp\Model\ResourceModel\QuoteResource;
use Freento\Mcp\Model\EntityTool\Schema;
use Freento\Mcp\Model\EntityTool\Field;
use Freento\Mcp\Model\Helper\StringHelper;
use Freento\Mcp\Model\ResourceModel\EntityTool\AbstractResource;
use Freento\Mcp\Model\EntityTool\AbstractTool;
use Freento\Mcp\Model\ToolResultFactory;

class GetQuotes extends AbstractTool
{
    private QuoteResource $quoteResource;

    public function __construct(
        QuoteResource $quoteResource,
        ToolResultFactory $resultFactory,
        StringHelper $stringHelper
    ) {
        parent::__construct($resultFactory, $stringHelper);
        $this->quoteResource = $quoteResource;
    }

    protected function getResource(): AbstractResource
    {
        return $this->quoteResource;
    }

    protected function buildSchema(): Schema
    {
        return new Schema(
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
                    description: 'Store ID'
                ),
                new Field(
                    name: 'created_at',
                    type: 'date',
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
                    column: false,
                    filter: false,
                    sortable: false
                ),
                new Field(
                    name: 'items_qty',
                    type: 'numeric',
                    column: false,
                    filter: false,
                    sortable: false
                ),
                new Field(
                    name: 'grand_total',
                    type: 'currency',
                    description: 'Quote grand total'
                ),
                new Field(
                    name: 'base_grand_total',
                    type: 'currency',
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
                    description: 'Customer ID (null for guest)'
                ),
                new Field(
                    name: 'customer_email',
                    type: 'string',
                    description: 'Customer email address'
                ),
                new Field(
                    name: 'customer_firstname',
                    type: 'string',
                    filter: false,
                    sortable: false
                ),
                new Field(
                    name: 'customer_lastname',
                    type: 'string',
                    filter: false,
                    sortable: false
                ),
                new Field(
                    name: 'customer_is_guest',
                    type: 'integer',
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

    protected function getDescriptionLines(): array
    {
        return [
            'Retrieve shopping cart (quote) information',
            'Find abandoned carts',
            'Check customer cart contents',
        ];
    }

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
