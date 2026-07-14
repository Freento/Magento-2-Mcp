<?php

declare(strict_types=1);

namespace Freento\Mcp\Model\Tool;

use Freento\Mcp\Model\EntityTool\AbstractTool;
use Freento\Mcp\Model\EntityTool\Field;
use Freento\Mcp\Model\EntityTool\Schema;
use Freento\Mcp\Model\Helper\DateTimeHelper;
use Freento\Mcp\Model\Helper\StringHelper;
use Freento\Mcp\Model\ResourceModel\EntityTool\AbstractResource;
use Freento\Mcp\Model\ResourceModel\EntityTool\ProductTierPriceResource;
use Freento\Mcp\Model\ToolResultFactory;

class GetProductTierPrices extends AbstractTool
{
    /**
     * @param ProductTierPriceResource $productTierPriceResource
     * @param ToolResultFactory $resultFactory
     * @param StringHelper $stringHelper
     * @param DateTimeHelper $dateTimeHelper
     */
    public function __construct(
        private readonly ProductTierPriceResource $productTierPriceResource,
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
        return $this->productTierPriceResource;
    }

    /**
     * @inheritDoc
     */
    public function getName(): string
    {
        return 'get_product_tier_prices';
    }

    /**
     * @inheritDoc
     */
    protected function buildSchema(): Schema
    {
        return new Schema(
            entity: 'product_tier_price',
            table: 'catalog_product_entity_tier_price',
            fields: [
                new Field(
                    name: 'value_id',
                    type: 'integer',
                    description: 'Tier price record ID'
                ),
                new Field(
                    name: 'entity_id',
                    type: 'integer',
                    column: 'catalog_product_entity.entity_id',
                    sortable: false,
                    description: 'Product entity ID'
                ),
                new Field(
                    name: 'sku',
                    type: 'string',
                    column: 'catalog_product_entity.sku',
                    sortable: false,
                    description: 'Product SKU. Supports wildcards.'
                ),
                new Field(
                    name: 'all_groups',
                    type: 'integer',
                    sortable: false,
                    description: 'Applies to all customer groups (1 = yes, 0 = specific group)',
                    allowGroupBy: true
                ),
                new Field(
                    name: 'customer_group_id',
                    type: 'integer',
                    sortable: false,
                    description: 'Customer group ID (relevant when all_groups = 0)',
                    allowGroupBy: true
                ),
                new Field(
                    name: 'customer_group_code',
                    type: 'string',
                    column: 'customer_group.customer_group_code',
                    filter: false,
                    sortable: false,
                    description: 'Customer group name'
                ),
                new Field(
                    name: 'qty',
                    type: 'numeric',
                    description: 'Minimum quantity to trigger tier price',
                    allowAggregate: true
                ),
                new Field(
                    name: 'value',
                    type: 'currency',
                    description: 'Fixed tier price (0 when percentage_value is set)',
                    allowAggregate: true
                ),
                new Field(
                    name: 'percentage_value',
                    type: 'numeric',
                    sortable: false,
                    description: 'Discount percentage (null when fixed value is set)',
                    allowAggregate: true
                ),
                new Field(
                    name: 'website_id',
                    type: 'integer',
                    sortable: false,
                    description: 'Website ID (0 = all websites)',
                    allowGroupBy: true
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
            'Query tier price rules for products',
            'Find quantity-based discounts by product or customer group',
            'Analyze tier pricing across customer groups',
        ];
    }

    /**
     * @inheritDoc
     */
    protected function getExamplePrompts(): array
    {
        return [
            'Show all tier prices',
            'What tier prices exist for product entity_id 42?',
            'Find tier prices for wholesale customer group',
            'Which products have percentage-based tier discounts?',
        ];
    }

    /**
     * @inheritDoc
     */
    protected function transformRows(array $rows): array
    {
        foreach ($rows as &$row) {
            if (isset($row['all_groups'])) {
                $row['all_groups'] = $row['all_groups'] ? 'Yes' : 'No';
            }
        }

        return $rows;
    }
}
