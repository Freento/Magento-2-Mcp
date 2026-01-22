<?php
declare(strict_types=1);

namespace Freento\Mcp\Model\Tool;

use Freento\Mcp\Model\ResourceModel\ProductResource;
use Freento\Mcp\Model\EntityTool\Schema;
use Freento\Mcp\Model\EntityTool\Field;
use Freento\Mcp\Model\Helper\StringHelper;
use Freento\Mcp\Model\ResourceModel\EntityTool\AbstractResource;
use Freento\Mcp\Model\EntityTool\AbstractTool;
use Freento\Mcp\Model\ToolResultFactory;

class GetProducts extends AbstractTool
{
    private ProductResource $productResource;

    public function __construct(
        ProductResource $productResource,
        ToolResultFactory $resultFactory,
        StringHelper $stringHelper
    ) {
        parent::__construct($resultFactory, $stringHelper);
        $this->productResource = $productResource;
    }

    protected function getResource(): AbstractResource
    {
        return $this->productResource;
    }

    protected function buildSchema(): Schema
    {
        return new Schema(
            entity: 'product',
            table: 'catalog_product_entity',
            fields: [
                new Field(
                    name: 'entity_id',
                    type: 'integer',
                    description: 'Product entity ID'
                ),
                new Field(
                    name: 'sku',
                    type: 'string',
                    description: 'Filter by SKU. Supports wildcards: "ABC%" (starts with), "%ABC" (ends with), "%ABC%" (contains)'
                ),
                new Field(
                    name: 'type_id',
                    type: 'string',
                    description: 'Filter by product type (simple, configurable, grouped, bundle, virtual, downloadable)'
                ),
                new Field(
                    name: 'attribute_set_id',
                    type: 'integer',
                    sortable: false,
                    description: 'Filter by attribute set ID'
                ),
                new Field(
                    name: 'created_at',
                    type: 'date',
                    description: 'Filter products created on or after/before this date (YYYY-MM-DD)'
                ),
                new Field(
                    name: 'updated_at',
                    type: 'date',
                    description: 'Filter products updated on or after/before this date (YYYY-MM-DD)'
                ),
            ],
            defaultLimit: 20,
            maxLimit: 100
        );
    }

    protected function getDescriptionLines(): array
    {
        return [
            'Search for products by SKU, ID, or dates',
            'Filter products by any attribute (e.g., color, size, manufacturer)',
            'Analyze product catalog data',
        ];
    }

    protected function getExamplePrompts(): array
    {
        return [
            'Show me recent products',
            'Get products with SKU containing ABC',
            'Find products updated in the last week',
            'List products where color is red',
            'Get products with manufacturer = Nike',
        ];
    }

    protected function getExtraSchemaProperties(): array
    {
        return [
            'ids' => [
                'type' => 'array',
                'items' => ['type' => 'integer'],
                'description' => 'Filter by multiple product entity IDs',
            ],
            'status' => [
                'type' => 'integer',
                'description' => 'Filter by status (1 = enabled, 2 = disabled)',
            ],
            'visibility' => [
                'type' => 'integer',
                'description' => 'Filter by visibility (1 = not visible, 2 = catalog, 3 = search, 4 = catalog+search)',
            ],
            'attribute_filter' => [
                'type' => 'object',
                'description' => 'Filter by any product attribute. Keys are attribute codes, values are filter values. Example: {"color": "red", "manufacturer": "Nike"}. Supports wildcards with % for text attributes.',
                'additionalProperties' => true,
            ],
            'store_id' => [
                'type' => 'integer',
                'description' => 'Store ID for attribute values (default: 0 for admin/default)',
            ],
        ];
    }
}
