<?php

declare(strict_types=1);

namespace Freento\Mcp\Model\Tool;

use Freento\Mcp\Model\EntityTool\AbstractTool;
use Freento\Mcp\Model\EntityTool\Field;
use Freento\Mcp\Model\EntityTool\Schema;
use Freento\Mcp\Model\Helper\DateTimeHelper;
use Freento\Mcp\Model\Helper\StringHelper;
use Freento\Mcp\Model\ResourceModel\EntityTool\AbstractResource;
use Freento\Mcp\Model\ResourceModel\EntityTool\CustomerResource;
use Freento\Mcp\Model\ToolResultFactory;
use Freento\Mcp\Model\EntityTool\SchemaFactory;

class GetCustomers extends AbstractTool
{
    /**
     * @param CustomerResource $customerResource
     * @param SchemaFactory $schemaFactory
     * @param ToolResultFactory $resultFactory
     * @param StringHelper $stringHelper
     * @param DateTimeHelper $dateTimeHelper
     */
    public function __construct(
        private readonly CustomerResource $customerResource,
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
        return $this->customerResource;
    }

    /**
     * @inheritDoc
     */
    protected function buildSchema(): Schema
    {
        return $this->schemaFactory->create(
            entity: 'customer',
            table: 'customer_entity',
            fields: [
                new Field(
                    name: 'entity_id',
                    type: 'integer',
                    description: 'Customer entity ID'
                ),
                new Field(
                    name: 'email',
                    type: 'string',
                    description: 'Customer email address (supports wildcards: %@example.com)',
                    anonymous: true
                ),
                new Field(
                    name: 'firstname',
                    type: 'string',
                    column: true,
                    anonymous: true
                ),
                new Field(
                    name: 'lastname',
                    type: 'string',
                    column: true,
                    anonymous: true
                ),
                new Field(
                    name: 'group_id',
                    type: 'integer',
                    sortable: false,
                    allowGroupBy: true,
                    description: 'Customer group ID'
                ),
                new Field(
                    name: 'group_name',
                    column: 'customer_group.customer_group_code',
                    sortable: false,
                    allowGroupBy: true
                ),
                new Field(
                    name: 'website_id',
                    type: 'integer',
                    allowGroupBy: true,
                    description: 'Website ID'
                ),
                new Field(
                    name: 'store_id',
                    type: 'integer',
                    allowGroupBy: true,
                    description: 'Store ID'
                ),
                new Field(
                    name: 'is_active',
                    type: 'integer',
                    sortable: false,
                    allowGroupBy: true,
                    description: 'Active status (1 = active, 0 = inactive)'
                ),
                new Field(
                    name: 'created_at',
                    type: 'date',
                    allowGroupBy: true,
                    groupByOptions: ['month', 'day'],
                    description: 'Registration date (YYYY-MM-DD)'
                ),
                new Field(
                    name: 'updated_at',
                    type: 'date',
                    description: 'Last update date (YYYY-MM-DD)'
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
            'Search for customers by email or ID',
            'Filter customers by registration date',
            'Analyze customer data',
        ];
    }

    /**
     * @inheritDoc
     */
    protected function getExamplePrompts(): array
    {
        return [
            'Show me recent customers',
            'Find customer with email john@example.com',
            'Get customers registered last week',
            'List customers in group ID 1',
        ];
    }

    /**
     * @inheritDoc
     */
    protected function transformRows(array $rows): array
    {
        foreach ($rows as &$row) {
            if (isset($row['is_active'])) {
                $row['is_active'] = $row['is_active'] ? 'Active' : 'Inactive';
            }
        }
        return $rows;
    }
}
