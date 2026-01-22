<?php
declare(strict_types=1);

namespace Freento\Mcp\Model\Tool;

use Freento\Mcp\Model\ResourceModel\StockResource;
use Freento\Mcp\Model\EntityTool\Schema;
use Freento\Mcp\Model\EntityTool\Field;
use Freento\Mcp\Model\Helper\StringHelper;
use Freento\Mcp\Model\ResourceModel\EntityTool\AbstractResource;
use Freento\Mcp\Model\EntityTool\AbstractTool;
use Freento\Mcp\Model\ToolResultFactory;

class GetStockSingleStock extends AbstractTool
{
    private StockResource $stockResource;

    public function __construct(
        StockResource $stockResource,
        ToolResultFactory $resultFactory,
        StringHelper $stringHelper
    ) {
        parent::__construct($resultFactory, $stringHelper);
        $this->stockResource = $stockResource;
    }

    protected function getResource(): AbstractResource
    {
        return $this->stockResource;
    }

    public function getName(): string
    {
        return 'get_stock_single_stock';
    }

    protected function buildSchema(): Schema
    {
        return new Schema(
            entity: 'stock_item',
            table: 'cataloginventory_stock_item',
            fields: [
                new Field(
                    name: 'product_id',
                    type: 'integer',
                    description: 'Product entity ID'
                ),
                new Field(
                    name: 'sku',
                    type: 'string',
                    column: 'product.sku',
                    description: 'Filter by SKU. Supports wildcards: "ABC%" (starts with), "%ABC" (ends with), "%ABC%" (contains)'
                ),
                new Field(
                    name: 'qty',
                    type: 'numeric',
                ),
                new Field(
                    name: 'is_in_stock',
                    type: 'integer',
                ),
                new Field(
                    name: 'min_qty',
                    type: 'numeric',
                    sortable: false
                ),
                new Field(
                    name: 'min_sale_qty',
                    type: 'numeric',
                    sortable: false
                ),
                new Field(
                    name: 'max_sale_qty',
                    type: 'numeric',
                    sortable: false
                ),
                new Field(
                    name: 'manage_stock',
                    type: 'integer',
                    sortable: false
                ),
                new Field(
                    name: 'backorders',
                    type: 'integer',
                    sortable: false
                ),
                new Field(
                    name: 'type_id',
                    type: 'string',
                    column: 'product.type_id',
                    sortable: false
                ),
            ],
            tableAlias: 'stock',
            defaultLimit: 20,
            maxLimit: 100
        );
    }

    protected function getDescriptionLines(): array
    {
        return [
            'Check product stock quantities',
            'Find out-of-stock products',
            'Find low stock products',
            'Analyze inventory levels',
        ];
    }

    protected function getExamplePrompts(): array
    {
        return [
            'Show me out of stock products',
            'Find products with qty less than 10',
            'List low stock items',
            'Get stock for SKU ABC123',
        ];
    }

    protected function getExtraSchemaProperties(): array
    {
        return [
            'product_ids' => [
                'type' => 'array',
                'items' => ['type' => 'integer'],
                'description' => 'Filter by multiple product entity IDs',
            ],
            'stock_status' => [
                'type' => 'string',
                'enum' => ['in_stock', 'out_of_stock'],
                'description' => 'Filter by stock status: "in_stock" or "out_of_stock"',
            ],
            'qty_from' => [
                'type' => 'number',
                'description' => 'Filter products with qty >= this value',
            ],
            'qty_to' => [
                'type' => 'number',
                'description' => 'Filter products with qty <= this value',
            ],
            'low_stock' => [
                'type' => 'boolean',
                'description' => 'If true, show only products where qty <= min_qty (low stock threshold)',
            ],
        ];
    }

    protected function transformRows(array $rows): array
    {
        foreach ($rows as &$row) {
            if (isset($row['is_in_stock'])) {
                $row['is_in_stock'] = $row['is_in_stock'] ? 'In Stock' : 'Out of Stock';
            }
            if (isset($row['qty'])) {
                $qtyFloat = (float)$row['qty'];
                $row['qty'] = ($qtyFloat == (int)$qtyFloat) ? (int)$qtyFloat : $qtyFloat;
            }
        }
        return $rows;
    }
}
