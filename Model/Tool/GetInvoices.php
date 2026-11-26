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
use Freento\Mcp\Model\ResourceModel\EntityTool\InvoiceResource;
use Freento\Mcp\Model\ToolResultFactory;

class GetInvoices extends AbstractTool
{
    /**
     * @param InvoiceResource $invoiceResource
     * @param SchemaFactory $schemaFactory
     * @param ToolResultFactory $resultFactory
     * @param StringHelper $stringHelper
     * @param DateTimeHelper $dateTimeHelper
     */
    public function __construct(
        private readonly InvoiceResource $invoiceResource,
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
        return $this->invoiceResource;
    }

    /**
     * @inheritDoc
     */
    protected function buildSchema(): Schema
    {
        return $this->schemaFactory->create(
            entity: 'invoice',
            table: 'sales_invoice',
            fields: [
                new Field(
                    name: 'entity_id',
                    type: 'integer',
                    sortable: false
                ),
                new Field(
                    name: 'increment_id',
                    type: 'string',
                    description: 'Invoice number (e.g., 000000001)'
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
                    description: 'State (1 = open, 2 = paid, 3 = canceled)'
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
                    description: 'Invoice grand total'
                ),
                new Field(
                    name: 'base_grand_total',
                    type: 'currency',
                    sortable: false,
                    allowAggregate: true,
                    description: 'Invoice grand total in base currency'
                ),
                new Field(
                    name: 'subtotal',
                    type: 'currency',
                    sortable: false,
                    allowAggregate: true
                ),
                new Field(
                    name: 'tax_amount',
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
                    name: 'total_qty',
                    type: 'numeric',
                    sortable: false,
                    allowAggregate: true,
                    description: 'Total quantity of items invoiced'
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
                    name: 'base_currency_code',
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
            'Retrieve invoice information',
            'Track invoiced amounts and payment state',
            'Analyze billing patterns',
        ];
    }

    /**
     * @inheritDoc
     */
    protected function getExamplePrompts(): array
    {
        return [
            'Show me recent invoices',
            'How much was invoiced last month?',
            'Find invoices for order 000000123',
            'List invoices with total > $100',
        ];
    }

    /**
     * @inheritDoc
     */
    protected function transformRows(array $rows): array
    {
        $stateLabels = [
            1 => 'Open',
            2 => 'Paid',
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
